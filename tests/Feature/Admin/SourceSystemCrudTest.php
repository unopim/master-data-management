<?php

use Webkul\MasterDataManagement\Enums\SourceSystemType;
use Webkul\MasterDataManagement\Models\SourceSystem;
use Webkul\MasterDataManagement\Repositories\EntityTypeRepository;
use Webkul\MasterDataManagement\Repositories\SourceSystemRepository;
use Webkul\MasterDataManagement\Services\RecordWriter;
use Webkul\MasterDataManagement\Services\XrefService;

it('creates a source system with defaults', function () {
    $this->loginAsAdmin();

    $code = $this->uniqueCode('sap_erp');

    $this->postJson(route('admin.masterdata.source_systems.store'), [
        'code' => $code,
        'name' => 'SAP ERP',
        'type' => 'erp',
    ])
        ->assertOk()
        ->assertJsonStructure(['redirect_url', 'message']);

    $system = SourceSystem::query()->where('code', $code)->first();

    expect($system->type)->toBe(SourceSystemType::Erp)
        ->and($system->priority)->toBe(100)
        ->and($system->is_active)->toBeTrue()
        ->and($system->requires_approval)->toBeFalse()
        ->and($system->external_id_is_personal)->toBeFalse();
});

it('updates a source system but never its code', function () {
    $this->loginAsAdmin();

    $system = app(SourceSystemRepository::class)->create(['code' => $code = $this->uniqueCode('mes'), 'name' => 'MES', 'type' => 'mes']);

    $this->putJson(route('admin.masterdata.source_systems.update', $system->id), [
        'name'                    => 'Shop floor',
        'type'                    => 'other',
        'priority'                => 10,
        'is_active'               => false,
        'requires_approval'       => true,
        'external_id_is_personal' => true,
        'contact_owner'           => 'ops@example.com',
    ])->assertOk();

    $system->refresh();

    expect($system->name)->toBe('Shop floor')
        ->and($system->type)->toBe(SourceSystemType::Other)
        ->and($system->priority)->toBe(10)
        ->and($system->is_active)->toBeFalse()
        ->and($system->requires_approval)->toBeTrue()
        ->and($system->external_id_is_personal)->toBeTrue()
        ->and($system->code)->toBe($code);

    $this->putJson(route('admin.masterdata.source_systems.update', $system->id), ['code' => 'renamed', 'name' => 'x', 'type' => 'erp'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('code');
});

it('rejects duplicate or invalid source system input', function (array $override, string $field) {
    $this->loginAsAdmin();

    $existing = app(SourceSystemRepository::class)->create(['code' => $this->uniqueCode('erp'), 'name' => 'ERP', 'type' => 'erp']);

    if (($override['code'] ?? null) === 'duplicate') {
        $override['code'] = $existing->code;
    }

    $this->postJson(route('admin.masterdata.source_systems.store'), $override + ['code' => $this->uniqueCode('new_sys'), 'name' => 'New', 'type' => 'erp'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors($field);
})->with([
    'duplicate code' => [['code' => 'duplicate'], 'code'],
    'bad code'       => [['code' => 'Bad-Code'], 'code'],
    'bad type'       => [['type' => 'warehouse'], 'type'],
    'bad priority'   => [['priority' => -1], 'priority'],
    'missing name'   => [['name' => ''], 'name'],
]);

it('refuses to delete a source system that has crosswalk entries', function () {
    $this->loginAsAdmin();

    $type = app(EntityTypeRepository::class)->create(['code' => $this->uniqueCode('line')]);
    $record = app(RecordWriter::class)->create($type, ['label' => 'A'], null);
    $system = app(SourceSystemRepository::class)->create(['code' => $this->uniqueCode('erp'), 'name' => 'ERP', 'type' => 'erp']);
    app(XrefService::class)->attach($record, $system, 'L1');

    $this->deleteJson(route('admin.masterdata.source_systems.destroy', $system->id))
        ->assertStatus(400)
        ->assertJson(['message' => trans('masterdata::app.source-systems.delete-has-xrefs')]);

    expect(SourceSystem::query()->whereKey($system->id)->exists())->toBeTrue();
});

it('deletes an unused source system', function () {
    $this->loginAsAdmin();

    $system = app(SourceSystemRepository::class)->create(['code' => $this->uniqueCode('temp'), 'name' => 'Temp', 'type' => 'other']);

    $this->deleteJson(route('admin.masterdata.source_systems.destroy', $system->id))->assertOk();

    expect(SourceSystem::query()->whereKey($system->id)->exists())->toBeFalse();
});

it('lists source systems and renders the pages', function () {
    $this->loginAsAdmin();

    $system = app(SourceSystemRepository::class)->create(['code' => $this->uniqueCode('gridsys'), 'name' => 'Grid', 'type' => 'crm']);

    $this->getJson(route('admin.masterdata.source_systems.index'), ['X-Requested-With' => 'XMLHttpRequest'])
        ->assertOk()
        ->assertJsonFragment(['code' => $system->code]);

    $this->get(route('admin.masterdata.source_systems.index'))->assertOk();
    $this->get(route('admin.masterdata.source_systems.create'))->assertOk();
    $this->get(route('admin.masterdata.source_systems.edit', $system->id))->assertOk();
});

it('forbids users without masterdata.source_systems acl', function () {
    $this->loginWithPermissions('custom', ['dashboard']);

    $this->get(route('admin.masterdata.source_systems.index'))->assertForbidden();
    $this->get(route('admin.masterdata.source_systems.create'))->assertForbidden();

    $this->postJson(route('admin.masterdata.source_systems.store'), ['code' => 'blocked', 'name' => 'B', 'type' => 'erp'])
        ->assertForbidden();
});

it('reads the hidden zero plus switch payload as false when off and true when on', function () {
    $this->loginAsAdmin();

    $system = app(SourceSystemRepository::class)->create(['code' => $this->uniqueCode('flags'), 'name' => 'Flags', 'type' => 'erp']);

    $this->putJson(route('admin.masterdata.source_systems.update', $system->id), [
        'name' => 'Flags', 'type' => 'erp', 'is_active' => '1', 'requires_approval' => '0', 'external_id_is_personal' => '1',
    ])->assertOk();

    $system->refresh();

    expect($system->is_active)->toBeTrue()
        ->and($system->requires_approval)->toBeFalse()
        ->and($system->external_id_is_personal)->toBeTrue();

    $this->putJson(route('admin.masterdata.source_systems.update', $system->id), [
        'name' => 'Flags', 'type' => 'erp', 'is_active' => '0', 'requires_approval' => '1', 'external_id_is_personal' => '0',
    ])->assertOk();

    $system->refresh();

    expect($system->is_active)->toBeFalse()
        ->and($system->requires_approval)->toBeTrue()
        ->and($system->external_id_is_personal)->toBeFalse();
});
