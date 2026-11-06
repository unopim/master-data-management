<?php

namespace Webkul\MasterDataManagement\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Webkul\MasterDataManagement\Models\Hierarchy;
use Webkul\MasterDataManagement\Repositories\HierarchyRepository;
use Webkul\MasterDataManagement\Services\HierarchyService;

/**
 * Fills a newly created primary hierarchy from its entity type's records, in batches of `masterdata.batch_size`.
 */
class BackfillPrimaryHierarchy implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $hierarchyId) {}

    /**
     * Copy every placed record of the hierarchy's entity type into its nodes.
     */
    public function handle(HierarchyRepository $hierarchyRepository, HierarchyService $hierarchyService): void
    {
        $hierarchy = $hierarchyRepository->find($this->hierarchyId);

        if ($hierarchy instanceof Hierarchy && $hierarchy->is_primary) {
            $hierarchyService->backfillPrimary($hierarchy);
        }
    }
}
