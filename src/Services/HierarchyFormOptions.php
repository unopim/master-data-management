<?php

namespace Webkul\MasterDataManagement\Services;

use Webkul\MasterDataManagement\Models\EntityType;
use Webkul\MasterDataManagement\Models\Hierarchy;
use Webkul\MasterDataManagement\Repositories\HierarchyRepository;

/**
 * Builds the view data of the entity type's hierarchies tab.
 */
class HierarchyFormOptions
{
    public function __construct(
        protected HierarchyRepository $hierarchyRepository,
        protected HierarchyService $hierarchyService,
    ) {}

    /**
     * Every hierarchy's editable state keyed by id; a hierarchical type gets its primary hierarchy on first view.
     *
     * @return array<string, mixed>
     */
    public function formData(EntityType $entityType): array
    {
        if ($entityType->is_hierarchical) {
            $this->hierarchyService->ensurePrimary($entityType);
        }

        return [
            'hierarchiesById' => $this->hierarchyRepository->forEntityType((int) $entityType->id)
                ->mapWithKeys(fn (Hierarchy $hierarchy): array => [$hierarchy->id => [
                    'id'         => $hierarchy->id,
                    'code'       => $hierarchy->code,
                    'is_primary' => $hierarchy->is_primary,
                    'label'      => $hierarchy->translations->pluck('label', 'locale')->all(),
                ]]),
        ];
    }
}
