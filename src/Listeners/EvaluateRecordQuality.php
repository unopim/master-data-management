<?php

namespace Webkul\MasterDataManagement\Listeners;

use Webkul\MasterDataManagement\Events\RecordCreated;
use Webkul\MasterDataManagement\Events\RecordRetired;
use Webkul\MasterDataManagement\Events\RecordUpdated;
use Webkul\MasterDataManagement\Events\RecordXrefsChanged;
use Webkul\MasterDataManagement\Quality\QualityEvaluator;

/**
 * Re-checks a record's quality issues right after a committed write, so a fix closes its issue at once.
 *
 * Rules that compare the record with other records run on the queue through QueueRecordMatch instead; a retired
 * record is checked by every rule, which closes all its issues. Issues are derived data: a failing check is reported
 * and never fails the write that already committed.
 */
class EvaluateRecordQuality
{
    public function __construct(protected QualityEvaluator $qualityEvaluator) {}

    public function handle(RecordCreated|RecordUpdated|RecordRetired|RecordXrefsChanged $event): void
    {
        rescue(fn () => $this->qualityEvaluator->evaluateRecord(
            $event->record,
            $event instanceof RecordRetired ? null : $this->qualityEvaluator->recordRules(),
        ));
    }
}
