<?php

namespace Webkul\MasterDataManagement\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Webkul\MasterDataManagement\Enums\RecordWebhookEvent;
use Webkul\MasterDataManagement\Services\RecordWebhooks;

/**
 * Delivers a chunk of applied record changes of one event to every active webhook subscribed to it, one request per
 * webhook.
 */
class SendRecordWebhookBatch implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 30;

    /**
     * @param  list<array{record_id: int, changes: array<string, mixed>, context: array<string, mixed>}>  $items
     */
    public function __construct(
        public string $event,
        public array $items,
    ) {}

    public function handle(RecordWebhooks $recordWebhooks): void
    {
        $event = RecordWebhookEvent::tryFrom($this->event);

        if ($event !== null) {
            $recordWebhooks->deliverMany($event, $this->items);
        }
    }
}
