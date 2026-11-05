<?php

namespace Webkul\MasterDataManagement\Services;

use Illuminate\Database\Eloquent\Collection;
use Webkul\MasterDataManagement\Enums\CodeStrategy;
use Webkul\MasterDataManagement\FieldTypes\FieldTypeRegistry;
use Webkul\MasterDataManagement\Models\EntityType;
use Webkul\MasterDataManagement\Models\Field;
use Webkul\MasterDataManagement\Models\Record;
use Webkul\MasterDataManagement\Repositories\FieldRepository;
use Webkul\MasterDataManagement\Rules\UniqueRecordReference;
use Webkul\MasterDataManagement\Rules\UniqueRecordValue;

/**
 * Builds the validation rules of a record's values from the entity type's active fields.
 *
 * Per-locale fields are required in the default locale (`masterdata.default_locale`) only; other locales are optional.
 */
class FieldRuleBuilder
{
    public function __construct(
        protected FieldRepository $fieldRepository,
        protected FieldTypeRegistry $fieldTypeRegistry,
    ) {}

    /**
     * The rules of the `values` input, keyed by validation attribute.
     *
     * A type with fields also gets `values.common` and `values.locale_specific.*` rules that reject unknown keys;
     * a type without fields keeps free-form values and gets no rules.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rulesFor(EntityType $type, ?Record $ignore = null): array
    {
        $fields = $this->fieldRepository->activeFor($type->id);

        if ($fields->isEmpty()) {
            return [];
        }

        [$localized, $common] = $fields->partition(fn (Field $field): bool => $field->value_per_locale);

        $rules = [
            'values.common'            => $this->keysRule($common),
            'values.locale_specific.*' => $this->keysRule($localized),
        ];

        $ignoreRecordId = $ignore?->getKey();

        foreach ($common as $field) {
            $rules['values.common.'.$field->code] = $this->fieldRules($type, $field, $this->isRequired($type, $field), null, $ignoreRecordId);
        }

        foreach ($localized as $field) {
            if ($this->isRequired($type, $field)) {
                $rules['values.locale_specific.'.$this->defaultLocale().'.'.$field->code] = ['required'];
            }
        }

        foreach ($localized as $field) {
            $rules['values.locale_specific.*.'.$field->code] = $this->fieldRules($type, $field, false, UniqueRecordValue::ATTRIBUTE_LOCALE, $ignoreRecordId);
        }

        return $rules;
    }

    /**
     * The attribute names of the value rules for validation messages: the field's translated label, else its code.
     *
     * @return array<string, string>
     */
    public function fieldLabels(EntityType $type): array
    {
        $labels = [];

        foreach ($this->fieldRepository->activeFor($type->id) as $field) {
            $label = $field->label;

            $label = is_string($label) && $label !== '' ? $label : $field->code;

            if (! $field->value_per_locale) {
                $labels['values.common.'.$field->code] = $label;

                continue;
            }

            $labels['values.locale_specific.'.$this->defaultLocale().'.'.$field->code] = $label;
            $labels['values.locale_specific.*.'.$field->code] = $label;
        }

        return $labels;
    }

    /**
     * The active identifier fields of the type, in position order, when its codes are built from them.
     *
     * @return Collection<int, Field>
     */
    public function identifierFields(EntityType $type): Collection
    {
        if ($type->code_strategy !== CodeStrategy::IdentifierFields) {
            return new Collection;
        }

        return $this->fieldRepository->activeFor($type->id)
            ->filter(fn (Field $field): bool => $field->is_identifier)
            ->values();
    }

    /**
     * The locale whose values satisfy `required` for per-locale fields and build per-locale identifier codes.
     *
     * Read from `masterdata.default_locale`, not the runtime `app.locale`, which the admin middleware switches to
     * each admin's UI locale.
     */
    public function defaultLocale(): string
    {
        return (string) config('masterdata.default_locale', 'en_US');
    }

    /**
     * The rules of one value: presence, the type's rules and the uniqueness check.
     *
     * @return array<int, mixed>
     */
    protected function fieldRules(EntityType $type, Field $field, bool $required, ?string $locale, ?int $ignoreRecordId): array
    {
        $rules = [$required ? 'required' : 'nullable'];

        if ($this->fieldTypeRegistry->has($field->type)) {
            array_push($rules, ...$this->fieldTypeRegistry->get($field->type)->rules($field));
        }

        if ($field->is_unique) {
            $rules[] = $this->storesInValues($field)
                ? new UniqueRecordValue($type->id, $field->code, $locale, $ignoreRecordId)
                : new UniqueRecordReference($field->id, $ignoreRecordId);
        }

        return $rules;
    }

    /**
     * Whether the field must hold a value: required fields, and identifier fields that build the code.
     */
    public function isRequired(EntityType $type, Field $field): bool
    {
        return $field->is_required
            || ($field->is_identifier && $type->code_strategy === CodeStrategy::IdentifierFields);
    }

    /**
     * Whether the field's values live in the values JSON rather than as relations.
     */
    protected function storesInValues(Field $field): bool
    {
        return ! $this->fieldTypeRegistry->has($field->type) || $this->fieldTypeRegistry->get($field->type)->storesInValues();
    }

    /**
     * An array rule that allows only the codes of the given fields as keys.
     *
     * @param  Collection<int, Field>  $fields
     * @return array<int, string>
     */
    protected function keysRule(Collection $fields): array
    {
        if ($fields->isEmpty()) {
            return ['array', 'size:0'];
        }

        return ['array:'.$fields->pluck('code')->implode(',')];
    }
}
