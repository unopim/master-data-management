<?php

namespace Webkul\MasterDataManagement\Services;

use Illuminate\Support\Facades\DB;
use Webkul\MasterDataManagement\Jobs\ReindexField;
use Webkul\MasterDataManagement\Models\EntityType;
use Webkul\MasterDataManagement\Models\Field;
use Webkul\MasterDataManagement\Repositories\FieldRepository;
use Webkul\MasterDataManagement\Repositories\RecordRepository;

/**
 * Creates, changes and removes entity type fields, keeping the record search index in step.
 */
class FieldService
{
    public function __construct(
        protected FieldRepository $fieldRepository,
        protected RecordRepository $recordRepository,
        protected RecordIndexer $recordIndexer,
    ) {}

    /**
     * Create a field on the entity type. A new field has no values yet, so the index needs no work.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(EntityType $type, array $data): Field
    {
        $field = $this->fieldRepository->create(['entity_type_id' => $type->id] + $data);

        $this->fieldRepository->forgetActive($type->id);

        return $field;
    }

    /**
     * Update the field. Leaving the index drops its index rows; joining it queues a reindex of the type's records.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Field $field, array $data): Field
    {
        $wasIndexed = $this->isIndexed($field);

        return DB::transaction(function () use ($field, $data, $wasIndexed): Field {
            $field = $this->fieldRepository->update($data, $field->id);

            $this->fieldRepository->forgetActive($field->entity_type_id);

            $isIndexed = $this->isIndexed($field);

            if ($wasIndexed && ! $isIndexed) {
                $this->recordIndexer->forgetField($field->entity_type_id, $field->code);
            } elseif (! $wasIndexed && $isIndexed) {
                ReindexField::dispatch($field->id)->afterCommit();
            }

            return $field;
        });
    }

    /**
     * Remove the field: archive it while its entity type has records, so no value is lost, otherwise delete it.
     *
     * @return bool Whether the field was archived rather than deleted.
     */
    public function delete(Field $field): bool
    {
        if (! $this->recordRepository->existsForEntityType($field->entity_type_id)) {
            $this->fieldRepository->delete($field->id);

            $this->fieldRepository->forgetActive($field->entity_type_id);

            return false;
        }

        $this->update($field, ['is_archived' => true]);

        return true;
    }

    /**
     * Whether the record index holds the field's values: the indexer covers active searchable or unique fields.
     */
    protected function isIndexed(Field $field): bool
    {
        return ! $field->is_archived && ($field->is_searchable || $field->is_unique);
    }
}
