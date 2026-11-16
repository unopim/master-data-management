<?php

namespace Webkul\MasterDataManagement\Listeners;

use Illuminate\Validation\ValidationException;
use Webkul\MasterDataManagement\Services\WebhookRecordFilters;
use Webkul\Webhook\Models\Webhook;

/**
 * Saves the "Master data" card of the webhook form: validated before the webhook is stored, applied after.
 */
class SyncWebhookRecordFilters
{
    public function __construct(protected WebhookRecordFilters $webhookRecordFilters) {}

    /**
     * Refuse the webhook save when the card is invalid.
     *
     * @throws ValidationException
     */
    public function validate(): void
    {
        $this->webhookRecordFilters->validate(request());
    }

    /**
     * Apply the card to the saved webhook.
     */
    public function handle(mixed $webhook): void
    {
        if ($webhook instanceof Webhook) {
            $this->webhookRecordFilters->sync($webhook, request());
        }
    }
}
