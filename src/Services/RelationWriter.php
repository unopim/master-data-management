<?php

namespace Webkul\MasterDataManagement\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Webkul\MasterDataManagement\Enums\RelationCardinality;
use Webkul\MasterDataManagement\Models\Field;
use Webkul\MasterDataManagement\Models\Record;
use Webkul\MasterDataManagement\Models\Relation;
use Webkul\MasterDataManagement\Models\RelationType;
use Webkul\MasterDataManagement\Repositories\FieldRepository;
use Webkul\MasterDataManagement\Repositories\RecordRepository;
use Webkul\MasterDataManagement\Repositories\RelationRepository;
use Webkul\MasterDataManagement\Repositories\RelationTypeRepository;

/**
 * Writes relations: the reference field values the record writer hands over, and links of the non-system types.
 */
class RelationWriter
{
    public function __construct(
        protected RelationRepository $relationRepository,
        protected RelationTypeRepository $relationTypeRepository,
        protected RecordRepository $recordRepository,
        protected FieldRepository $fieldRepository,
    ) {}

    /**
     * Replace the record's relations of each given reference field with rows for the given uuids.
     *
     * Runs inside the record writer's transaction. Fields left out keep their relations; an empty list clears one.
     * Rows to unchanged targets are kept, so their ids and attributes survive a save.
     *
     * @param  array<string, list<string>>  $referencesByFieldCode
     */
    public function syncReferences(Record $from, array $referencesByFieldCode): void
    {
        if ($referencesByFieldCode === []) {
            return;
        }

        $fields = $this->fieldRepository->activeFor((int) $from->entity_type_id)
            ->filter(fn (Field $field): bool => $field->type === 'reference' && array_key_exists($field->code, $referencesByFieldCode))
            ->keyBy('id');

        if ($fields->isEmpty()) {
            return;
        }

        $types = $this->relationTypeRepository->systemTypesForFields($fields->keys()->all());

        foreach ($fields as $field) {
            if (! $types->has($field->id)) {
                $types->put($field->id, $this->relationTypeRepository->systemTypeForField($field));
            }
        }

        $ids = $this->recordRepository->idsByUuids(array_values(array_unique(array_merge(...array_values($referencesByFieldCode)))));

        $existing = $this->relationRepository->targetsByType((int) $from->id, $types->pluck('id')->map(intval(...))->all());

        $stale = [];

        $rows = [];

        foreach ($fields as $field) {
            $typeId = (int) $types->get($field->id)->id;

            $wanted = array_values(array_unique(array_filter(array_map(
                fn (string $uuid): ?int => $ids[$uuid] ?? null,
                $referencesByFieldCode[$field->code],
            ))));

            $current = $existing[$typeId] ?? [];

            array_push($stale, ...array_keys(array_diff($current, $wanted)));

            foreach (array_diff($wanted, $current) as $toRecordId) {
                $rows[] = ['relation_type_id' => $typeId, 'from_record_id' => (int) $from->id, 'to_record_id' => $toRecordId];
            }
        }

        $this->relationRepository->deleteIds($stale);

        $this->relationRepository->insertMany($rows);
    }

    /**
     * Link two records through a non-system relation type; a `one` type replaces the record's current link.
     *
     * @param  array<string, mixed>  $attributes
     *
     * @throws ValidationException
     */
    public function link(RelationType $type, Record $from, Record $to, array $attributes = []): Relation
    {
        if ($type->is_system) {
            throw ValidationException::withMessages(['relation_type' => trans('masterdata::app.relation-types.validation.system-type')]);
        }

        if ((int) $from->entity_type_id !== $type->from_entity_type_id || (int) $to->entity_type_id !== $type->to_entity_type_id) {
            throw ValidationException::withMessages(['relation_type' => trans('masterdata::app.relation-types.validation.type-mismatch')]);
        }

        return DB::transaction(function () use ($type, $from, $to, $attributes): Relation {
            if ($type->cardinality === RelationCardinality::One) {
                $this->relationRepository->deleteFrom((int) $from->id, (int) $type->id);
            }

            return $this->relationRepository->create([
                'relation_type_id' => $type->id,
                'from_record_id'   => $from->id,
                'to_record_id'     => $to->id,
                'attributes'       => $attributes === [] ? null : $attributes,
            ]);
        });
    }

    /**
     * Remove a link of a non-system relation type.
     *
     * @throws ValidationException
     */
    public function unlink(Relation $relation): void
    {
        if ($relation->relationType?->is_system) {
            throw ValidationException::withMessages(['relation_type' => trans('masterdata::app.relation-types.validation.system-type')]);
        }

        $this->relationRepository->delete($relation->id);
    }
}
