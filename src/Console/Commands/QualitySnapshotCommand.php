<?php

namespace Webkul\MasterDataManagement\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Date;
use Webkul\MasterDataManagement\Jobs\CaptureQualitySnapshots;
use Webkul\MasterDataManagement\Quality\QualitySnapshotCalculator;

#[Description('Store today\'s data quality snapshot of every active master data entity type')]
#[Signature('masterdata:quality:snapshot
        {--now : Capture in this process instead of queueing the capture}')]
class QualitySnapshotCommand extends Command
{
    /**
     * Queue, or run, the capture of today's snapshots.
     */
    public function handle(QualitySnapshotCalculator $qualitySnapshotCalculator): int
    {
        if ($this->option('now')) {
            $captured = $qualitySnapshotCalculator->capture(Date::now());

            $this->components->info(trans('masterdata::app.quality-dashboard.snapshot.done', ['count' => $captured]));

            return self::SUCCESS;
        }

        dispatch(new CaptureQualitySnapshots);

        $this->components->info(trans('masterdata::app.quality-dashboard.snapshot.queued'));

        return self::SUCCESS;
    }
}
