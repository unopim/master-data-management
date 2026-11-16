<?php

namespace Webkul\MasterDataManagement\Http\ViewComposers;

use Illuminate\View\View;
use Webkul\MasterDataManagement\Models\EntityType;
use Webkul\MasterDataManagement\Repositories\EntityTypeRepository;
use Webkul\MasterDataManagement\Repositories\SourceSystemRepository;
use Webkul\MasterDataManagement\Services\WebhookRecordFilters;
use Webkul\Webhook\Models\Webhook;

/**
 * Feeds the "Master data" card of the webhook form with the edited webhook's filters and the select options.
 */
class WebhookFiltersCardComposer
{
    public function __construct(
        protected EntityTypeRepository $entityTypeRepository,
        protected SourceSystemRepository $sourceSystemRepository,
        protected WebhookRecordFilters $webhookRecordFilters,
    ) {}

    /**
     * Bind the card data to the view.
     */
    public function compose(View $view): void
    {
        $webhook = $view->getData()['webhook'] ?? null;

        $view->with([
            'masterdataFilters'   => $this->webhookRecordFilters->current($webhook instanceof Webhook ? $webhook : null),
            'entityTypeOptions'   => $this->entityTypeRepository->listActive()
                ->map(fn (EntityType $type): array => ['id' => $type->code, 'label' => $type->name ?: $type->code])
                ->values()
                ->all(),
            'sourceSystemOptions' => collect($this->sourceSystemRepository->namesByCode())
                ->map(fn (mixed $name, string $code): array => ['id' => $code, 'label' => (string) ($name ?: $code)])
                ->values()
                ->all(),
        ]);
    }
}
