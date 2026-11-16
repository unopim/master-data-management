<?php

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Webkul\DataTransfer\Helpers\Import;
use Webkul\DataTransfer\Jobs\Import\ImportTrackBatch;
use Webkul\DataTransfer\Models\JobInstances;
use Webkul\DataTransfer\Models\JobTrack;
use Webkul\MasterDataManagement\Enums\RecordAction;
use Webkul\MasterDataManagement\Enums\RecordWebhookEvent;
use Webkul\MasterDataManagement\Models\ChangeRequest;
use Webkul\MasterDataManagement\Repositories\EntityTypeRepository;
use Webkul\MasterDataManagement\Repositories\RoleEntityPermissionRepository;
use Webkul\MasterDataManagement\Repositories\SourceSystemRepository;
use Webkul\MasterDataManagement\Security\EntityTypePermissions;
use Webkul\MasterDataManagement\Services\ChangeRequestService;
use Webkul\MasterDataManagement\Services\FieldService;
use Webkul\MasterDataManagement\Services\RecordEraser;
use Webkul\MasterDataManagement\Services\RecordMerger;
use Webkul\MasterDataManagement\Services\RecordWebhookBuffer;
use Webkul\MasterDataManagement\Services\RecordWriter;
use Webkul\MasterDataManagement\Services\XrefService;
use Webkul\MasterDataManagement\ValueObjects\SourceContext;
use Webkul\User\Models\Admin;
use Webkul\User\Models\Role;
use Webkul\Webhook\Models\Webhook;
use Webkul\Webhook\Models\WebhookLog;
use Webkul\Webhook\Registry\EventRegistry;

beforeEach(function () {
    Http::fake(['*' => Http::response(['ok' => true], 200)]);

    WebhookLog::query()->delete();
    Webhook::query()->delete();

    $this->type = app(EntityTypeRepository::class)->create(['code' => $this->uniqueCode('supplier'), 'status' => 'active', 'label_field_code' => 'name', 'code_strategy' => 'source_id']);

    foreach (['name', 'city'] as $code) {
        app(FieldService::class)->create($this->type, ['code' => $code, 'type' => 'text']);
    }

    $this->type = $this->type->fresh();
    $this->writer = app(RecordWriter::class);
    $this->erp = app(SourceSystemRepository::class)->create(['code' => $this->uniqueCode('erp'), 'name' => 'ERP', 'type' => 'erp']);

    $this->hook = fn (array $events, array $extra = []): Webhook => Webhook::create([
        'name'      => 'MDM hook',
        'url'       => 'https://8.8.8.8/hook',
        'is_active' => true,
        'events'    => array_map(fn (RecordWebhookEvent $event): string => $event->value, $events),
        'extra'     => $extra,
    ]);

    $this->record = fn (string $code, array $common, ?SourceContext $source = null) => $this->writer->create(
        $this->type,
        ['code' => $code, 'values' => ['common' => $common]],
        null,
        $source,
    );

    $this->sent = fn (RecordWebhookEvent $event): array => Http::recorded()
        ->map(fn (array $pair): Request => $pair[0])
        ->filter(fn (Request $request): bool => ($request->data()['event'] ?? null) === $event->value)
        ->values()
        ->all();
});

it('registers the master record events with the webhook event registry', function () {
    expect(app(EventRegistry::class)->groups()['masterdata'] ?? [])->toHaveKeys(array_keys(RecordWebhookEvent::registry()))
        ->and(app(EventRegistry::class)->has('masterdata.record.created'))->toBeTrue();
});

