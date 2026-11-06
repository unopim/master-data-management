<?php

namespace Webkul\MasterDataManagement\Repositories;

use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as BaseCollection;
use Illuminate\Support\Facades\DB;
use Webkul\Core\Eloquent\Repository;
use Webkul\MasterDataManagement\Contracts\Relation;
use Webkul\MasterDataManagement\Enums\RelationCardinality;
use Webkul\MasterDataManagement\Models\Record;
use Webkul\MasterDataManagement\Models\Relation as RelationModel;
use Webkul\MasterDataManagement\Models\RelationTypeProxy;
use Webkul\User\Models\Admin;

class RelationRepository extends Repository
{
    /**
     * The columns of the other record a relation listing loads.
     */
    protected const array RECORD_COLUMNS = ['id', 'uuid', 'code', 'label', 'entity_type_id'];

    public function __construct(
        protected RecordRepository $recordRepository,
        Container $container,
    ) {
        parent::__construct($container);
    }

    /**
     * Get model class name.
     */
    public function model(): string
    {
        return Relation::class;
    }

    /**
     * The relations the record points out with, with their type and target record, in creation order.
     *
     * @return Collection<int, RelationModel>
     */
    public function outbound(Record $record, int $limit = 500): Collection
    {
        return $this->model->newQuery()
            ->where('from_record_id', $record->id)
            ->with(['relationType.translations', 'toRecord' => fn ($query) => $query->select(self::RECORD_COLUMNS)])
            ->orderBy('id')
            ->limit($limit)
            ->get();
    }

    /**
     * The relations other records point at the record with, with their type and source record, in creation order.
     *
     * @return Collection<int, RelationModel>
     */
    public function inbound(Record $record, int $limit = 500): Collection
    {
        return $this->model->newQuery()
            ->where('to_record_id', $record->id)
            ->with(['relationType.translations', 'fromRecord' => fn ($query) => $query->select(self::RECORD_COLUMNS)])
            ->orderBy('id')
            ->limit($limit)
            ->get();
    }

    /**
     * The relations starting from or pointing at any of the records, as bare rows in creation order, up to the limit.
     *
     * Unscoped: the record graph keeps only the ends the admin may see.
     *
     * @param  list<int>  $recordIds
     * @return list<object{from_record_id: int, to_record_id: int, relation_type_id: int}>
     */
    public function edgesTouching(array $recordIds, int $limit): array
    {
        if ($recordIds === []) {
            return [];
        }

        return $this->model->newQuery()
            ->where(fn (Builder $query) => $query->whereIn('from_record_id', $recordIds)->orWhereIn('to_record_id', $recordIds))
            ->orderBy('id')
            ->limit($limit)
            ->toBase()
            ->get(['from_record_id', 'to_record_id', 'relation_type_id'])
            ->all();
    }

    /**
     * The reference field values of the records: record id, then field code, then the target uuids in link order.
     *
     * A target merged since it was linked reads as its surviving record (one hop). One query for all the records.
     * Unscoped: the record writer compares and keeps every stored target; screens and the API read
     * `visibleReferenceUuids()` or `referenceValues()` instead.
     *
     * @param  list<int>  $recordIds
     * @return array<int, array<string, list<string>>>
     */
    public function referenceUuids(array $recordIds): array
    {
        if ($recordIds === []) {
            return [];
        }

        $references = [];

        foreach ($this->referenceRows($recordIds) as $row) {
            $references[(int) $row->from_record_id][(string) $row->field_code][] = (string) ($row->survivor_uuid ?? $row->target_uuid);
        }

        return $references;
    }

    /**
     * The reference field values of the records the admin may see, limited to the targets the admin may see.
     *
     * @param  list<int>  $recordIds
     * @return array<int, array<string, list<string>>>
     */
    public function visibleReferenceUuids(array $recordIds, ?Admin $admin = null): array
    {
        $references = $this->referenceUuids($recordIds);

        $visible = array_flip($this->recordRepository->visibleUuids($this->flattenUuids($references), $admin));

        foreach ($references as $recordId => $fields) {
            foreach ($fields as $code => $uuids) {
                $uuids = array_values(array_filter($uuids, fn (string $uuid): bool => isset($visible[$uuid])));

                if ($uuids === []) {
                    unset($references[$recordId][$code]);
                } else {
                    $references[$recordId][$code] = $uuids;
                }
            }
        }

        return $references;
    }

