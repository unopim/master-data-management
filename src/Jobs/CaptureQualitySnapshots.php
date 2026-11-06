<?php

namespace Webkul\MasterDataManagement\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Date;
use Webkul\MasterDataManagement\Quality\QualitySnapshotCalculator;

/**
 * Stores the day's data quality snapshot of every active entity type.
 */
class CaptureQualitySnapshots implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 1800;

    /**
     * Run one capture at a time; a capture queued meanwhile waits and then overwrites the same day.
     *
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [new WithoutOverlapping('masterdata-quality-snapshots')->releaseAfter(60)->expireAfter(3600)];
    }

    public function handle(QualitySnapshotCalculator $qualitySnapshotCalculator): void
    {
        $qualitySnapshotCalculator->capture(Date::now());
    }
}