it('sends a created record in the REST shape with its entity type, changes and source', function () {
    $webhook = ($this->hook)([RecordWebhookEvent::Created]);

    $record = ($this->record)('SUP-1', ['name' => 'Acme', 'city' => 'Pune'], new SourceContext('api', $this->erp->id, 'E-1'));

    app(XrefService::class)->attach($record, $this->erp, 'E-1');

    $requests = ($this->sent)(RecordWebhookEvent::Created);

    expect($requests)->toHaveCount(1);

    $data = $requests[0]->data()['data'][0];

    expect($requests[0]->header('X-Unopim-Event')[0])->toBe('masterdata.record.created')
        ->and($data['entity_type'])->toBe($this->type->code)
        ->and($data['uuid'])->toBe($record->uuid)
        ->and($data['code'])->toBe('SUP-1')
        ->and($data['status'])->toBe('active')
        ->and($data['values']['common'])->toEqual(['name' => 'Acme', 'city' => 'Pune'])
        ->and($data)->toHaveKeys(['label', 'revision', 'schema_version', 'ids', 'references', 'created_at', 'updated_at'])
        ->and($data['changes']['code'])->toBe(['old' => null, 'new' => 'SUP-1'])
        ->and($data['changes']['values']['common.city'])->toBe(['old' => null, 'new' => 'Pune'])
        ->and($data['source'])->toBe(['origin' => 'api', 'system' => $this->erp->code, 'external_id' => 'E-1'])
        ->and(WebhookLog::query()->where('webhook_id', $webhook->id)->where('event', 'masterdata.record.created')->value('sku'))->toBe('SUP-1');
});

it('sends only the changed attributes of an update and skips webhooks not subscribed to it', function () {
    ($this->hook)([RecordWebhookEvent::Created]);
    $updates = ($this->hook)([RecordWebhookEvent::Updated]);

    $record = ($this->record)('SUP-2', ['name' => 'Acme', 'city' => 'Pune']);

    $this->writer->update($record, ['values' => ['common' => ['city' => 'Mumbai']]], $record->revision, null);

    $requests = ($this->sent)(RecordWebhookEvent::Updated);

    expect($requests)->toHaveCount(1)
        ->and($requests[0]->header('X-Unopim-Webhook-Id')[0])->toBe((string) $updates->id)
        ->and($requests[0]->data()['data'][0]['changes'])->toBe(['values' => ['common.city' => ['old' => 'Pune', 'new' => 'Mumbai']]])
        ->and(($this->sent)(RecordWebhookEvent::Created))->toHaveCount(1);
});

it('sends nothing while a change waits for approval and sends it once approved', function () {
    $type = app(EntityTypeRepository::class)->create(['code' => $this->uniqueCode('line'), 'status' => 'active', 'code_strategy' => 'source_id', 'approval_required' => true]);

    $admin = function (bool $approves) use ($type): Admin {
        $role = Role::factory()->create(['permission_type' => 'custom', 'permissions' => array_merge(
            ['masterdata', 'masterdata.records', 'masterdata.records.view', 'masterdata.records.create', 'masterdata.records.edit', 'masterdata.approvals'],
            $approves ? [RecordAction::Approve->aclKey()] : [],
        )]);

        app(RoleEntityPermissionRepository::class)->syncForEntityType($type->id, [
            $role->id => array_merge(['view', 'create', 'edit'], $approves ? ['approve'] : []),
        ]);

        app(EntityTypePermissions::class)->flush();

        return Admin::factory()->create(['role_id' => $role->id]);
    };

    $requester = $admin(false);
    $approver = $admin(true);

    ($this->hook)([RecordWebhookEvent::Created]);

    $result = $this->writer->submitCreate($type, ['code' => 'L-1'], $requester);

    expect($result->gate->isPending())->toBeTrue()
        ->and(($this->sent)(RecordWebhookEvent::Created))->toBe([]);

    app(ChangeRequestService::class)->approve(ChangeRequest::query()->where('entity_type_id', $type->id)->latest('id')->firstOrFail(), $approver, null);

    $requests = ($this->sent)(RecordWebhookEvent::Created);

    expect($requests)->toHaveCount(1)
        ->and($requests[0]->data()['data'][0]['code'])->toBe('L-1');
});