    /**
     * The record's reference field values in the writer's input shape, a uuid for a `one` field and a list for
     * `many`, limited to the targets the admin may see.
     *
     * @return array<string, string|list<string>>
     */
    public function referenceValues(Record $record, ?Admin $admin = null): array
    {
        return $this->referenceValuesFor([(int) $record->id], $admin)[(int) $record->id] ?? [];
    }

    /**
     * The reference field values of each record in the writer's input shape, as `referenceValues()` reads them for
     * one record, in one query for all the records.
     *
     * @param  list<int>  $recordIds
     * @return array<int, array<string, string|list<string>>>
     */
    public function referenceValuesFor(array $recordIds, ?Admin $admin = null): array
    {
        if ($recordIds === []) {
            return [];
        }

        $rows = $this->referenceRows($recordIds);

        $visible = array_flip($this->recordRepository->visibleUuids(
            array_values(array_unique($rows->map(fn (object $row): string => (string) ($row->survivor_uuid ?? $row->target_uuid))->all())),
            $admin,
        ));

        $values = [];

        foreach ($rows as $row) {
            $uuid = (string) ($row->survivor_uuid ?? $row->target_uuid);

            if (! isset($visible[$uuid])) {
                continue;
            }

            if ($row->cardinality === RelationCardinality::Many->value) {
                $values[(int) $row->from_record_id][(string) $row->field_code][] = $uuid;
            } else {
                $values[(int) $row->from_record_id][(string) $row->field_code] = $uuid;
            }
        }

        return $values;
    }

