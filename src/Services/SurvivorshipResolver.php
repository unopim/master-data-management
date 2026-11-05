<?php

namespace Webkul\MasterDataManagement\Services;

use Webkul\MasterDataManagement\Models\Record;
use Webkul\MasterDataManagement\Models\SurvivorshipRule;
use Webkul\MasterDataManagement\Repositories\SourceSystemRepository;
use Webkul\MasterDataManagement\Repositories\SurvivorshipRuleRepository;
use Webkul\MasterDataManagement\Repositories\XrefRepository;
use Webkul\MasterDataManagement\Survivorship\Contracts\SurvivorshipStrategy;
use Webkul\MasterDataManagement\Survivorship\StrategyRegistry;
use Webkul\MasterDataManagement\Survivorship\SurvivorshipCandidate;

/**
 * Decides, field by field, which of two records' values the merged golden record keeps.
 *
 * A choice names the side a field's value comes from, `survivor` or `merged`, and applies to the common value and
 * every locale's value of that field. Fields without a choice follow the entity type's survivorship rules, and
 * without a rule the most complete rule: the survivor's value unless it is empty and the merged record has one.
 */
class SurvivorshipResolver
{
    public const string SURVIVOR = 'survivor';

    public const string MERGED = 'merged';

    public function __construct(
        protected SurvivorshipRuleRepository $survivorshipRuleRepository,
        protected StrategyRegistry $strategyRegistry,
        protected XrefRepository $xrefRepository,
        protected SourceSystemRepository $sourceSystemRepository,
    ) {}

    /**
     * The side each field takes under the entity type's survivorship rules, for every field either record holds.
     *
     * A field follows its own rule, else the type's default rule, else suggest(); the record label follows only a
     * rule targeting it. A side of null leaves the field to the steward, as a manual rule asks.
     *
     * @param  array<string, list<string>>  $survivorReferences
     * @param  array<string, list<string>>  $mergedReferences
     * @return array<string, string|null>
     */
    public function decide(Record $survivor, Record $merged, array $survivorReferences = [], array $mergedReferences = []): array
    {
        return array_replace(
            $this->suggest($survivor->values ?? [], $merged->values ?? [], $survivorReferences, $mergedReferences),
            $this->ruledSides($survivor, $merged, $survivorReferences, $mergedReferences),
        );
    }

    /**
     * The side of each target a survivorship rule decides, null where the rule leaves it to the steward.
     *
     * @param  array<string, list<string>>  $survivorReferences
     * @param  array<string, list<string>>  $mergedReferences
     * @return array<string, string|null>
     */
    public function ruledSides(Record $survivor, Record $merged, array $survivorReferences = [], array $mergedReferences = []): array
    {
        $picks = $this->picks($survivor, [$merged], [(int) $survivor->id => $survivorReferences, (int) $merged->id => $mergedReferences]);

        return array_map(fn (?Record $winner): ?string => match (true) {
            $winner === null                          => null,
            (int) $winner->id === (int) $survivor->id => self::SURVIVOR,
            default                                   => self::MERGED,
        }, $picks);
    }

