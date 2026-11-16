<?php

namespace Webkul\MasterDataManagement\Listeners;

use Webkul\MasterDataManagement\Enums\ChangeOperation;
use Webkul\MasterDataManagement\Enums\RecordWebhookEvent;
use Webkul\MasterDataManagement\Events\RecordCreated;
use Webkul\MasterDataManagement\Events\RecordErased;
use Webkul\MasterDataManagement\Events\RecordMerged;
use Webkul\MasterDataManagement\Events\RecordRetired;
use Webkul\MasterDataManagement\Events\RecordUnmerged;
use Webkul\MasterDataManagement\Events\RecordUpdated;
use Webkul\MasterDataManagement\Jobs\SendRecordWebhook;
use Webkul\MasterDataManagement\Services\RecordWebhookBuffer;
use Webkul\MasterDataManagement\Services\RecordWebhooks;
use Webkul\Webhook\Repositories\WebhookRepository;

/**
 * Queues the webhook delivery of an applied record change, when an active webhook subscribes to its event.
 *
 * The record events fire only once a change is applied, so a change held for approval sends nothing until it is
 * approved. The update a merge or unmerge fires on the survivor is left out: the merge event already reports it.
 * A bulk import's writes go to the open RecordWebhookBuffer, which queues them in chunks.
 */
class QueueRecordWebhook
{
    public function __construct(
        protected WebhookRepository $webhookRepository,
        protected RecordWebhooks $recordWebhooks,
        protected RecordWebhookBuffer $recordWebhookBuffer,
    ) {}

    public function handle(RecordCreated|RecordUpdated|RecordRetired|RecordMerged|RecordUnmerged|RecordErased $event): void
    {
        $webhookEvent = $this->webhookEvent($event);

        if (! $webhookEvent instanceof RecordWebhookEvent || ! $this->webhookRepository->hasActiveForEvent($webhookEvent->value)) {
            return;
        }

        $record = $event instanceof RecordMerged || $event instanceof RecordUnmerged ? $event->survivor : $event->record;

        $context = match (true) {
            $event instanceof RecordErased   => ['erased_ids' => $event->erasedIds, 'author_id' => $event->by?->id],
            $event instanceof RecordMerged,
            $event instanceof RecordUnmerged => $this->recordWebhooks->context($event->changeSet) + ['merged_id' => (int) $event->merged->id],
            default                          => $this->recordWebhooks->context($event->changeSet),
        };

        $changes = $event instanceof RecordErased ? [] : $this->recordWebhooks->changes($event->changeSet->before, $event->changeSet->after);

        if ($this->recordWebhookBuffer->isOpen() && ! $event instanceof RecordErased && $event->changeSet->source?->isImport() === true) {
            $this->recordWebhookBuffer->push($webhookEvent->value, (int) $record->id, $changes, $context);

            return;
        }

        dispatch(new SendRecordWebhook((int) $record->id, $webhookEvent->value, $changes, $context))
            ->onQueue('webhooks')
            ->afterCommit();
    }

    protected function webhookEvent(object $event): ?RecordWebhookEvent
    {
        return match (true) {
            $event instanceof RecordCreated  => RecordWebhookEvent::Created,
            $event instanceof RecordRetired  => RecordWebhookEvent::Retired,
            $event instanceof RecordMerged   => RecordWebhookEvent::Merged,
            $event instanceof RecordUnmerged => RecordWebhookEvent::Unmerged,
            $event instanceof RecordErased   => RecordWebhookEvent::Erased,
            $event instanceof RecordUpdated  => match ($event->changeSet->operation) {
                ChangeOperation::Merge, ChangeOperation::Unmerge => null,
                ChangeOperation::Reactivate                      => RecordWebhookEvent::Reactivated,
                default                                          => RecordWebhookEvent::Updated,
            },
            default => null,
        };
    }
}
