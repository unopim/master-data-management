<?php

namespace Webkul\MasterDataManagement\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Webkul\Core\Eloquent\Repository;
use Webkul\MasterDataManagement\Contracts\Xref;
use Webkul\MasterDataManagement\Enums\XrefStatus;
use Webkul\MasterDataManagement\Models\Xref as XrefModel;

class XrefRepository extends Repository
{
    /**
     * Get model class name.
     */
    public function model(): string
    {
        return Xref::class;
    }

    /**
     * Find the crosswalk entry for an external id, whatever its status.
     */
    public function findByExternalId(int $sourceSystemId, int $entityTypeId, string $externalId): ?XrefModel
    {
        return $this->model->newQuery()
            ->where('source_system_id', $sourceSystemId)
            ->where('entity_type_id', $entityTypeId)
            ->where('external_id', $externalId)
            ->first();
    }

    /**
     * Find the crosswalk entries of several external ids of one source system and entity type, whatever their status.
     *
     * @param  list<string>  $externalIds
     * @return Collection<string, XrefModel> Keyed by external id.
     */
    public function findManyByExternalIds(int $sourceSystemId, int $entityTypeId, array $externalIds): Collection
    {
        if ($externalIds === []) {
            return new Collection;
        }

        return $this->model->newQuery()
            ->where('source_system_id', $sourceSystemId)
            ->where('entity_type_id', $entityTypeId)
            ->whereIn('external_id', array_values(array_unique($externalIds)))
            ->get()
            ->keyBy('external_id');
    }

    /**
     * List the active external ids of a record as rows of source system code and external id.
     *
     * @return array<int, object{code: string, external_id: string}>
     */
    public function activeIdsForRecord(int $recordId): array
    {
        return DB::table('mdm_xrefs')
            ->join('mdm_source_systems', 'mdm_source_systems.id', '=', 'mdm_xrefs.source_system_id')
            ->where('mdm_xrefs.record_id', $recordId)
            ->where('mdm_xrefs.status', XrefStatus::Active->value)
            ->orderBy('mdm_source_systems.code')
            ->orderBy('mdm_xrefs.external_id')
            ->get(['mdm_source_systems.code', 'mdm_xrefs.external_id'])
            ->all();
    }

    /**
     * List every crosswalk entry of a record with its source system, whatever the status.
     *
     * @return array<int, object{id: int, code: string, name: string, type: string, external_id: string, status: string, first_seen_at: ?string, last_seen_at: ?string}>
     */
    public function entriesForRecord(int $recordId): array
    {
        return DB::table('mdm_xrefs')
            ->join('mdm_source_systems', 'mdm_source_systems.id', '=', 'mdm_xrefs.source_system_id')
            ->where('mdm_xrefs.record_id', $recordId)
            ->orderBy('mdm_source_systems.code')
            ->orderBy('mdm_xrefs.external_id')
            ->get([
                'mdm_xrefs.id',
                'mdm_source_systems.code',
                'mdm_source_systems.name',
                'mdm_source_systems.type',
                'mdm_xrefs.external_id',
                'mdm_xrefs.status',
                'mdm_xrefs.first_seen_at',
                'mdm_xrefs.last_seen_at',
            ])
            ->all();
    }

    /**
     * Find an active crosswalk entry of the record by its id.
     */
    public function findActiveForRecord(int $recordId, int $xrefId): ?XrefModel
    {
        return $this->model->newQuery()
            ->whereKey($xrefId)
            ->where('record_id', $recordId)
            ->where('status', XrefStatus::Active->value)
            ->first();
    }

    /**
     * The active external ids of each record grouped by source system code, in one query.
     *
     * @param  list<int>  $recordIds
     * @return array<int, array<string, list<string>>> External ids by system code, keyed by record id.
     */
    public function activeIdsByRecords(array $recordIds): array
    {
        if ($recordIds === []) {
            return [];
        }

        $ids = [];

        DB::table('mdm_xrefs')
            ->join('mdm_source_systems', 'mdm_source_systems.id', '=', 'mdm_xrefs.source_system_id')
            ->whereIn('mdm_xrefs.record_id', $recordIds)
            ->where('mdm_xrefs.status', XrefStatus::Active->value)
            ->orderBy('mdm_xrefs.record_id')
            ->orderBy('mdm_source_systems.code')
            ->orderBy('mdm_xrefs.external_id')
            ->get(['mdm_xrefs.record_id', 'mdm_source_systems.code', 'mdm_xrefs.external_id'])
            ->each(function (object $row) use (&$ids): void {
                $ids[(int) $row->record_id][(string) $row->code][] = (string) $row->external_id;
            });

        return $ids;
    }

    /**
     * The source system ids each record has an active external id in, in one query.
     *
     * @param  list<int>  $recordIds
     * @return array<int, list<int>> System ids keyed by record id.
     */
    public function activeSystemIdsByRecord(array $recordIds): array
    {
        if ($recordIds === []) {
            return [];
        }

        $linked = [];

        DB::table('mdm_xrefs')
            ->whereIn('record_id', $recordIds)
            ->where('status', XrefStatus::Active->value)
            ->distinct()
            ->get(['record_id', 'source_system_id'])
            ->each(function (object $row) use (&$linked): void {
                $linked[(int) $row->record_id][] = (int) $row->source_system_id;
            });

        return $linked;
    }

    /**
     * Move the record's active external ids to another record; returns the moved ids.
     *
     * @return list<int>
     */
    public function moveActive(int $fromRecordId, int $toRecordId): array
    {
        $ids = $this->model->newQuery()
            ->where('record_id', $fromRecordId)
            ->where('status', XrefStatus::Active->value)
            ->pluck('id')
            ->map(intval(...))
            ->all();

        foreach (array_chunk($ids, 500) as $chunk) {
            $this->model->newQuery()->whereKey($chunk)->update(['record_id' => $toRecordId, 'last_pushed_hash' => null]);
        }

        return $ids;
    }

    /**
     * Move the given external ids back to their original record while they are still active on the current one.
     *
     * @param  list<int>  $ids
     */
    public function moveBack(array $ids, int $currentRecordId, int $originalRecordId): void
    {
        foreach (array_chunk($ids, 500) as $chunk) {
            $this->model->newQuery()
                ->whereKey($chunk)
                ->where('record_id', $currentRecordId)
                ->where('status', XrefStatus::Active->value)
                ->update(['record_id' => $originalRecordId, 'last_pushed_hash' => null]);
        }
    }
}
