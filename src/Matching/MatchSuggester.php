<?php

namespace Webkul\MasterDataManagement\Matching;

use InvalidArgumentException;
use Webkul\MasterDataManagement\Matching\Comparators\FuzzyComparator;
use Webkul\MasterDataManagement\Matching\Contracts\Comparator;
use Webkul\MasterDataManagement\Models\EntityType;
use Webkul\MasterDataManagement\Models\MatchRule;
use Webkul\MasterDataManagement\Models\Record;
use Webkul\MasterDataManagement\Repositories\MatchRuleRepository;
use Webkul\MasterDataManagement\Repositories\RecordRepository;
use Webkul\MasterDataManagement\Services\Normalizer;
use Webkul\User\Models\Admin;

/**
 * Proposes existing records for an incoming source row.
 *
 * Candidates come from a bounded lookup: code and label prefixes, the indexed label field, and the values of the
 * type's blocking match rules. Without match rules a candidate scores the better of its fuzzy code and label
 * similarity; with rules it scores the weighted average of the rules the subject has a value for.
 */
class MatchSuggester
{
    /**
     * The most records one suggestion run scores.
     */
    public const int CANDIDATE_LIMIT = 200;

    /**
     * Suggestions scoring below this are left out.
     */
    public const float MIN_SCORE = 0.4;

    /**
     * The reason prefix of a score reached through match rules, followed by the target that contributed most.
     */
    public const string RULE_REASON_PREFIX = 'rule_';

    /**
     * The shortest token used as a lookup prefix.
     */
    protected const int MIN_PREFIX_LENGTH = 2;

    public function __construct(
        protected RecordRepository $recordRepository,
        protected MatchRuleRepository $matchRuleRepository,
        protected ComparatorRegistry $comparatorRegistry,
        protected FuzzyComparator $fuzzyComparator,
        protected Normalizer $normalizer,
    ) {}

    /**
     * The best scoring records of the entity type for the incoming row, highest score first.
     *
     * `values` follows the RecordWriter shape (`common` and `locale_specific`) and is only read by match rules.
     *
     * @param  array{code?: ?string, label?: ?string, values?: ?array<string, mixed>}  $subject
     * @return list<MatchSuggestion>
     */
    public function suggest(EntityType $type, array $subject, int $limit = 5, ?Admin $admin = null): array
    {
        $code = trim((string) ($subject['code'] ?? ''));

        $label = trim((string) ($subject['label'] ?? ''));

        $values = is_array($subject['values'] ?? null) ? $subject['values'] : [];

        $rules = $this->resolveRules((int) $type->id);

        $candidates = $this->recordRepository->matchCandidates(
            (int) $type->id,
            $code === '' ? [] : [$code],
            $this->prefixes($code, $label),
            self::CANDIDATE_LIMIT,
            $admin,
            $type->label_field_code ?: null,
            $this->blockingKeys($rules, $code, $label, $values),
            $rules !== [],
        );

        $suggestions = [];

        foreach ($candidates as $record) {
            $suggestion = $rules === []
                ? $this->score($record, $code, $label)
                : $this->scoreByRules($record, $rules, $code, $label, $values);

            if ($suggestion->score >= self::MIN_SCORE) {
                $suggestions[] = $suggestion;
            }
        }

        usort($suggestions, fn (MatchSuggestion $left, MatchSuggestion $right): int => $right->score <=> $left->score
            ?: $left->record->id <=> $right->record->id);

        return array_slice($suggestions, 0, max(1, $limit));
    }

    /**
     * Score one record against the incoming code and label, keeping the better of the code and label scores.
     */
    public function score(Record $record, string $code, string $label): MatchSuggestion
    {
        [$codeScore, $codeReason] = $this->compare($code, (string) $record->code, 'code');

        [$labelScore, $labelReason] = $this->compare($label, (string) $record->label, 'label');

        return $codeScore >= $labelScore
            ? new MatchSuggestion($record, $codeScore, $codeReason)
            : new MatchSuggestion($record, $labelScore, $labelReason);
    }

    /**
     * Score one record by the weighted average of the rules the subject has a value for; a record lacking the value
     * scores 0 on that rule. The reason names the target that contributed most.
     *
     * @param  list<array{0: MatchRule, 1: Comparator}>  $rules
     * @param  array<string, mixed>  $values
     */
    protected function scoreByRules(Record $record, array $rules, string $code, string $label, array $values): MatchSuggestion
    {
        $total = 0.0;

        $weights = 0.0;

        $contributions = [];

        foreach ($rules as [$rule, $comparator]) {
            [$left, $locale] = $this->subjectValue($rule, $code, $label, $values);

            if ($left === '') {
                continue;
            }

            $right = $this->recordValue($record, $rule, $locale);

            $score = $right === '' ? 0.0 : max(0.0, min(1.0, $comparator->compare($left, $right, (array) ($rule->options ?? []))));

            $total += $rule->weight * $score;

            $weights += $rule->weight;

            $contributions[$rule->target] = ($contributions[$rule->target] ?? 0.0) + $rule->weight * $score;
        }

        if ($weights <= 0.0) {
            return new MatchSuggestion($record, 0.0, self::RULE_REASON_PREFIX.$rules[0][0]->target);
        }

        arsort($contributions);

        return new MatchSuggestion($record, round($total / $weights, 4), self::RULE_REASON_PREFIX.array_key_first($contributions));
    }

