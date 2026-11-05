<?php

use Webkul\MasterDataManagement\Enums\RecordStatus;
use Webkul\MasterDataManagement\Models\EntityType;
use Webkul\MasterDataManagement\Models\Record;
use Webkul\MasterDataManagement\Repositories\EntityTypeRepository;
use Webkul\MasterDataManagement\Repositories\RoleEntityPermissionRepository;
use Webkul\MasterDataManagement\Services\RecordWriter;

beforeEach(function () {
    $this->writer = app(RecordWriter::class);
    $this->type = app(EntityTypeRepository::class)->create(['code' => $this->uniqueCode('employee'), 'code_strategy' => 'source_id']);

    $this->record = fn (string $code, bool $retired = false): Record => $retired
        ? $this->writer->retire($created = $this->writer->create($this->type, ['code' => $code, 'label' => $code], null), $created->revision, null)
        : $this->writer->create($this->type, ['code' => $code, 'label' => $code], null);

    $this->loginAs = function (EntityType $type, array $permissions, array $actions): void {
        $admin = $this->loginWithPermissions('custom', $permissions);

        app(RoleEntityPermissionRepository::class)->syncForEntityType($type->id, [$admin->role_id => $actions]);
    };

    $this->rowActions = fn (): array => collect($this->ajaxGrid(route('admin.masterdata.records.index', $this->type->code))->assertOk()->json('records'))
        ->mapWithKeys(fn (array $row): array => [$row['code'] => collect($row['actions'])->pluck('index')->all()])
        ->all();
});

it('erases a retired record from the admin', function () {
    $this->loginAsAdmin();
    $record = ($this->record)('EMP-1', true);

    $this->postJson(route('admin.masterdata.records.erase', $record->uuid), ['revision' => $record->revision])
        ->assertOk()
        ->assertJsonPath('message', trans('masterdata::app.records.erase.success'));

    expect($record->fresh()->status)->toBe(RecordStatus::Erased);
});

it('refuses to erase an active record or at a stale revision', function () {
    $this->loginAsAdmin();
    $active = ($this->record)('EMP-2');
    $retired = ($this->record)('EMP-3', true);

    $this->postJson(route('admin.masterdata.records.erase', $active->uuid), ['revision' => $active->revision])->assertUnprocessable();
    $this->postJson(route('admin.masterdata.records.erase', $retired->uuid), ['revision' => $retired->revision - 1])->assertConflict();

    expect($active->fresh()->status)->toBe(RecordStatus::Active)
        ->and($retired->fresh()->status)->toBe(RecordStatus::Retired);
});

it('needs the erase permission on top of retiring', function () {
    $record = ($this->record)('EMP-4', true);

    ($this->loginAs)($this->type, ['masterdata', 'masterdata.records', 'masterdata.records.view', 'masterdata.records.delete'], ['view', 'delete']);

    $this->postJson(route('admin.masterdata.records.erase', $record->uuid), ['revision' => $record->revision])->assertForbidden();

    expect(($this->rowActions)()['EMP-4'])->not->toContain('erase');

    ($this->loginAs)($this->type, ['masterdata', 'masterdata.records', 'masterdata.records.view', 'masterdata.records.erase'], ['view']);

    $this->postJson(route('admin.masterdata.records.erase', $record->uuid), ['revision' => $record->revision])->assertForbidden();
});

it('offers erase only on retired rows', function () {
    $this->loginAsAdmin();
    ($this->record)('EMP-5');
    ($this->record)('EMP-6', true);

    $actions = ($this->rowActions)();

    expect($actions['EMP-5'])->toContain('retire')->not->toContain('erase')
        ->and($actions['EMP-6'])->toContain('erase')->toContain('reactivate');
});

it('retires the selected live records and skips the rest', function () {
    $this->loginAsAdmin();
    $first = ($this->record)('EMP-7');
    $second = ($this->record)('EMP-8');
    $retired = ($this->record)('EMP-9', true);

    $grid = $this->ajaxGrid(route('admin.masterdata.records.index', $this->type->code))->json();

    expect(collect($grid['mass_actions'])->pluck('url'))->toContain(route('admin.masterdata.records.mass_retire', $this->type->code));

    $this->postJson(route('admin.masterdata.records.mass_retire', $this->type->code), ['indices' => [$first->id, $second->id, $retired->id]])
        ->assertOk()
        ->assertJsonPath('retired', 2)
        ->assertJsonPath('skipped', 1);

    expect($first->fresh()->status)->toBe(RecordStatus::Retired)
        ->and($second->fresh()->status)->toBe(RecordStatus::Retired);
});

it('only mass retires records of the type in the url', function () {
    $this->loginAsAdmin();
    $other = app(EntityTypeRepository::class)->create(['code' => $this->uniqueCode('site'), 'code_strategy' => 'source_id']);
    $foreign = $this->writer->create($other, ['code' => 'S-1'], null);

    $this->postJson(route('admin.masterdata.records.mass_retire', $this->type->code), ['indices' => [$foreign->id]])
        ->assertBadRequest();

    expect($foreign->fresh()->status)->toBe(RecordStatus::Active);
});

it('forbids mass retire without the delete grant', function () {
    $record = ($this->record)('EMP-10');

    ($this->loginAs)($this->type, ['masterdata', 'masterdata.records', 'masterdata.records.view', 'masterdata.records.delete'], ['view']);

    $this->postJson(route('admin.masterdata.records.mass_retire', $this->type->code), ['indices' => [$record->id]])->assertForbidden();

    $grid = $this->ajaxGrid(route('admin.masterdata.records.index', $this->type->code))->json();

    expect($grid['mass_actions'])->toBe([])
        ->and($record->fresh()->status)->toBe(RecordStatus::Active);
});

it('validates the mass retire selection', function () {
    $this->loginAsAdmin();

    $this->postJson(route('admin.masterdata.records.mass_retire', $this->type->code), ['indices' => []])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('indices');
});
