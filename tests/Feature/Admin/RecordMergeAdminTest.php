<?php

use Webkul\MasterDataManagement\Enums\RecordStatus;
use Webkul\MasterDataManagement\Models\RecordMerge;
use Webkul\MasterDataManagement\Repositories\EntityTypeRepository;
use Webkul\MasterDataManagement\Services\FieldService;
use Webkul\MasterDataManagement\Services\RecordWriter;

beforeEach(function () {
    $this->type = app(EntityTypeRepository::class)->create(['code' => $this->uniqueCode('supplier'), 'label_field_code' => 'name']);

    foreach (['name' => 'text', 'city' => 'text'] as $code => $fieldType) {
        app(FieldService::class)->create($this->type, ['code' => $code, 'type' => $fieldType]);
    }

    $writer = app(RecordWriter::class);

    $this->survivor = $writer->create($this->type->fresh(), ['values' => ['common' => ['name' => 'Acme Steel']]], null);
    $this->duplicate = $writer->create($this->type->fresh(), ['values' => ['common' => ['name' => 'Acme Steel Ltd', 'city' => 'Pune']]], null);
});

it('shows the merge screen with a field comparison for the chosen duplicate', function () {
    $this->loginAsAdmin();

    $this->get(route('admin.masterdata.records.merge.create', $this->survivor->uuid))
        ->assertOk()
        ->assertSee(trans('masterdata::app.records.merge.compare'));

    $this->get(route('admin.masterdata.records.merge.create', [$this->survivor->uuid, 'with' => $this->duplicate->uuid]))
        ->assertOk()
        ->assertSee('Acme Steel Ltd')
        ->assertSee('Pune')
        ->assertSee(trans('masterdata::app.records.merge.submit'));
});

it('lists other records of the type as merge candidates', function () {
    $this->loginAsAdmin();

    $ids = collect($this->getJson(route('admin.masterdata.records.merge.options', $this->survivor->uuid))->assertOk()->json('options'))->pluck('id');

    expect($ids->all())->toContain($this->duplicate->uuid)
        ->and($ids->all())->not->toContain($this->survivor->uuid);
});

it('merges and unmerges through the admin', function () {
    $this->loginAsAdmin();

    $this->postJson(route('admin.masterdata.records.merge.store', $this->survivor->uuid), [
        'merged'            => $this->duplicate->uuid,
        'survivor_revision' => $this->survivor->revision,
        'merged_revision'   => $this->duplicate->revision,
        'choices'           => ['name' => 'merged'],
    ])->assertOk()->assertJsonPath('redirect_url', route('admin.masterdata.records.edit', $this->survivor->uuid));

    expect($this->duplicate->fresh()->status)->toBe(RecordStatus::Merged)
        ->and($this->survivor->fresh()->label)->toBe('Acme Steel Ltd')
        ->and($this->survivor->fresh()->values['common']['city'])->toBe('Pune');

    $merge = RecordMerge::query()->where('merged_id', $this->duplicate->id)->firstOrFail();

    $this->get(route('admin.masterdata.records.edit', $this->survivor->uuid))
        ->assertOk()
        ->assertSee(route('admin.masterdata.records.merge.destroy', [$this->survivor->uuid, $merge->id]), false);

    $this->deleteJson(route('admin.masterdata.records.merge.destroy', [$this->survivor->uuid, $merge->id]))->assertOk();

    expect($this->duplicate->fresh()->status)->toBe(RecordStatus::Active)
        ->and($this->survivor->fresh()->label)->toBe('Acme Steel');
});

it('validates the merge input', function () {
    $this->loginAsAdmin();

    $this->postJson(route('admin.masterdata.records.merge.store', $this->survivor->uuid), [
        'merged'  => 'not-a-uuid',
        'choices' => ['name' => 'both'],
    ])->assertUnprocessable()->assertJsonValidationErrors(['merged', 'survivor_revision', 'merged_revision', 'choices.name']);
});

it('reports a stale revision as a validation error', function () {
    $this->loginAsAdmin();

    $this->postJson(route('admin.masterdata.records.merge.store', $this->survivor->uuid), [
        'merged'            => $this->duplicate->uuid,
        'survivor_revision' => $this->survivor->revision + 3,
        'merged_revision'   => $this->duplicate->revision,
    ])->assertUnprocessable()->assertJsonValidationErrors(['merged']);

    expect($this->duplicate->fresh()->status)->toBe(RecordStatus::Active);
});

it('refuses merging without the merge permission', function () {
    $this->loginWithPermissions('custom', ['masterdata', 'masterdata.records', 'masterdata.records.view', 'masterdata.records.edit']);

    $this->get(route('admin.masterdata.records.merge.create', $this->survivor->uuid))->assertForbidden();

    $this->postJson(route('admin.masterdata.records.merge.store', $this->survivor->uuid), [
        'merged'            => $this->duplicate->uuid,
        'survivor_revision' => $this->survivor->revision,
        'merged_revision'   => $this->duplicate->revision,
    ])->assertForbidden();

    expect($this->duplicate->fresh()->status)->toBe(RecordStatus::Active);
});
