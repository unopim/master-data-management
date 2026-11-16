<?php

use Webkul\MasterDataManagement\Repositories\EntityTypeRepository;
use Webkul\MasterDataManagement\Repositories\SourceSystemRepository;
use Webkul\Webhook\Models\Webhook;

beforeEach(function () {
    $this->loginAsAdmin();

    $this->type = app(EntityTypeRepository::class)->create(['code' => $this->uniqueCode('supplier'), 'status' => 'active']);
    $this->erp = app(SourceSystemRepository::class)->create(['code' => $this->uniqueCode('erp'), 'name' => 'ERP', 'type' => 'erp']);

    $this->webhook = Webhook::create([
        'name'      => 'MDM hook',
        'url'       => 'https://8.8.8.8/hook',
        'is_active' => true,
        'events'    => ['masterdata.record.created'],
        'extra'     => ['other' => 'kept'],
    ]);

    $this->payload = fn (array $card): array => [
        'name'               => 'MDM hook',
        'url'                => 'https://8.8.8.8/hook',
        'is_active'          => 1,
        'events'             => ['masterdata.record.created', 'masterdata.record.updated'],
        'masterdata_webhook' => ['present' => 1] + $card,
    ];
});

it('shows the master data card and the master record events on the webhook form', function () {
    $this->get(route('webhook.edit', $this->webhook->id))
        ->assertOk()
        ->assertSee('name="masterdata_webhook[present]"', false)
        ->assertSee(trans('masterdata::app.webhooks.card.title'))
        ->assertSee($this->type->code)
        ->assertSee(trans('masterdata::app.webhooks.events.merged'));
});

it('stores the entity type and source system filters in the webhook extra, keeping its other keys', function () {
    $this->put(route('webhook.update', $this->webhook->id), ($this->payload)([
        'entity_types'          => json_encode([$this->type->code]),
        'ignore_source_systems' => $this->erp->code,
    ]))->assertRedirect(route('webhook.edit', $this->webhook->id));

    expect($this->webhook->fresh()->extra)->toBe([
        'other'      => 'kept',
        'masterdata' => ['entity_types' => [$this->type->code], 'ignore_source_systems' => [$this->erp->code]],
    ])->and($this->webhook->fresh()->events)->toBe(['masterdata.record.created', 'masterdata.record.updated']);
});

it('refuses an unknown entity type or source system and saves nothing', function () {
    $this->put(route('webhook.update', $this->webhook->id), ($this->payload)([
        'entity_types'          => ['no-such-type'],
        'ignore_source_systems' => ['no-such-system'],
    ]))->assertSessionHasErrors(['masterdata_webhook.entity_types.0', 'masterdata_webhook.ignore_source_systems.0']);

    expect($this->webhook->fresh()->extra)->toBe(['other' => 'kept'])
        ->and($this->webhook->fresh()->events)->toBe(['masterdata.record.created']);
});

it('leaves the filters alone when the form carries no master data card', function () {
    $payload = ($this->payload)([]);
    unset($payload['masterdata_webhook']);

    $this->put(route('webhook.update', $this->webhook->id), $payload)->assertRedirect();

    expect($this->webhook->fresh()->extra)->toBe(['other' => 'kept']);
});
