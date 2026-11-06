<?php

namespace Webkul\MasterDataManagement\Validation;

use Illuminate\Support\Facades\Lang;
use Webkul\MasterDataManagement\Models\Field;
use Webkul\MasterDataManagement\Models\FieldValidationRule;
use Webkul\MasterDataManagement\Repositories\FieldValidationRuleRepository;

/**
 * Checks field values against their configured validation rules and words the failures.
 *
 * Empty values pass every rule, as presence is the field's `required` flag's job. A rule whose type is no longer
 * registered fails closed, so removing a package cannot silently drop a check.
 */
class ValueRuleChecker
{
    public function __construct(
        protected RuleTypeRegistry $ruleTypeRegistry,
        protected FieldValidationRuleRepository $fieldValidationRuleRepository,
    ) {}

    /**
     * The entity type's validation rules of the given severity, grouped by field id.
     *
     * @return array<int, list<FieldValidationRule>>
     */
    public function rulesByField(int $entityTypeId, bool $blocking): array
    {
        $grouped = [];

        foreach ($this->fieldValidationRuleRepository->forEntityType($entityTypeId) as $rule) {
            if ($rule instanceof FieldValidationRule && $rule->blocks() === $blocking) {
                $grouped[$rule->field_id][] = $rule;
            }
        }

        return $grouped;
    }

    /**
     * The failure message of the rule for the value, or null when it passes.
     *
     * @param  array<array-key, mixed>  $siblings  The other values of the record, by field code.
     */
    public function failure(FieldValidationRule $rule, Field $field, mixed $value, array $siblings): ?string
    {
        if (in_array($value, [null, '', []], true)) {
            return null;
        }

        $attribute = is_string($field->label) && $field->label !== '' ? $field->label : (string) $field->code;

        if (! $this->ruleTypeRegistry->has((string) $rule->type)) {
            return trans('masterdata::app.validation-rules.messages.unavailable', ['attribute' => $attribute, 'rule' => (string) $rule->type]);
        }

        $type = $this->ruleTypeRegistry->get((string) $rule->type);

        $params = (array) ($rule->params ?? []);

        if ($type->passes($value, $params, $siblings)) {
            return null;
        }

        $replace = ['attribute' => $attribute] + $type->placeholders($params);

        $custom = $this->customMessage($rule);

        if ($custom !== null) {
            return strtr($custom, array_combine(array_map(fn (string $key): string => ':'.$key, array_keys($replace)), $replace));
        }

        $key = 'masterdata::app.validation-rules.messages.'.$rule->type;

        return trans(Lang::has($key) ? $key : 'masterdata::app.validation-rules.messages.default', $replace);
    }

    /**
     * The rule's message in the current locale, else in the default locale; null when neither is set.
     */
    protected function customMessage(FieldValidationRule $rule): ?string
    {
        $messages = (array) ($rule->messages ?? []);

        foreach ([app()->getLocale(), (string) config('masterdata.default_locale', 'en_US')] as $locale) {
            if (is_string($messages[$locale] ?? null) && trim($messages[$locale]) !== '') {
                return $messages[$locale];
            }
        }

        return null;
    }

    /**
     * The values a value of the section is checked with: a per-locale value also sees the record's common values.
     *
     * @param  array<array-key, mixed>  $values  The record's `values`, in the `common` / `locale_specific` shape.
     * @return array<array-key, mixed>
     */
    public function siblings(array $values, ?string $locale): array
    {
        $common = is_array($values['common'] ?? null) ? $values['common'] : [];

        if ($locale === null) {
            return $common;
        }

        $localized = $values['locale_specific'][$locale] ?? null;

        return array_replace($common, is_array($localized) ? $localized : []);
    }
}