    /**
     * The record whose value of each ruled target survives a merge of the others into the survivor.
     *
     * Only targets a rule decides are returned: every field when the type has a default rule, else the fields and
     * label with a rule of their own. A null pick leaves the target to the steward. Strategies see the survivor
     * first and the others by id, so ties go the same way every time.
     *
     * @param  list<Record>  $others
     * @param  array<int, array<string, list<string>>>  $references  Reference field targets keyed by record id.
     * @return array<string, Record|null>
     */
    public function picks(Record $survivor, array $others, array $references = []): array
    {
        $rules = $this->survivorshipRuleRepository->forEntityType((int) $survivor->entity_type_id)
            ->filter(fn (SurvivorshipRule $rule): bool => $this->strategyRegistry->has((string) $rule->strategy));

        if ($rules->isEmpty()) {
            return [];
        }

        usort($others, fn (Record $a, Record $b): int => (int) $a->id <=> (int) $b->id);

        $records = [$survivor, ...$others];

        $default = $rules->first(fn (SurvivorshipRule $rule): bool => $rule->isDefault());

        $byTarget = $rules->reject(fn (SurvivorshipRule $rule): bool => $rule->isDefault())->keyBy('target');

        $codes = $this->fieldCodes(...array_map(fn (Record $record): array => $record->values ?? [], $records));

        foreach ($references as $recordReferences) {
            $codes = [...$codes, ...array_map(strval(...), array_keys($recordReferences))];
        }

        $sources = $this->sources($records);

        $strategies = [];

        $picks = [];

        foreach (array_unique($codes) as $code) {
            $rule = $byTarget->get($code) ?? $default;

            if (! $rule instanceof SurvivorshipRule) {
                continue;
            }

            $candidates = array_map(fn (Record $record): SurvivorshipCandidate => new SurvivorshipCandidate(
                $record,
                ($references[(int) $record->id][$code] ?? []) !== [] ? ['common' => $references[(int) $record->id][$code]] : $this->valuesOf($record->values ?? [], $code),
                $sources[(int) $record->id] ?? [],
            ), $records);

            $picks[$code] = $this->pick($strategies, $rule, $candidates);
        }

        $labelRule = $byTarget->get(SurvivorshipRule::TARGET_LABEL);

        if ($labelRule instanceof SurvivorshipRule && ! array_key_exists(SurvivorshipRule::TARGET_LABEL, $picks)) {
            $picks[SurvivorshipRule::TARGET_LABEL] = $this->pick($strategies, $labelRule, array_map(
                fn (Record $record): SurvivorshipCandidate => new SurvivorshipCandidate($record, ['common' => $record->label], $sources[(int) $record->id] ?? []),
                $records,
            ));
        }

        return $picks;
    }

    /**
     * The side each field's value comes from when nobody chose, for every field either record holds.
     *
     * @param  array<string, mixed>  $survivorValues
     * @param  array<string, mixed>  $mergedValues
     * @param  array<string, list<string>>  $survivorReferences
     * @param  array<string, list<string>>  $mergedReferences
     * @return array<string, string>
     */
    public function suggest(array $survivorValues, array $mergedValues, array $survivorReferences = [], array $mergedReferences = []): array
    {
        $suggestions = [];

        $codes = array_unique([...$this->fieldCodes($survivorValues, $mergedValues), ...array_map(strval(...), array_keys($survivorReferences + $mergedReferences))]);

        foreach ($codes as $code) {
            $survivorFilled = $this->hasValue($this->valuesOf($survivorValues, $code)) || ($survivorReferences[$code] ?? []) !== [];

            $mergedFilled = $this->hasValue($this->valuesOf($mergedValues, $code)) || ($mergedReferences[$code] ?? []) !== [];

            $suggestions[$code] = ! $survivorFilled && $mergedFilled ? self::MERGED : self::SURVIVOR;
        }

        return $suggestions;
    }

    /**
     * Build the merged values: each field from the chosen side, else from the suggested one.
     *
     * @param  array<string, mixed>  $survivorValues
     * @param  array<string, mixed>  $mergedValues
     * @param  array<string, string>  $choices
     * @return array<string, mixed>
     */
    public function combineValues(array $survivorValues, array $mergedValues, array $choices): array
    {
        $choices += $this->suggest($survivorValues, $mergedValues);

        $result = $survivorValues;

        foreach ($choices as $code => $side) {
            if ($side !== self::MERGED) {
                continue;
            }

            $this->put($result, 'common', null, $code, $mergedValues['common'] ?? []);

            $locales = array_unique([...array_keys($survivorValues['locale_specific'] ?? []), ...array_keys($mergedValues['locale_specific'] ?? [])]);

            foreach ($locales as $locale) {
                $this->put($result, 'locale_specific', (string) $locale, $code, $mergedValues['locale_specific'][$locale] ?? []);
            }
        }

        return $result;
    }

