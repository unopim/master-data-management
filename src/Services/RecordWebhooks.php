<?php

namespace Webkul\MasterDataManagement\Services;

use Illuminate\Http\Request;
use Webkul\MasterDataManagement\Enums\RecordStatus;
use Webkul\MasterDataManagement\Enums\RecordWebhookEvent;
use Webkul\MasterDataManagement\Http\Resources\RecordResource;
use Webkul\MasterDataManagement\Models\Record;
use Webkul\MasterDataManagement\Repositories\RecordRepository;
use Webkul\MasterDataManagement\Repositories\RelationRepository;
use Webkul\MasterDataManagement\Repositories\SourceSystemRepository;
use Webkul\MasterDataManagement\ValueObjects\ChangeSet;
use Webkul\MasterDataManagement\ValueObjects\SourceContext;
use Webkul\User\Repositories\AdminRepository;
use Webkul\Webhook\Models\Webhook;
use Webkul\Webhook\Repositories\WebhookRepository;
use Webkul\Webhook\Services\WebhookService;

/**
 * Builds the webhook body of an applied record change and delivers it to the subscribed webhooks.
 *
 * The body is the REST record resource plus the entity type code, the changed attributes and the write's source.
 * A webhook narrows what it receives through `extra.masterdata` (see WEBHOOK_FILTERS): the entity type codes it
 * reports (none means all) and the source system codes whose writes it ignores, so a system is not sent back its
 * own changes. Webhooks belong to no admin, so the data access grants do not narrow them.
 */
class RecordWebhooks
{
    public const string WEBHOOK_FILTERS = 'masterdata';

    public function __construct(
        protected WebhookRepository $webhookRepository,
        protected WebhookService $webhookService,
        protected RecordRepository $recordRepository,
        protected RelationRepository $relationRepository,
        protected SourceSystemRepository $sourceSystemRepository,
        protected XrefService $xrefService,
        protected AdminRepository $adminRepository,
    ) {}

    /**
     * The changed attributes between two record snapshots: code, label, status and parent (uuid) as old/new pairs,
     * and values and references per field path.
     *
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     * @return array<string, mixed>
     */
    public function changes(array $before, array $after): array
    {
        $changes = [];

        foreach (['code', 'label', 'status'] as $key) {
            if (($before[$key] ?? null) !== ($after[$key] ?? null)) {
                $changes[$key] = ['old' => $before[$key] ?? null, 'new' => $after[$key] ?? null];
            }
        }

        if (($before['parent_id'] ?? null) != ($after['parent_id'] ?? null)) {
            $uuids = $this->recordRepository->forWebhook(array_values(array_filter([$before['parent_id'] ?? null, $after['parent_id'] ?? null], is_numeric(...))))->pluck('uuid', 'id');

            $changes['parent'] = ['old' => $uuids[$before['parent_id'] ?? 0] ?? null, 'new' => $uuids[$after['parent_id'] ?? 0] ?? null];
        }

        $values = $this->fieldChanges($this->valuePaths((array) ($before['values'] ?? [])), $this->valuePaths((array) ($after['values'] ?? [])));

        $references = $this->fieldChanges((array) ($before['references'] ?? []), (array) ($after['references'] ?? []));

        return $changes + array_filter(['values' => $values, 'references' => $references]);
    }

    /**
     * The author and source of a change, reduced to scalars the queued delivery can carry.
     *
     * @return array<string, mixed>
     */
    public function context(ChangeSet $changeSet): array
    {
        return [
            'author_id' => $changeSet->author?->id,
            'source'    => $changeSet->source instanceof SourceContext ? [
                'origin'      => $changeSet->source->origin,
                'system_id'   => $changeSet->source->sourceSystemId,
                'external_id' => $changeSet->source->externalId,
            ] : null,
        ];
    }

    /**
     * Deliver the change to every active webhook that subscribes to the event and accepts the record.
     *
     * @param  array<string, mixed>  $changes
     * @param  array<string, mixed>  $context
     */
    public function deliver(int $recordId, RecordWebhookEvent $event, array $changes, array $context): void
    {
        $this->deliverMany($event, [['record_id' => $recordId, 'changes' => $changes, 'context' => $context]]);
    }