it('reports a merge once with the merged uuid and no separate survivor update', function () {
    ($this->hook)([RecordWebhookEvent::Merged, RecordWebhookEvent::Updated, RecordWebhookEvent::Unmerged]);

    $survivor = ($this->record)('SUP-3', ['name' => 'Acme Steel']);
    $duplicate = ($this->record)('SUP-4', ['name' => 'ACME', 'city' => 'Pune']);

    app(RecordMerger::class)->merge($survivor, $duplicate, [], $survivor->revision, $duplicate->revision, null);

    $merged = ($this->sent)(RecordWebhookEvent::Merged);

    expect($merged)->toHaveCount(1)
        ->and($merged[0]->data()['data'][0]['uuid'])->toBe($survivor->uuid)
        ->and($merged[0]->data()['data'][0]['merged_uuid'])->toBe($duplicate->uuid)
        ->and($merged[0]->data()['data'][0]['changes']['values']['common.city'])->toBe(['old' => null, 'new' => 'Pune'])
        ->and(($this->sent)(RecordWebhookEvent::Updated))->toBe([]);

    app(RecordMerger::class)->unmerge($duplicate->fresh(), null);

    expect(($this->sent)(RecordWebhookEvent::Unmerged))->toHaveCount(1)
        ->and(($this->sent)(RecordWebhookEvent::Updated))->toBe([]);
});

it('reports a retire and a reactivation under their own events', function () {
    ($this->hook)([RecordWebhookEvent::Retired, RecordWebhookEvent::Reactivated, RecordWebhookEvent::Updated]);

    $record = ($this->record)('SUP-5', ['name' => 'Acme']);
    $record = $this->writer->retire($record, $record->revision, null);
    $this->writer->reactivate($record, $record->revision, null);

    expect(($this->sent)(RecordWebhookEvent::Retired)[0]->data()['data'][0]['changes'])->toBe(['status' => ['old' => 'active', 'new' => 'retired']])
        ->and(($this->sent)(RecordWebhookEvent::Reactivated)[0]->data()['data'][0]['status'])->toBe('active')
        ->and(($this->sent)(RecordWebhookEvent::Updated))->toBe([]);
});

it('sends only the uuids of an erased record, never its data', function () {
    ($this->hook)([RecordWebhookEvent::Erased]);

    $record = ($this->record)('SUP-6', ['name' => 'Jane']);
    $record = $this->writer->retire($record, $record->revision, null);

    app(RecordEraser::class)->erase($record, $record->revision, null);

    $requests = ($this->sent)(RecordWebhookEvent::Erased);

    expect($requests)->toHaveCount(1)
        ->and($requests[0]->data()['data'][0])->toBe([
            'uuid'         => $record->uuid,
            'entity_type'  => $this->type->code,
            'status'       => 'erased',
            'erased_uuids' => [$record->uuid],
        ])
        ->and($requests[0]->body())->not->toContain('Jane')
        ->and($requests[0]->body())->not->toContain('SUP-6');
});

it('honours the entity type filter and ignores writes from the listed source systems', function () {
    $otherType = ($this->hook)([RecordWebhookEvent::Created], ['masterdata' => ['entity_types' => ['not-this-type']]]);
    $noEcho = ($this->hook)([RecordWebhookEvent::Created], ['masterdata' => ['entity_types' => [$this->type->code], 'ignore_source_systems' => [$this->erp->code]]]);

    ($this->record)('SUP-7', ['name' => 'From ERP'], new SourceContext('api', $this->erp->id, 'E-7'));

    expect(($this->sent)(RecordWebhookEvent::Created))->toBe([]);

    ($this->record)('SUP-8', ['name' => 'From admin'], new SourceContext('admin'));

    $requests = ($this->sent)(RecordWebhookEvent::Created);

    expect($requests)->toHaveCount(1)
        ->and($requests[0]->header('X-Unopim-Webhook-Id')[0])->toBe((string) $noEcho->id)
        ->and(WebhookLog::query()->where('webhook_id', $otherType->id)->exists())->toBeFalse();
});

it('queues nothing when no webhook subscribes to master data events', function () {
    ($this->hook)([]);

    ($this->record)('SUP-9', ['name' => 'Acme']);

    Http::assertNothingSent();
});

