<?php

use Webkul\MasterDataManagement\Enums\CodeStrategy;
use Webkul\MasterDataManagement\Enums\EntityTypeStatus;
use Webkul\MasterDataManagement\Enums\MasteringStyle;
use Webkul\MasterDataManagement\Models\EntityType;
use Webkul\MasterDataManagement\Repositories\EntityTypeRepository;
use Webkul\MasterDataManagement\Services\FieldService;
use Webkul\MasterDataManagement\Services\RecordWriter;

it('creates an entity type with translated name', function () {
    $this->loginAsAdmin();

    $code = $this->uniqueCode('supplier');

    $this->postJson(route('admin.masterdata.entity_types.store'), [
        'code' => $code,
        'name' => 'Supplier',
    ])
        ->assertOk()
        ->assertJsonStructure(['redirect_url', 'message']);

    $type = EntityType::query()->where('code', $code)->first();

    expect($type)->not->toBeNull()
        ->and($type->mastering_style)->toBe(MasteringStyle::Consolidation)
        ->and($type->code_strategy)->toBe(CodeStrategy::Pattern)
        ->and($type->status)->toBe(EntityTypeStatus::Draft)
        ->and($type->code_pattern)->toBe('{TYPE}-{seq:6}')
        ->and($type->schema_version)->toBe(0)
        ->and($type->translate(core()->getDefaultLocaleCodeFromDefaultChannel())->name)->toBe('Supplier');
});

it('updates settings and translations', function () {
    $this->loginAsAdmin();

    $type = app(EntityTypeRepository::class)->create([
        'code'                                           => $this->uniqueCode('site'),
        core()->getDefaultLocaleCodeFromDefaultChannel() => ['name' => 'Site'],
    ]);

    $this->putJson(route('admin.masterdata.entity_types.update', $type->id), [
        'translations'    => [core()->getDefaultLocaleCodeFromDefaultChannel() => ['name' => 'Plant', 'description' => 'A plant']],
        'mastering_style' => 'centralized',
        'code_strategy'   => 'source_id',
        'is_hierarchical' => true,
        'status'          => 'active',
        'settings'        => ['sla_hours' => ['review' => 24]],
    ])
        ->assertOk()
        ->assertJsonStructure(['redirect_url', 'message']);

    $type->refresh();

    expect($type->mastering_style)->toBe(MasteringStyle::Centralized)
        ->and($type->is_hierarchical)->toBeTrue()
        ->and($type->status)->toBe(EntityTypeStatus::Active)
        ->and($type->settings)->toBe(['sla_hours' => ['review' => 24]])
        ->and($type->translate(core()->getDefaultLocaleCodeFromDefaultChannel())->name)->toBe('Plant');
});