    /**
     * Deliver several changes of one event, one request per webhook carrying the records it accepts.
     *
     * Records, crosswalk ids, references, source systems and authors are read in one query each.
     *
     * @param  list<array{record_id: int, changes: array<string, mixed>, context: array<string, mixed>}>  $items
     */
    public function deliverMany(RecordWebhookEvent $event, array $items): void
    {
        $webhooks = $this->webhookRepository->getActiveForEvent($event->value);

        if ($webhooks->isEmpty() || $items === []) {
            return;
        }

        $related = fn (array $context): array => array_map(intval(...), [
            ...(array) ($context['erased_ids'] ?? []),
            ...(isset($context['merged_id']) ? [$context['merged_id']] : []),
        ]);

        $recordIds = array_map(fn (array $item): int => (int) $item['record_id'], $items);

        $records = $this->recordRepository->forWebhook(array_values(array_unique(array_merge(
            $recordIds,
            ...array_map(fn (array $item): array => $related((array) $item['context']), $items),
        ))));

        $erased = $event === RecordWebhookEvent::Erased;

        $liveIds = $erased ? [] : array_values(array_intersect(array_unique($recordIds), $records->keys()->all()));

        $xrefIds = $this->xrefService->idsForRecords($liveIds);

        $references = $this->relationRepository->referenceValuesFor($liveIds);

        $systems = $this->sourceSystemRepository->codesById(array_values(array_unique(array_filter(array_map(
            fn (array $item): ?int => isset($item['context']['source']['system_id']) ? (int) $item['context']['source']['system_id'] : null,
            $items,
        )))));

        $actors = $this->actorNames(array_values(array_unique(array_filter(array_map(
            fn (array $item): ?int => isset($item['context']['author_id']) ? (int) $item['context']['author_id'] : null,
            $items,
        )))));

        $entries = [];

        foreach ($items as $item) {
            $record = $records->get((int) $item['record_id']);

            if (! $record instanceof Record) {
                continue;
            }

            $context = (array) $item['context'];

            $typeCode = (string) $record->entityType?->code;

            $source = $this->source((array) ($context['source'] ?? []), $systems);

            $data = $erased
                ? [
                    'uuid'         => $record->uuid,
                    'entity_type'  => $typeCode,
                    'status'       => RecordStatus::Erased->value,
                    'erased_uuids' => $records->only($related($context))->pluck('uuid')->values()->all(),
                ]
                : ['entity_type' => $typeCode] + $this->resource($record, $xrefIds[(int) $record->id] ?? [], $references[(int) $record->id] ?? []) + [
                    'changes' => (object) $item['changes'],
                    'source'  => $source,
                ] + (isset($context['merged_id']) ? ['merged_uuid' => $records->get((int) $context['merged_id'])?->uuid] : []);

            $entries[] = [
                'type'   => $typeCode,
                'system' => $source['system'] ?? null,
                'item'   => [
                    'code'  => $erased ? $record->uuid : $record->code,
                    'data'  => $data,
                    'actor' => isset($context['author_id']) ? ($actors[(int) $context['author_id']] ?? null) : null,
                ],
            ];
        }

        foreach ($webhooks as $webhook) {
            $accepted = array_values(array_map(
                fn (array $entry): array => $entry['item'],
                array_filter($entries, fn (array $entry): bool => $this->accepts($webhook, $entry['type'], $entry['system'])),
            ));

            if ($accepted !== []) {
                $this->webhookService->deliverEvents($webhook, $event->value, $accepted);
            }
        }
    }

    /**
     * Whether a webhook reports a record of this type written from this source system.
     */
    public function accepts(Webhook $webhook, string $typeCode, ?string $sourceSystem): bool
    {
        $filters = (array) (((array) $webhook->extra)[self::WEBHOOK_FILTERS] ?? []);

        $types = (array) ($filters['entity_types'] ?? []);

        if ($types !== [] && ! in_array($typeCode, $types, true)) {
            return false;
        }

        return $sourceSystem === null || ! in_array($sourceSystem, (array) ($filters['ignore_source_systems'] ?? []), true);
    }

    /**
     * Key each stored value by its field path: `common.{field}` or `locale_specific.{locale}.{field}`.
     *
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    protected function valuePaths(array $values): array
    {
        $paths = [];

        foreach ((array) ($values['common'] ?? []) as $field => $value) {
            $paths['common.'.$field] = $value;
        }

        foreach ((array) ($values['locale_specific'] ?? []) as $locale => $fields) {
            foreach ((array) $fields as $field => $value) {
                $paths['locale_specific.'.$locale.'.'.$field] = $value;
            }
        }

        return $paths;
    }

    /**
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     * @return array<string, array{old: mixed, new: mixed}>
     */
    protected function fieldChanges(array $before, array $after): array
    {
        $changes = [];

        foreach (array_keys($before + $after) as $path) {
            if (($before[$path] ?? null) !== ($after[$path] ?? null)) {
                $changes[$path] = ['old' => $before[$path] ?? null, 'new' => $after[$path] ?? null];
            }
        }

        return $changes;
    }

    /**
     * The record as the REST API returns it.
     *
     * @param  array<string, list<string>>  $ids
     * @param  array<string, string|list<string>>  $references
     * @return array<string, mixed>
     */
    protected function resource(Record $record, array $ids, array $references): array
    {
        return new RecordResource($record, $ids, $references)->resolve(resolve(Request::class));
    }

    /**
     * @param  array<string, mixed>  $source
     * @param  array<int, string>  $systems  Source system codes keyed by id.
     * @return array{origin: string, system: ?string, external_id: ?string}|null
     */
    protected function source(array $source, array $systems): ?array
    {
        if (! isset($source['origin'])) {
            return null;
        }

        return [
            'origin'      => (string) $source['origin'],
            'system'      => isset($source['system_id']) ? ($systems[(int) $source['system_id']] ?? null) : null,
            'external_id' => isset($source['external_id']) ? (string) $source['external_id'] : null,
        ];
    }

    /**
     * @param  list<int>  $adminIds
     * @return array<int, string>
     */
    protected function actorNames(array $adminIds): array
    {
        if ($adminIds === []) {
            return [];
        }

        return $this->adminRepository->findWhereIn('id', $adminIds, ['id', 'name'])
            ->mapWithKeys(fn ($admin): array => [(int) $admin->id => (string) $admin->name])
            ->all();
    }
}