it('sends the records one import batch writes in one request per webhook, with a log row per record', function () {
    $webhook = ($this->hook)([RecordWebhookEvent::Created, RecordWebhookEvent::Updated]);

    ($this->record)('SUP-10', ['name' => 'Old name']);

    Storage::fake('private');

    $path = 'imports/'.$this->uniqueCode('file').'.csv';
    Storage::disk('private')->put($path, "code,name\nSUP-10,New name\nSUP-11,Beta\nSUP-12,Gamma\n");

    $settings = [
        'type'                => 'import',
        'action'              => Import::ACTION_APPEND,
        'validation_strategy' => Import::VALIDATION_STRATEGY_SKIP_ERRORS,
        'allowed_errors'      => 0,
        'field_separator'     => ',',
        'file_path'           => $path,
    ];

    $instance = JobInstances::query()->create($settings + [
        'code'        => $this->uniqueCode('suppliers'),
        'entity_type' => 'masterdata_records',
        'filters'     => ['entity_type' => $this->type->code],
    ]);

    $track = JobTrack::query()->create($settings + [
        'state'            => Import::STATE_PENDING,
        'meta'             => $instance->toArray(),
        'job_instances_id' => $instance->id,
        'user_id'          => Admin::factory()->create(['role_id' => Role::factory()->create(['permission_type' => 'all'])->id])->id,
    ]);

    Http::fake(['*' => Http::response(['ok' => true], 200)]);
    WebhookLog::query()->delete();

    ImportTrackBatch::dispatchSync($track);

    expect($track->refresh()->state)->toBe(Import::STATE_COMPLETED);

    $created = ($this->sent)(RecordWebhookEvent::Created);
    $updated = ($this->sent)(RecordWebhookEvent::Updated);

    expect($created)->toHaveCount(1)
        ->and(array_column($created[0]->data()['data'], 'code'))->toBe(['SUP-11', 'SUP-12'])
        ->and($created[0]->data()['data'][0]['source']['origin'])->toBe('import')
        ->and($updated)->toHaveCount(1)
        ->and($updated[0]->data()['data'][0]['changes'])->toBe(['label' => ['old' => 'Old name', 'new' => 'New name'], 'values' => ['common.name' => ['old' => 'Old name', 'new' => 'New name']]])
        ->and(WebhookLog::query()->where('webhook_id', $webhook->id)->orderBy('sku')->pluck('sku')->all())->toBe(['SUP-10', 'SUP-11', 'SUP-12']);

    $log = WebhookLog::query()->where('sku', 'SUP-12')->sole();

    expect(array_column($log->extra['payload']['data'], 'code'))->toBe(['SUP-12']);
});

it('queues the collected deliveries in chunks once the outermost collection ends', function () {
    ($this->hook)([RecordWebhookEvent::Created]);

    $buffer = app(RecordWebhookBuffer::class);
    $buffer->open();
    $buffer->open();

    foreach (range(1, RecordWebhookBuffer::CHUNK_SIZE + 1) as $number) {
        ($this->record)('BULK-'.$number, ['name' => 'Bulk '.$number], new SourceContext(SourceContext::IMPORT));
    }

    $buffer->flush();

    expect(Http::recorded())->toHaveCount(0);

    $buffer->flush();

    $requests = ($this->sent)(RecordWebhookEvent::Created);

    expect($requests)->toHaveCount(2)
        ->and(count($requests[0]->data()['data']))->toBe(RecordWebhookBuffer::CHUNK_SIZE)
        ->and(count($requests[1]->data()['data']))->toBe(1)
        ->and($buffer->isOpen())->toBeFalse();
});

it('sends a write that is not an import at once, even while a collection is open', function () {
    ($this->hook)([RecordWebhookEvent::Created]);

    $buffer = app(RecordWebhookBuffer::class);
    $buffer->open();

    ($this->record)('NOW-1', ['name' => 'Now'], new SourceContext('api'));

    expect(($this->sent)(RecordWebhookEvent::Created))->toHaveCount(1);

    $buffer->flush();
});

it('closes the collection when the import batch transaction rolls back', function () {
    ($this->hook)([RecordWebhookEvent::Created]);

    $buffer = app(RecordWebhookBuffer::class);

    try {
        DB::transaction(function () use ($buffer): void {
            $buffer->open();

            try {
                ($this->record)('ROLLED-1', ['name' => 'Rolled'], new SourceContext(SourceContext::IMPORT));

                throw new RuntimeException('batch failed');
            } finally {
                $buffer->flush();
            }
        });
    } catch (RuntimeException) {
    }

    expect($buffer->isOpen())->toBeFalse()
        ->and(Http::recorded())->toHaveCount(0);
});