    /**
     * Compare two values with the fuzzy comparator; the reason is how they compared followed by the kind.
     *
     * @return array{0: float, 1: string}
     */
    protected function compare(string $left, string $right, string $kind): array
    {
        [$score, $how] = $this->fuzzyComparator->assess($left, $right);

        return [$score, $how.'_'.$kind];
    }

    /**
     * The type's match rules paired with their comparators; rules whose comparator is no longer registered are left out.
     *
     * @return list<array{0: MatchRule, 1: Comparator}>
     */
    protected function resolveRules(int $entityTypeId): array
    {
        $rules = [];

        foreach ($this->matchRuleRepository->forEntityType($entityTypeId) as $rule) {
            if ($rule->weight <= 0) {
                continue;
            }

            try {
                $rules[] = [$rule, $this->comparatorRegistry->get($rule->comparator)];
            } catch (InvalidArgumentException) {
                continue;
            }
        }

        return $rules;
    }

    /**
     * The target and value pairs of the blocking rules the subject has a value for.
     *
     * @param  list<array{0: MatchRule, 1: Comparator}>  $rules
     * @param  array<string, mixed>  $values
     * @return list<array{0: string, 1: string}>
     */
    protected function blockingKeys(array $rules, string $code, string $label, array $values): array
    {
        $keys = [];

        foreach ($rules as [$rule]) {
            if (! $rule->is_blocking) {
                continue;
            }

            [$value] = $this->subjectValue($rule, $code, $label, $values);

            if ($value !== '') {
                $keys[$rule->target."\0".$value] = [$rule->target, mb_substr($value, 0, 255)];
            }
        }

        return array_values($keys);
    }

    /**
     * The subject's value for the rule target, and the locale it was read from for a per-locale value.
     *
     * @param  array<string, mixed>  $values
     * @return array{0: string, 1: ?string}
     */
    protected function subjectValue(MatchRule $rule, string $code, string $label, array $values): array
    {
        return match ($rule->target) {
            MatchRule::TARGET_CODE  => [$code, null],
            MatchRule::TARGET_LABEL => [$label, null],
            default                 => $this->fieldValue($values, $rule->target, null),
        };
    }

    /**
     * The record's value for the rule target, read in the subject's locale when it has one.
     */
    protected function recordValue(Record $record, MatchRule $rule, ?string $locale): string
    {
        return match ($rule->target) {
            MatchRule::TARGET_CODE  => trim((string) $record->code),
            MatchRule::TARGET_LABEL => trim((string) $record->label),
            default                 => $this->fieldValue((array) ($record->values ?? []), $rule->target, $locale)[0],
        };
    }

    /**
     * A field value as text, from the common values or else the given locale or the first locale holding one.
     *
     * @param  array<string, mixed>  $values
     * @return array{0: string, 1: ?string}
     */
    protected function fieldValue(array $values, string $fieldCode, ?string $locale): array
    {
        $common = $this->text($values['common'][$fieldCode] ?? null);

        if ($common !== '') {
            return [$common, null];
        }

        $localized = is_array($values['locale_specific'] ?? null) ? $values['locale_specific'] : [];

        if ($locale !== null) {
            $text = $this->text($localized[$locale][$fieldCode] ?? null);

            if ($text !== '') {
                return [$text, $locale];
            }
        }

        foreach ($localized as $code => $localeValues) {
            $text = is_array($localeValues) ? $this->text($localeValues[$fieldCode] ?? null) : '';

            if ($text !== '') {
                return [$text, (string) $code];
            }
        }

        return ['', null];
    }

    /**
     * A stored value as comparable text: scalars as they are, lists joined by spaces, anything else empty.
     */
    protected function text(mixed $value): string
    {
        return match (true) {
            is_bool($value)   => $value ? '1' : '0',
            is_scalar($value) => trim((string) $value),
            is_array($value)  => trim(implode(' ', array_filter($value, is_scalar(...)))),
            default           => '',
        };
    }

    /**
     * The lookup prefixes: the first token of the code and of the label, raw and normalised.
     *
     * @return list<string>
     */
    protected function prefixes(string $code, string $label): array
    {
        $prefixes = [];

        foreach ([$code, $label] as $value) {
            if ($value === '') {
                continue;
            }

            $raw = mb_strtolower((string) preg_split('/[\s\-_.\/]+/u', $value, 2)[0]);

            $normalized = explode(' ', $this->normalizer->normalize($value), 2)[0];

            foreach ([$raw, $normalized] as $prefix) {
                if (mb_strlen($prefix) >= self::MIN_PREFIX_LENGTH) {
                    $prefixes[] = mb_substr($prefix, 0, 64);
                }
            }
        }

        return array_values(array_unique($prefixes));
    }
}
