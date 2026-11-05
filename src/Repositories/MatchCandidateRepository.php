<?php

namespace Webkul\MasterDataManagement\Repositories;

use Webkul\Core\Eloquent\Repository;
use Webkul\MasterDataManagement\Contracts\MatchCandidate;
use Webkul\MasterDataManagement\Models\MatchCandidate as MatchCandidateModel;

class MatchCandidateRepository extends Repository
{
    /**
     * Get model class name.
     */
    public function model(): string
    {
        return MatchCandidate::class;
    }

    /**
     * Find the inbox row of an external id, whatever its status.
     */
    public function findByExternalId(int $sourceSystemId, int $entityTypeId, string $externalId): ?MatchCandidateModel
    {
        return $this->model->newQuery()
            ->where('source_system_id', $sourceSystemId)
            ->where('entity_type_id', $entityTypeId)
            ->where('external_id', $externalId)
            ->first();
    }

    /**
     * Find an inbox row under a row lock; call it inside a transaction.
     */
    public function findForUpdate(int $id): ?MatchCandidateModel
    {
        return $this->model->newQuery()->lockForUpdate()->find($id);
    }

    /**
     * Clear the label and source payload of the candidates matched to the records.
     *
     * @param  list<int>  $recordIds
     */
    public function eraseForRecords(array $recordIds): void
    {
        $this->model->newQuery()
            ->whereIn('matched_record_id', $recordIds)
            ->toBase()
            ->update(['label' => null, 'payload' => null]);
    }
}
