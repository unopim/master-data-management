<?php

namespace Webkul\MasterDataManagement\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Webkul\MasterDataManagement\Enums\RecordWebhookEvent;
use Webkul\MasterDataManagement\Services\RecordWebhooks;

/**
 * Delivers one applied record change to every active webhook subscribed to its event.
 */
class SendRecordWebhook implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 30;

    /**
     * @param  array<string, mixed>  $changes  Attribute => old/new pairs, see RecordWebhooks::changes().
     * @param  array<string, mixed>  $context  Author, source and merge or erase details, see RecordWebhooks::context().
     */
    public function __construct(
        public int $recordId,
        public string $event,
        public array $changes = [],
        public array $context = [],
    ) {}

    public function handle(RecordWebhooks $recordWebhooks): void
    {
        $event = RecordWebhookEvent::tryFrom($this->event);

        if ($event !== null) {
            $recordWebhooks->deliver($this->recordId, $event, $this->changes, $this->context);
        }
    }
}
