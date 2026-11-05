<?php

namespace Webkul\MasterDataManagement\Listeners;

use Webkul\MasterDataManagement\Events\RecordCreated;
use Webkul\MasterDataManagement\Events\RecordUpdated;
use Webkul\MasterDataManagement\Jobs\MatchRecordOnWrite;

/**
 * Queues the duplicate check of a written record once the write commits, when its type matches on write.
 *
 * A bulk import's writes are left out, so a large file does not queue one job per row; the import is checked by the
 * quality scan (`masterdata:quality:scan`) instead.
 */
class QueueRecordMatch
{
    public function handle(RecordCreated|RecordUpdated $event): void
    {
        if ($event->changeSet->source?->isImport() !== true && $event->record->entityType?->matchesOnWrite()) {
            dispatch(new MatchRecordOnWrite((int) $event->record->id))->afterCommit();
        }
    }
}
