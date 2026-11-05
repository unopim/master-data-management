<?php

namespace Webkul\MasterDataManagement\Repositories;

use DateTimeInterface;
use Illuminate\Database\UniqueConstraintViolationException;
use Webkul\Core\Eloquent\Repository;
use Webkul\MasterDataManagement\Contracts\IdempotencyKey;
use Webkul\MasterDataManagement\Models\IdempotencyKey as IdempotencyKeyModel;

class IdempotencyKeyRepository extends Repository
{
    /**
     * Get model class name.
     */
    public function model(): string
    {
        return IdempotencyKey::class;
    }

    /**
     * Find the stored response of the client's key.
     */
    public function findForClient(string $clientId, string $key): ?IdempotencyKeyModel
    {
        return $this->model->newQuery()
            ->where('api_client_id', $clientId)
            ->where('key', $key)
            ->first();
    }

    /**
     * Store the response of the client's key; a row stored meanwhile for the same key wins.
     *
     * @param  array{body: string, headers: array<string, string>}  $response
     */
    public function remember(string $clientId, string $key, string $requestHash, int $statusCode, array $response): void
    {
        try {
            $this->model->newQuery()->create([
                'api_client_id' => $clientId,
                'key'           => $key,
                'request_hash'  => $requestHash,
                'status_code'   => $statusCode,
                'response'      => $response,
            ]);
        } catch (UniqueConstraintViolationException) {
        }
    }

    /**
     * Delete the keys stored before the given time in batches; returns how many were deleted.
     */
    public function pruneBefore(DateTimeInterface $before, int $batchSize): int
    {
        $deleted = 0;

        $this->model->newQuery()
            ->where('created_at', '<', $before)
            ->select('id')
            ->chunkById($batchSize, function ($rows) use (&$deleted): void {
                $deleted += $this->model->newQuery()->whereKey($rows->modelKeys())->delete();
            });

        return $deleted;
    }
}