it('rejects duplicate or invalid codes', function (string $code) {
    $this->loginAsAdmin();

    $existing = app(EntityTypeRepository::class)->create(['code' => $this->uniqueCode('line')]);

    $code = $code === 'duplicate' ? $existing->code : $code;

    $this->postJson(route('admin.masterdata.entity_types.store'), ['code' => $code, 'name' => 'X'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('code');
})->with(['duplicate' => 'duplicate', 'uppercase' => 'Line', 'leading digit' => '1line', 'single char' => 'a', 'dash' => 'li-ne']);

it('refuses delete when records exist', function () {
    $this->loginAsAdmin();

    $type = app(EntityTypeRepository::class)->create(['code' => $this->uniqueCode('busy')]);
    app(RecordWriter::class)->create($type, ['label' => 'Line A'], null);

    $this->deleteJson(route('admin.masterdata.entity_types.destroy', $type->id))
        ->assertStatus(400)
        ->assertJson(['message' => trans('masterdata::app.entity-types.delete-has-records')]);

    expect(EntityType::query()->whereKey($type->id)->exists())->toBeTrue();
});

it('deletes an entity type', function () {
    $this->loginAsAdmin();

    $type = app(EntityTypeRepository::class)->create(['code' => $this->uniqueCode('temp')]);

    $this->deleteJson(route('admin.masterdata.entity_types.destroy', $type->id))
        ->assertOk()
        ->assertJsonStructure(['message']);

    expect(EntityType::query()->whereKey($type->id)->exists())->toBeFalse();
});

it('generates sequential codes', function () {
    $type = app(EntityTypeRepository::class)->create(['code' => $this->uniqueCode('line')]);
    $repository = app(EntityTypeRepository::class);

    expect($repository->nextCode($type))->toBe(strtoupper($type->code).'-000001')
        ->and($repository->nextCode($type))->toBe(strtoupper($type->code).'-000002');
});

it('honours a custom code pattern', function () {
    $repository = app(EntityTypeRepository::class);
    $type = $repository->create(['code' => $this->uniqueCode('asset'), 'code_pattern' => 'A{seq:3}-{TYPE}']);

    expect($repository->nextCode($type))->toBe('A001-'.strtoupper($type->code));
});

it('lists entity types through the datagrid', function () {
    $this->loginAsAdmin();

    $type = app(EntityTypeRepository::class)->create(['code' => $this->uniqueCode('gridtype')]);

    $this->getJson(route('admin.masterdata.entity_types.index'), ['X-Requested-With' => 'XMLHttpRequest'])
        ->assertOk()
        ->assertJsonFragment(['id' => $type->id]);

    $this->get(route('admin.masterdata.entity_types.index'))->assertOk();
});

it('renders the edit page', function () {
    $this->loginAsAdmin();

    $type = app(EntityTypeRepository::class)->create(['code' => 'editable']);

    $this->get(route('admin.masterdata.entity_types.edit', $type->id))->assertOk();
});

it('forbids users without masterdata.entity_types acl', function () {
    $this->loginWithPermissions('custom', ['dashboard']);

    $this->get(route('admin.masterdata.entity_types.index'))->assertForbidden();

    $this->postJson(route('admin.masterdata.entity_types.store'), ['code' => 'blocked', 'name' => 'Blocked'])
        ->assertForbidden();
});

it('renders the create page', function () {
    $this->loginAsAdmin();

    $this->get(route('admin.masterdata.entity_types.create'))->assertOk();
});

it('keeps stored values when a partial update omits flags and parents', function () {
    $this->loginAsAdmin();

    $repository = app(EntityTypeRepository::class);
    $parent = $repository->create(['code' => 'parenttype']);
    $type = $repository->create([
        'code'                    => 'childtype',
        'is_hierarchical'         => true,
        'approval_required'       => true,
        'allowed_parent_type_ids' => [$parent->id],
    ]);

    $this->putJson(route('admin.masterdata.entity_types.update', $type->id), ['status' => 'active'])
        ->assertOk();

    $type->refresh();

    expect($type->is_hierarchical)->toBeTrue()
        ->and($type->approval_required)->toBeTrue()
        ->and($type->allowed_parent_type_ids)->toBe([$parent->id])
        ->and($type->status)->toBe(EntityTypeStatus::Active);
});

it('rejects a code change on update', function () {
    $this->loginAsAdmin();

    $type = app(EntityTypeRepository::class)->create(['code' => 'fixedcode']);

    $this->putJson(route('admin.masterdata.entity_types.update', $type->id), ['code' => 'changed'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('code');

    expect($type->refresh()->code)->toBe('fixedcode');
});

it('forbids edit, update, create and destroy without acl', function () {
    $type = app(EntityTypeRepository::class)->create(['code' => 'guarded']);

    $this->loginWithPermissions('custom', ['dashboard']);

    $this->get(route('admin.masterdata.entity_types.create'))->assertForbidden();
    $this->get(route('admin.masterdata.entity_types.edit', $type->id))->assertForbidden();
    $this->putJson(route('admin.masterdata.entity_types.update', $type->id), ['status' => 'active'])->assertForbidden();
    $this->deleteJson(route('admin.masterdata.entity_types.destroy', $type->id))->assertForbidden();

    expect(EntityType::query()->whereKey($type->id)->exists())->toBeTrue();
});

it('accepts an active field of the type as its label field', function () {
    $this->loginAsAdmin();

    $type = app(EntityTypeRepository::class)->create(['code' => $this->uniqueCode('employee')]);
    app(FieldService::class)->create($type, ['code' => 'full_name', 'type' => 'text']);

    $this->putJson(route('admin.masterdata.entity_types.update', $type->id), ['label_field_code' => 'full_name'])
        ->assertOk();

    expect($type->refresh()->label_field_code)->toBe('full_name');
});

it('rejects a label field the type does not have as an active field', function (string $code) {
    $this->loginAsAdmin();

    $type = app(EntityTypeRepository::class)->create(['code' => $this->uniqueCode('employee')]);
    app(FieldService::class)->create($type, ['code' => 'full_name', 'type' => 'text']);
    app(FieldService::class)->create($type, ['code' => 'old_name', 'type' => 'text', 'is_archived' => true]);

    $this->putJson(route('admin.masterdata.entity_types.update', $type->id), ['label_field_code' => $code])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('label_field_code');

    expect($type->refresh()->label_field_code)->toBeNull();
})->with(['unknown' => 'emp', 'archived' => 'old_name']);

it('rejects a label field when creating a type, which has no fields yet', function () {
    $this->loginAsAdmin();

    $this->postJson(route('admin.masterdata.entity_types.store'), [
        'code'             => $this->uniqueCode('employee'),
        'name'             => 'Employee',
        'label_field_code' => 'name',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('label_field_code');
});
