<?php

namespace Webkul\MasterDataManagement\Listeners;

use Webkul\MasterDataManagement\Events\SchemaPublished;
use Webkul\MasterDataManagement\Jobs\RunQualityScan;

/**
 * Queues a quality scan of the entity type once a new schema version is published, so records are checked
 * against the rules they now have to meet.
 */
class QueueQualityScan
{
    public function handle(SchemaPublished $event): void
    {
        dispatch(new RunQualityScan((int) $event->entityType->id));
    }
}
