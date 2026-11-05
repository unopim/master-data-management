<?php

namespace Webkul\MasterDataManagement\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Webkul\MasterDataManagement\Models\Field;
use Webkul\MasterDataManagement\Repositories\FieldRepository;
use Webkul\MasterDataManagement\Repositories\RecordRepository;
use Webkul\MasterDataManagement\Services\RecordIndexer;

/**
 * Rebuilds the search index of every record of a field's entity type, after the field's indexing changed.
 */
class ReindexField implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $fieldId) {}

    /**
     * Re-sync the records in batches, each under a row lock so a concurrent write cannot leave a stale index.
     */
    public function handle(FieldRepository $fieldRepository, RecordRepository $recordRepository, RecordIndexer $recordIndexer): void
    {
        $field = $fieldRepository->find($this->fieldId);

        if (! $field instanceof Field) {
            return;
        }

        $recordRepository->chunkIdsForEntityType(
            $field->entity_type_id,
            $recordIndexer->batchSize(),
            function (array $ids) use ($recordRepository, $recordIndexer): void {
                DB::transaction(fn () => $recordIndexer->syncMany($recordRepository->lockForIndexing($ids)));
            },
        );
    }
}
