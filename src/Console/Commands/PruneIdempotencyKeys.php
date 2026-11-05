<?php

namespace Webkul\MasterDataManagement\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Webkul\MasterDataManagement\Repositories\IdempotencyKeyRepository;

#[Description('Delete REST idempotency keys older than a day')]
#[Signature('masterdata:idempotency:prune')]
class PruneIdempotencyKeys extends Command
{
    /**
     * Delete the expired keys in batches.
     */
    public function handle(IdempotencyKeyRepository $idempotencyKeyRepository): int
    {
        $deleted = $idempotencyKeyRepository->pruneBefore(now()->subDay(), (int) config('masterdata.batch_size', 500));

        $this->components->info(trans('masterdata::app.api.idempotency-pruned', ['count' => $deleted]));

        return self::SUCCESS;
    }
}
