<?php

namespace Webkul\MasterDataManagement\Services;

use Illuminate\Database\Eloquent\Collection;
use Webkul\Core\Repositories\LocaleRepository;
use Webkul\MasterDataManagement\Enums\CrossFieldOperator;
use Webkul\MasterDataManagement\Enums\ValidationSeverity;
use Webkul\MasterDataManagement\FieldTypes\FieldTypeRegistry;
use Webkul\MasterDataManagement\Models\EntityType;
use Webkul\MasterDataManagement\Models\Field;
use Webkul\MasterDataManagement\Models\FieldTransform;
use Webkul\MasterDataManagement\Models\FieldValidationRule;
use Webkul\MasterDataManagement\Repositories\FieldRepository;
use Webkul\MasterDataManagement\Repositories\FieldTransformRepository;
use Webkul\MasterDataManagement\Repositories\FieldValidationRuleRepository;
use Webkul\MasterDataManagement\Standardization\TransformRegistry;
use Webkul\MasterDataManagement\Validation\RuleTypeRegistry;

/**
 * Builds the view data of the entity type's validation tab and names the fields its rules and transforms may cover.
 */
class FieldRuleFormOptions
{
    public function __construct(
        protected FieldRepository $fieldRepository,
        protected FieldTypeRegistry $fieldTypeRegistry,
        protected FieldValidationRuleRepository $fieldValidationRuleRepository,
        protected FieldTransformRepository $fieldTransformRepository,
        protected RuleTypeRegistry $ruleTypeRegistry,
        protected TransformRegistry $transformRegistry,
        protected LocaleRepository $localeRepository,
    ) {}

    /**
     * The active fields of the type whose values live in the values JSON, the ones rules and transforms apply to.
     *
     * @return Collection<int, Field>
     */
    public function ruleFields(int $entityTypeId): Collection
    {
        return $this->fieldRepository->activeFor($entityTypeId)
            ->filter(fn (Field $field): bool => $this->fieldTypeRegistry->has($field->type) && $this->fieldTypeRegistry->get($field->type)->storesInValues())
            ->values();
    }

    /**
     * The field, type, severity, operator, transform and locale options and every rule's and transform's editable
     * state, keyed by id.
     *
     * @return array<string, mixed>
     */
    public function formData(EntityType $entityType): array
    {
        $fields = $this->ruleFields((int) $entityType->id);

        return [
            'ruleFieldOptions' => $fields
                ->map(fn (Field $field): array => ['id' => $field->id, 'label' => $this->fieldLabel($field)])
                ->all(),
            'crossFieldOptions' => $this->fieldRepository->activeFor((int) $entityType->id)
                ->map(fn (Field $field): array => ['id' => (string) $field->code, 'label' => $this->fieldLabel($field)])
                ->values()
                ->all(),
            'ruleTypeOptions' => $this->ruleTypeRegistry->options(),
            'severityOptions' => array_map(
                fn (ValidationSeverity $severity): array => ['id' => $severity->value, 'label' => trans('masterdata::app.validation-rules.severities.'.$severity->value)],
                ValidationSeverity::cases(),
            ),
            'operatorOptions' => array_map(
                fn (CrossFieldOperator $operator): array => ['id' => $operator->value, 'label' => trans('masterdata::app.validation-rules.operators.'.$operator->value)],
                CrossFieldOperator::cases(),
            ),
            'transformOptions'    => $this->transformRegistry->options(),
            'ruleLocales'         => $this->localeRepository->getActiveLocales(),
            'validationRulesById' => $this->fieldValidationRuleRepository->forEntityType((int) $entityType->id)
                ->mapWithKeys(fn (FieldValidationRule $rule): array => [$rule->id => [
                    'id'         => $rule->id,
                    'field_id'   => $rule->field_id,
                    'type'       => $rule->type,
                    'severity'   => $rule->severity->value,
                    'params'     => (object) ($rule->params ?? []),
                    'messages'   => (object) ($rule->messages ?? []),
                    'sort_order' => $rule->sort_order,
                ]]),
            'fieldTransformsById' => $this->fieldTransformRepository->forEntityType((int) $entityType->id)
                ->mapWithKeys(fn (FieldTransform $transform): array => [$transform->id => [
                    'id'         => $transform->id,
                    'field_id'   => $transform->field_id,
                    'transform'  => $transform->transform,
                    'sort_order' => $transform->sort_order,
                ]]),
        ];
    }

    /**
     * The option label of a field: its translated label and its code.
     */
    protected function fieldLabel(Field $field): string
    {
        return ($field->label ?: $field->code).' ('.$field->code.')';
    }
}
