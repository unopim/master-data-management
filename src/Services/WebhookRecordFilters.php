<?php

namespace Webkul\MasterDataManagement\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Webkul\MasterDataManagement\Models\EntityTypeProxy;
use Webkul\MasterDataManagement\Models\SourceSystemProxy;
use Webkul\Webhook\Models\Webhook;
use Webkul\Webhook\Repositories\WebhookRepository;

/**
 * Saves the "Master data" card of the webhook form into the webhook's `extra.masterdata`: the entity types it
 * reports and the source systems whose writes it ignores.
 */
class WebhookRecordFilters
{
    public const string INPUT = 'masterdata_webhook';

    public const array LISTS = ['entity_types', 'ignore_source_systems'];

    public function __construct(protected WebhookRepository $webhookRepository) {}

    /**
     * Whether the request carries the card.
     */
    public function submitted(Request $request): bool
    {
        return $request->boolean(self::INPUT.'.present');
    }

    /**
     * Validate the card before the webhook is saved.
     *
     * @throws ValidationException
     */
    public function validate(Request $request): void
    {
        if (! $this->submitted($request)) {
            return;
        }

        $key = self::INPUT;

        Validator::make([$key => $this->lists($request)], [
            $key.'.entity_types'            => ['array', 'max:200'],
            $key.'.entity_types.*'          => ['string', Rule::exists(EntityTypeProxy::modelClass(), 'code')],
            $key.'.ignore_source_systems'   => ['array', 'max:200'],
            $key.'.ignore_source_systems.*' => ['string', Rule::exists(SourceSystemProxy::modelClass(), 'code')],
        ], [], [
            $key.'.entity_types.*'          => trans('masterdata::app.webhooks.card.entity-types'),
            $key.'.ignore_source_systems.*' => trans('masterdata::app.webhooks.card.ignore-source-systems'),
        ])->validate();
    }

    /**
     * Store the card on the saved webhook, keeping the rest of its `extra`.
     */
    public function sync(Webhook $webhook, Request $request): void
    {
        if (! $this->submitted($request)) {
            return;
        }

        $extra = (array) $webhook->extra;

        $extra[RecordWebhooks::WEBHOOK_FILTERS] = $this->lists($request);

        $this->webhookRepository->update(['extra' => $extra], $webhook->id);
    }

    /**
     * The card's lists from the request; a multiselect may post a JSON or comma separated string.
     *
     * @return array<string, list<string>>
     */
    public function lists(Request $request): array
    {
        $lists = [];

        foreach (self::LISTS as $list) {
            $value = $request->input(self::INPUT.'.'.$list, []);

            if (is_string($value)) {
                $decoded = json_decode($value, true);

                $value = is_array($decoded) ? $decoded : explode(',', $value);
            }

            $lists[$list] = array_values(array_unique(array_filter(array_map(
                fn (mixed $item): string => is_scalar($item) ? trim((string) $item) : '',
                (array) $value,
            ), fn (string $item): bool => $item !== '')));
        }

        return $lists;
    }

    /**
     * The card's current lists of a webhook.
     *
     * @return array<string, list<string>>
     */
    public function current(?Webhook $webhook): array
    {
        $filters = (array) (((array) ($webhook?->extra ?? []))[RecordWebhooks::WEBHOOK_FILTERS] ?? []);

        return array_map(fn (string $list): array => array_values((array) ($filters[$list] ?? [])), array_combine(self::LISTS, self::LISTS));
    }
}