    /**
     * The reference fields whose targets the survivor takes from the merged record.
     *
     * @param  array<string, list<string>>  $survivorReferences
     * @param  array<string, list<string>>  $mergedReferences
     * @param  array<string, string>  $choices
     * @return array<string, list<string>>
     */
    public function combineReferences(array $survivorReferences, array $mergedReferences, array $choices): array
    {
        $choices += $this->suggest([], [], $survivorReferences, $mergedReferences);

        $taken = [];

        foreach ($choices as $code => $side) {
            if ($side === self::MERGED && ($mergedReferences[$code] ?? []) !== ($survivorReferences[$code] ?? [])) {
                $taken[$code] = $mergedReferences[$code] ?? [];
            }
        }

        return $taken;
    }

    /**
     * Every field code the given values hold, in first-seen order.
     *
     * @param  array<string, mixed>  ...$sides
     * @return list<string>
     */
    public function fieldCodes(array ...$sides): array
    {
        $codes = [];

        foreach ($sides as $side) {
            foreach (array_keys($side['common'] ?? []) as $code) {
                $codes[(string) $code] = true;
            }

            foreach ($side['locale_specific'] ?? [] as $localeValues) {
                foreach (array_keys((array) $localeValues) as $code) {
                    $codes[(string) $code] = true;
                }
            }
        }

        return array_keys($codes);
    }

    /**
     * The field's common value and per-locale values, keyed `common` and by locale.
     *
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    public function valuesOf(array $values, string $code): array
    {
        $found = [];

        if (array_key_exists($code, $values['common'] ?? [])) {
            $found['common'] = $values['common'][$code];
        }

        foreach ($values['locale_specific'] ?? [] as $locale => $localeValues) {
            if (array_key_exists($code, (array) $localeValues)) {
                $found[(string) $locale] = $localeValues[$code];
            }
        }

        return $found;
    }

    /**
     * The record the rule's strategy picks, resolving each strategy once per call.
     *
     * @param  array<string, SurvivorshipStrategy>  $strategies
     * @param  list<SurvivorshipCandidate>  $candidates
     */
    protected function pick(array &$strategies, SurvivorshipRule $rule, array $candidates): ?Record
    {
        $strategy = $strategies[$rule->strategy] ??= $this->strategyRegistry->get((string) $rule->strategy);

        return $strategy->pick($candidates, (array) ($rule->options ?? []))?->record;
    }

    /**
     * The priority of each source system the records hold active external ids in, keyed by record id.
     *
     * @param  list<Record>  $records
     * @return array<int, array<int, int>>
     */
    protected function sources(array $records): array
    {
        $linked = $this->xrefRepository->activeSystemIdsByRecord(array_map(fn (Record $record): int => (int) $record->id, $records));

        $priorities = $this->sourceSystemRepository->prioritiesById(array_values(array_unique(array_merge([], ...array_values($linked)))));

        $sources = [];

        foreach ($linked as $recordId => $systemIds) {
            foreach ($systemIds as $systemId) {
                if (array_key_exists($systemId, $priorities)) {
                    $sources[$recordId][$systemId] = $priorities[$systemId];
                }
            }
        }

        return $sources;
    }

    /**
     * Whether any of the given values is filled.
     *
     * @param  array<array-key, mixed>  $values
     */
    protected function hasValue(array $values): bool
    {
        foreach ($values as $value) {
            if ($value !== null && $value !== '' && $value !== []) {
                return true;
            }
        }

        return false;
    }

    /**
     * Copy one field of a section from the source into the result, dropping it when the source lacks it.
     *
     * @param  array<string, mixed>  $result
     * @param  array<array-key, mixed>  $source
     */
    protected function put(array &$result, string $section, ?string $locale, string $code, array $source): void
    {
        if ($locale === null) {
            if (array_key_exists($code, $source)) {
                $result[$section][$code] = $source[$code];
            } else {
                unset($result[$section][$code]);
            }

            return;
        }

        if (array_key_exists($code, $source)) {
            $result[$section][$locale][$code] = $source[$code];
        } else {
            unset($result[$section][$locale][$code]);
        }
    }
}
