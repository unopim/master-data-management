<?php

namespace Webkul\MasterDataManagement\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Webkul\MasterDataManagement\Services\HierarchyService;

/**
 * Rewrites the paths and depths below a moved record, in batches of `masterdata.batch_size`.
 */
class RewriteDescendantPaths implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $recordId,
        public string $oldPath,
        public string $newPath,
        public int $depthDelta,
    ) {}

    /**
     * Move every descendant from the old path prefix to the new one.
     */
    public function handle(HierarchyService $hierarchyService): void
    {
        $hierarchyService->rewriteDescendantPaths($this->recordId, $this->oldPath, $this->newPath, $this->depthDelta);
    }
}
