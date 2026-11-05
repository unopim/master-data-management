<?php

namespace Webkul\MasterDataManagement\Services;

use Webkul\MasterDataManagement\Matching\ComparatorRegistry;
use Webkul\MasterDataManagement\Models\EntityType;
use Webkul\MasterDataManagement\Models\Field;
use Webkul\MasterDataManagement\Models\MatchRule;
use Webkul\MasterDataManagement\Repositories\FieldRepository;
use Webkul\MasterDataManagement\Repositories\MatchRuleRepository;

/**
 * Builds the view data of the entity type's matching tab.
 */
class MatchRuleFormOptions
{
    public function __construct(
        protected FieldRepository $fieldRepository,
        protected MatchRuleRepository $matchRuleRepository,
        protected ComparatorRegistry $comparatorRegistry,
    ) {}

    /**
     * The target and comparator options and every match rule's editable state, keyed by id.
     *
     * @return array<string, mixed>
     */
    public function formData(EntityType $entityType): array
    {
        $fields = $this->fieldRepository->activeFor((int) $entityType->id)
            ->map(fn (Field $field): array => ['id' => (string) $field->code, 'label' => ($field->label ?: $field->code).' ('.$field->code.')'])
            ->values()
            ->all();

        return [
            'matchTargetOptions' => [
                ['id' => MatchRule::TARGET_CODE, 'label' => trans('masterdata::app.match-rules.targets.code')],
                ['id' => MatchRule::TARGET_LABEL, 'label' => trans('masterdata::app.match-rules.targets.label')],
                ...$fields,
            ],
            'comparatorOptions' => $this->comparatorRegistry->options(),
            'matchRulesById'    => $this->matchRuleRepository->forEntityType((int) $entityType->id)
                ->mapWithKeys(fn (MatchRule $rule): array => [$rule->id => [
                    'id'          => $rule->id,
                    'target'      => $rule->target,
                    'comparator'  => $rule->comparator,
                    'weight'      => $rule->weight,
                    'is_blocking' => $rule->is_blocking,
                    'sort_order'  => $rule->sort_order,
                    'options'     => (object) ($rule->options ?? []),
                ]]),
        ];
    }
}
