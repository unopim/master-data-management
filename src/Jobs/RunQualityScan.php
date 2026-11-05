<?php

namespace Webkul\MasterDataManagement\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Webkul\MasterDataManagement\Quality\QualityScanner;

/**
 * Re-checks the completeness and quality issues of every record of an entity type.
 */
class RunQualityScan implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public int $maxExceptions = 1;

    public int $timeout = 1800;

    public function __construct(public int $entityTypeId) {}

    /**
     * Run one scan per entity type at a time; a scan queued meanwhile waits and runs after it.
     *
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [new WithoutOverlapping('masterdata-quality-scan-'.$this->entityTypeId)->releaseAfter(30)->expireAfter(3600)];
    }

    public function handle(QualityScanner $qualityScanner): void
    {
        $qualityScanner->scan($this->entityTypeId);
    }
}