    /**
     * The ids of the given reference fields whose system relation type holds at least one relation.
     *
     * @param  list<int>  $fieldIds
     * @return list<int>
     */
    public function fieldIdsWithRelations(array $fieldIds): array
    {
        if ($fieldIds === []) {
            return [];
        }

        return DB::table('mdm_relation_types')
            ->whereIn('mdm_relation_types.field_id', $fieldIds)
            ->where('mdm_relation_types.is_system', true)
            ->whereExists(fn ($query) => $query->select(DB::raw(1))
                ->from('mdm_relations')
                ->whereColumn('mdm_relations.relation_type_id', 'mdm_relation_types.id'))
            ->pluck('mdm_relation_types.field_id')
            ->map(fn (mixed $id): int => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Every uuid of a record id => field code => uuids map, once.
     *
     * @param  array<int, array<string, list<string>>>  $references
     * @return list<string>
     */
    protected function flattenUuids(array $references): array
    {
        $uuids = [];

        foreach ($references as $fields) {
            foreach ($fields as $list) {
                array_push($uuids, ...$list);
            }
        }

        return array_values(array_unique($uuids));
    }

    /**
     * The system relation rows of the records with their field code, cardinality and target uuids, in link order.
     *
     * @param  list<int>  $recordIds
     * @return BaseCollection<int, object>
     */
    protected function referenceRows(array $recordIds): BaseCollection
    {
        return DB::table('mdm_relations')
            ->join('mdm_relation_types', 'mdm_relation_types.id', '=', 'mdm_relations.relation_type_id')
            ->join('mdm_fields', 'mdm_fields.id', '=', 'mdm_relation_types.field_id')
            ->join('mdm_records as target', 'target.id', '=', 'mdm_relations.to_record_id')
            ->leftJoin('mdm_records as survivor', 'survivor.id', '=', 'target.merged_into_id')
            ->whereIn('mdm_relations.from_record_id', $recordIds)
            ->where('mdm_relation_types.is_system', true)
            ->orderBy('mdm_relations.id')
            ->get([
                'mdm_relations.from_record_id',
                'mdm_fields.code as field_code',
                'mdm_relation_types.cardinality',
                'target.uuid as target_uuid',
                'survivor.uuid as survivor_uuid',
            ]);
    }

    /**
     * The target record ids of the record's relations of each given type, keyed by type id then relation id.
     *
     * @param  list<int>  $relationTypeIds
     * @return array<int, array<int, int>>
     */
    public function targetsByType(int $fromRecordId, array $relationTypeIds): array
    {
        if ($relationTypeIds === []) {
            return [];
        }

        $targets = [];

        $rows = $this->model->newQuery()
            ->where('from_record_id', $fromRecordId)
            ->whereIn('relation_type_id', $relationTypeIds)
            ->orderBy('id')
            ->toBase()
            ->get(['id', 'relation_type_id', 'to_record_id']);

        foreach ($rows as $row) {
            $targets[(int) $row->relation_type_id][(int) $row->id] = (int) $row->to_record_id;
        }

        return $targets;
    }

    /**
     * Insert relation rows in one statement per chunk.
     *
     * @param  list<array<string, mixed>>  $rows
     */
    public function insertMany(array $rows): void
    {
        $now = now();

        foreach (array_chunk($rows, 500) as $chunk) {
            $this->model->newQuery()->insert(array_map(
                fn (array $row): array => $row + ['created_at' => $now, 'updated_at' => $now],
                $chunk,
            ));
        }
    }

    /**
     * Delete the relations with the given ids.
     *
     * @param  list<int>  $ids
     */
    public function deleteIds(array $ids): void
    {
        if ($ids !== []) {
            $this->model->newQuery()->whereKey($ids)->delete();
        }
    }

    /**
     * Delete the record's relations of the given type.
     */
    public function deleteFrom(int $fromRecordId, int $relationTypeId): void
    {
        $this->model->newQuery()
            ->where('from_record_id', $fromRecordId)
            ->where('relation_type_id', $relationTypeId)
            ->delete();
    }

    /**
     * Whether any relation of the type exists.
     */
    public function existsForType(int $relationTypeId): bool
    {
        return $this->model->newQuery()->where('relation_type_id', $relationTypeId)->exists();
    }

    /**
     * Whether a record other than the ignored one references any of the uuids through the reference field.
     *
     * @param  list<string>  $uuids
     */
    public function referenceTaken(int $fieldId, array $uuids, ?int $ignoreFromRecordId): bool
    {
        if ($uuids === []) {
            return false;
        }

        return $this->model->newQuery()
            ->whereHas('relationType', fn (Builder $query) => $query->where('is_system', true)->where('field_id', $fieldId))
            ->whereHas('toRecord', fn (Builder $query) => $query->whereIn('uuid', $uuids))
            ->when($ignoreFromRecordId !== null, fn (Builder $query) => $query->where('from_record_id', '!=', $ignoreFromRecordId))
            ->exists();
    }

    /**
     * Point the relations that end at one record at another, skipping rows that would link the new target to itself
     * or repeat a relation it already has; returns the moved ids.
     *
     * @return list<int>
     */
    public function repointInbound(int $fromTargetId, int $toTargetId): array
    {
        return $this->repoint('to_record_id', 'from_record_id', $fromTargetId, $toTargetId, false);
    }

    /**
     * Point the record's own relations of types not backed by a reference field at another record, with the same
     * skips as an inbound move; returns the moved ids.
     *
     * @return list<int>
     */
    public function repointOutbound(int $fromSourceId, int $toSourceId): array
    {
        return $this->repoint('from_record_id', 'to_record_id', $fromSourceId, $toSourceId, true);
    }

    /**
     * Point the given relations back from one record to another on the given end, leaving rows changed since.
     *
     * @param  list<int>  $ids
     */
    public function restoreEnd(array $ids, string $column, int $currentId, int $originalId): void
    {
        foreach (array_chunk($ids, 500) as $chunk) {
            $this->model->newQuery()
                ->whereKey($chunk)
                ->where($column, $currentId)
                ->update([$column => $originalId, 'updated_at' => now()]);
        }
    }

    /**
     * Move one end of a record's relations to another record in chunks.
     *
     * @return list<int>
     */
    protected function repoint(string $column, string $otherColumn, int $fromId, int $toId, bool $plainTypesOnly): array
    {
        $query = $this->model->newQuery()
            ->where($column, $fromId)
            ->where($otherColumn, '!=', $toId)
            ->when($plainTypesOnly, fn (Builder $query) => $query->whereIn(
                'relation_type_id',
                RelationTypeProxy::modelClass()::query()->whereNull('field_id')->select('id'),
            ));

        $moved = [];

        $query->select(['id', 'relation_type_id', $otherColumn])->chunkById(500, function (Collection $rows) use ($column, $otherColumn, $toId, &$moved): void {
            $taken = $this->model->newQuery()
                ->where($column, $toId)
                ->whereIn($otherColumn, $rows->pluck($otherColumn)->unique()->values()->all())
                ->get(['relation_type_id', $otherColumn])
                ->mapWithKeys(fn (RelationModel $row): array => [$row->relation_type_id.'|'.$row->{$otherColumn} => true]);

            $ids = $rows
                ->reject(fn (RelationModel $row): bool => $taken->has($row->relation_type_id.'|'.$row->{$otherColumn}))
                ->pluck('id')
                ->map(intval(...))
                ->values()
                ->all();

            if ($ids !== []) {
                $this->model->newQuery()->whereKey($ids)->update([$column => $toId, 'updated_at' => now()]);

                array_push($moved, ...$ids);
            }
        });

        return $moved;
    }
}
