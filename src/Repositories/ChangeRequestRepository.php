<?php

namespace Webkul\MasterDataManagement\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Webkul\Core\Eloquent\Repository;
use Webkul\MasterDataManagement\Contracts\ChangeRequest;
use Webkul\MasterDataManagement\Enums\ChangeRequestStatus;
use Webkul\MasterDataManagement\Models\ChangeRequest as ChangeRequestModel;

class ChangeRequestRepository extends Repository
{
    /**
     * Get model class name.
     */
    public function model(): string
    {
        return ChangeRequest::class;
    }

    /**
     * Find a change request under a row lock; call it inside a transaction.
     */
    public function findForUpdate(int $id): ?ChangeRequestModel
    {
        return $this->model->newQuery()->lockForUpdate()->find($id);
    }

    /**
     * The latest change requests of a record, newest first, with the admins who asked and decided.
     *
     * @return Collection<int, ChangeRequestModel>
     */
    public function latestForRecord(int $recordId, int $limit): Collection
    {
        return $this->model->newQuery()
            ->with(['requester:id,name', 'decider:id,name'])
            ->where('record_id', $recordId)
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

    /**
     * The oldest pending change request of a record, if any.
     */
    public function firstPendingForRecord(int $recordId): ?ChangeRequestModel
    {
        return $this->model->newQuery()
            ->where('record_id', $recordId)
            ->where('status', ChangeRequestStatus::Pending->value)
            ->orderBy('id')
            ->first();
    }

    /**
     * Count the pending change requests of a record.
     */
    public function pendingCountForRecord(int $recordId): int
    {
        return $this->model->newQuery()
            ->where('record_id', $recordId)
            ->where('status', ChangeRequestStatus::Pending->value)
            ->count();
    }

    /**
     * Clear the record snapshots, input and comments of every change request on the records.
     *
     * @param  list<int>  $recordIds
     */
    public function eraseForRecords(array $recordIds): void
    {
        $this->model->newQuery()
            ->whereIn('record_id', $recordIds)
            ->toBase()
            ->update(['before' => '[]', 'after' => '[]', 'input' => '[]', 'comment' => null]);
    }
}
