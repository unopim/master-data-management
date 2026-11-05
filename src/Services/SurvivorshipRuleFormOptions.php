<?php

namespace Webkul\MasterDataManagement\Services;

use Webkul\MasterDataManagement\Models\EntityType;
use Webkul\MasterDataManagement\Models\Field;
use Webkul\MasterDataManagement\Models\SurvivorshipRule;
use Webkul\MasterDataManagement\Repositories\FieldRepository;
use Webkul\MasterDataManagement\Repositories\SourceSystemRepository;
use Webkul\MasterDataManagement\Repositories\SurvivorshipRuleRepository;
use Webkul\MasterDataManagement\Survivorship\StrategyRegistry;

/**
 * Builds the view data of the entity type's survivorship tab.
 */
class SurvivorshipRuleFormOptions
{
    /**
     * The most source systems the source order picker lists.
     */
    protected const int SOURCE_LIMIT = 200;

    public function __construct(
        protected FieldRepository $fieldRepository,
        protected SurvivorshipRuleRepository $survivorshipRuleRepository,
        protected SourceSystemRepository $sourceSystemRepository,
        protected StrategyRegistry $strategyRegistry,
    ) {}

    /**
     * The target, strategy and source system options and every survivorship rule's editable state, keyed by id.
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
            'survivorshipTargetOptions' => [
                ['id' => SurvivorshipRule::TARGET_DEFAULT, 'label' => trans('masterdata::app.survivorship-rules.targets.default')],
                ['id' => SurvivorshipRule::TARGET_LABEL, 'label' => trans('masterdata::app.survivorship-rules.targets.label')],
                ...$fields,
            ],
            'strategyOptions'       => $this->strategyRegistry->options(),
            'sourceSystemOptions'   => $this->sourceSystemRepository->searchOptions(null, [], 1, self::SOURCE_LIMIT, false)['options'],
            'survivorshipRulesById' => $this->survivorshipRuleRepository->forEntityType((int) $entityType->id)
                ->mapWithKeys(fn (SurvivorshipRule $rule): array => [$rule->id => [
                    'id'           => $rule->id,
                    'target'       => $rule->target ?? SurvivorshipRule::TARGET_DEFAULT,
                    'strategy'     => $rule->strategy,
                    'sort_order'   => $rule->sort_order,
                    'source_order' => array_values(array_map(intval(...), (array) ($rule->options[SurvivorshipRule::OPTION_SOURCE_ORDER] ?? []))),
                ]]),
        ];
    }
}
