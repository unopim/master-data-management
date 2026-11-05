<?php

namespace Webkul\MasterDataManagement\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Webkul\Core\Eloquent\Repository;
use Webkul\MasterDataManagement\Contracts\RecordMerge;
use Webkul\MasterDataManagement\Models\RecordMerge as RecordMergeModel;

class RecordMergeRepository extends Repository
{
    /**
     * Get model class name.
     */
    public function model(): string
    {
        return RecordMerge::class;
    }

    /**
     * The standing merge that folded the record into its survivor, locked when asked.
     */
    public function activeForMerged(int $mergedId, bool $lock = false): ?RecordMergeModel
    {
        return $this->model->newQuery()
            ->where('merged_id', $mergedId)
            ->whereNull('unmerged_at')
            ->when($lock, fn ($query) => $query->lockForUpdate())
            ->latest('id')
            ->first();
    }

    /**
     * The standing merges into the survivor, newest first.
     *
     * @return Collection<int, RecordMergeModel>
     */
    public function activeForSurvivor(int $survivorId, int $limit = 50): Collection
    {
        return $this->model->newQuery()
            ->with('merged:id,uuid,code,label')
            ->where('survivor_id', $survivorId)
            ->whereNull('unmerged_at')
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    /**
     * Whether a later standing merge into the survivor exists, which an unmerge must not undo out of order.
     */
    public function hasLaterActive(int $survivorId, int $afterId): bool
    {
        return $this->model->newQuery()
            ->where('survivor_id', $survivorId)
            ->whereNull('unmerged_at')
            ->where('id', '>', $afterId)
            ->exists();
    }

    /**
     * Clear the value snapshots of the merges the records took part in, which can then no longer be undone.
     *
     * @param  list<int>  $recordIds
     */
    public function eraseForRecords(array $recordIds): void
    {
        $this->model->newQuery()
            ->whereIn('survivor_id', $recordIds)
            ->orWhereIn('merged_id', $recordIds)
            ->toBase()
            ->update(['survivor_before' => '[]', 'merged_before' => '[]', 'result' => '[]']);
    }
}
