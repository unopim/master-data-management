<?php

namespace Webkul\MasterDataManagement\Services;

use Illuminate\Support\Facades\DB;
use Webkul\MasterDataManagement\Jobs\SendRecordWebhookBatch;

/**
 * Collects the webhook deliveries of a bulk import while it is open, then queues them grouped by event in chunks, so
 * a large file sends one request per webhook for each chunk instead of one per row.
 *
 * Bound scoped: one buffer per request or queued job. Opening nests; the outermost flush queues the deliveries.
 */
class RecordWebhookBuffer
{
    public const int CHUNK_SIZE = 100;

    protected int $depth = 0;

    /**
     * @var array<string, list<array{record_id: int, changes: array<string, mixed>, context: array<string, mixed>}>>
     */
    protected array $items = [];

    /**
     * Start collecting.
     */
    public function open(): void
    {
        $this->depth++;
    }

    /**
     * Whether deliveries are being collected.
     */
    public function isOpen(): bool
    {
        return $this->depth > 0;
    }

    /**
     * Collect one delivery.
     *
     * @param  array<string, mixed>  $changes
     * @param  array<string, mixed>  $context
     */
    public function push(string $event, int $recordId, array $changes, array $context): void
    {
        $this->items[$event][] = ['record_id' => $recordId, 'changes' => $changes, 'context' => $context];
    }

    /**
     * Stop collecting once the surrounding transaction ends and, after the outermost collection, queue what was
     * collected.
     *
     * The record events are dispatched after commit, and an import batch runs inside a transaction, so collection
     * stays open until that commit (or rollback) has delivered them; without a transaction it ends at once.
     */
    public function flush(): void
    {
        $released = false;

        $release = function () use (&$released): void {
            if ($released) {
                return;
            }

            $released = true;

            $this->release();
        };

        DB::afterRollBack($release);

        DB::afterCommit($release);
    }

    protected function release(): void
    {
        $this->depth = max(0, $this->depth - 1);

        if ($this->depth > 0) {
            return;
        }

        foreach ($this->items as $event => $items) {
            foreach (array_chunk($items, self::CHUNK_SIZE) as $chunk) {
                dispatch(new SendRecordWebhookBatch($event, $chunk))->onQueue('webhooks');
            }
        }

        $this->items = [];
    }
}
