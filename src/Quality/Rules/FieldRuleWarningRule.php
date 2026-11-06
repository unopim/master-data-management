<?php

namespace Webkul\MasterDataManagement\Quality\Rules;

use Illuminate\Database\Eloquent\Collection;
use Webkul\MasterDataManagement\Enums\QualitySeverity;
use Webkul\MasterDataManagement\Models\EntityType;
use Webkul\MasterDataManagement\Models\Field;
use Webkul\MasterDataManagement\Quality\DetectedIssue;
use Webkul\MasterDataManagement\Repositories\FieldRepository;
use Webkul\MasterDataManagement\Validation\ValueRuleChecker;

/**
 * Reports the stored values that break a warning-severity validation rule of their field, which never refuses the
 * write: one warning per record, rule and value, so fixing the value closes it on the next write or scan.
 */
class FieldRuleWarningRule implements QualityRule
{
    public const string CODE = 'field_rule';

    public function __construct(
        protected ValueRuleChecker $valueRuleChecker,
        protected FieldRepository $fieldRepository,
    ) {}

    /**
     * {@inheritdoc}
     */
    public function code(): string
    {
        return self::CODE;
    }

    /**
     * Check each record's common values, and per-locale values in every locale, against the warning rules.
     *
     * {@inheritdoc}
     */
    public function evaluate(EntityType $type, Collection $records): array
    {
        $warnings = $this->valueRuleChecker->rulesByField((int) $type->id, false);

        if ($warnings === []) {
            return [];
        }

        $fields = $this->fieldRepository->activeFor((int) $type->id)->keyBy('id');

        $issues = [];

        foreach ($records as $record) {
            $values = $record->values ?? [];

            foreach ($warnings as $fieldId => $rules) {
                $field = $fields->get($fieldId);

                if (! $field instanceof Field) {
                    continue;
                }

                $sections = $field->value_per_locale
                    ? array_keys(is_array($values['locale_specific'] ?? null) ? $values['locale_specific'] : [])
                    : [null];

                foreach ($sections as $locale) {
                    $siblings = $this->valueRuleChecker->siblings($values, $locale === null ? null : (string) $locale);

                    $attribute = $locale === null ? 'values.common.'.$field->code : 'values.locale_specific.'.$locale.'.'.$field->code;

                    foreach ($rules as $rule) {
                        $message = $this->valueRuleChecker->failure($rule, $field, $siblings[$field->code] ?? null, $siblings);

                        if ($message === null) {
                            continue;
                        }

                        $issues[] = new DetectedIssue(
                            (int) $record->id,
                            self::CODE,
                            mb_substr($attribute.'#'.$rule->id, 0, 191),
                            QualitySeverity::Warning,
                            ['field' => (string) $field->code, 'message' => mb_substr($message, 0, 500)],
                        );
                    }
                }
            }
        }

        return $issues;
    }
}
