<?php

namespace Webkul\MasterDataManagement\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Webkul\MasterDataManagement\Models\EntityType;
use Webkul\MasterDataManagement\Models\Record;
use Webkul\MasterDataManagement\Quality\QualityEvaluator;
use Webkul\MasterDataManagement\Repositories\RecordRepository;

/**
 * Checks one written record for likely duplicates with the type's match rules and syncs its duplicate issues.
 *
 * Does nothing once the record is gone or its type no longer matches on write.
 */
class MatchRecordOnWrite implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 300;

    public function __construct(public int $recordId) {}

    public function handle(RecordRepository $recordRepository, QualityEvaluator $qualityEvaluator): void
    {
        $record = $recordRepository->forQualityScan([$this->recordId])->first();

        if (! $record instanceof Record || ! $record->entityType instanceof EntityType || ! $record->entityType->matchesOnWrite()) {
            return;
        }

        $qualityEvaluator->evaluateRecord($record, $qualityEvaluator->comparingRules());
    }
}
