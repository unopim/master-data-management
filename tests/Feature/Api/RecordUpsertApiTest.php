<?php

use Webkul\AdminApi\Tests\Traits\ApiHelperTrait;
use Webkul\MasterDataManagement\Enums\ChangeRequestStatus;
use Webkul\MasterDataManagement\Enums\RecordAction;
use Webkul\MasterDataManagement\Models\ChangeRequest;
use Webkul\MasterDataManagement\Models\Record;
use Webkul\MasterDataManagement\Repositories\EntityTypeRepository;
use Webkul\MasterDataManagement\Repositories\RoleEntityPermissionRepository;
use Webkul\MasterDataManagement\Repositories\SourceSystemRepository;
use Webkul\MasterDataManagement\Security\EntityTypePermissions;
use Webkul\MasterDataManagement\Services\ChangeRequestService;
use Webkul\MasterDataManagement\Services\FieldService;
use Webkul\MasterDataManagement\Services\RecordWriter;
use Webkul\MasterDataManagement\Services\XrefService;
use Webkul\User\Models\Admin;
use Webkul\User\Models\Role;

uses(ApiHelperTrait::class);

beforeEach(function () {
    $this->type = app(EntityTypeRepository::class)->create(['code' => $this->uniqueCode('equipment'), 'status' => 'active', 'code_strategy' => 'source_id']);
    app(FieldService::class)->create($this->type, ['code' => 'speed', 'type' => 'integer']);
    $this->maximo = app(SourceSystemRepository::class)->create(['code' => $this->uniqueCode('maximo'), 'name' => 'Maximo', 'type' => 'cmms']);
    $this->writer = app(RecordWriter::class);
    $this->xrefs = app(XrefService::class);

    $this->url = fn (string $externalId, ?string $system = null, ?string $type = null): string => route('admin.api.masterdata.records.upsert', [
        $type ?? $this->type->code,
        $system ?? $this->maximo->code,
        $externalId,
    ]);

    $this->restrictApiUser = function (array $actions): Admin {
        $admin = Admin::query()->latest('id')->firstOrFail();
        $role = Role::factory()->create(['permission_type' => 'custom', 'permissions' => []]);
        $admin->forceFill(['type' => 'api', 'role_id' => $role->id])->save();

        app(RoleEntityPermissionRepository::class)->syncForEntityType($this->type->id, [$role->id => $actions]);
        app(EntityTypePermissions::class)->flush();

        return $admin;
    };
});

it('creates a record for an unknown external id and links the id', function () {
    $response = $this->putJson(($this->url)('A-0101210'), ['code' => 'LKV-E3', 'label' => 'Case Labeler', 'values' => ['common' => ['speed' => 40]]], $this->getAuthenticationHeaders())
        ->assertCreated()
        ->assertJsonPath('data.code', 'LKV-E3')
        ->assertJsonPath('data.values.common.speed', 40)
        ->assertJsonPath("data.ids.{$this->maximo->code}", ['A-0101210']);

    $record = $this->xrefs->resolve($this->maximo, $this->type->id, 'A-0101210');

    expect($record?->code)->toBe('LKV-E3')
        ->and($record->created_by)->not->toBeNull()
        ->and($response->headers->get('Location'))->toBe(route('admin.api.masterdata.records.show', [$this->type->code, $record->uuid]));
});

it('updates the record an external id resolves to', function () {
    $record = $this->writer->create($this->type, ['code' => 'LKV-E1', 'label' => 'Filler', 'values' => ['common' => ['speed' => 10]]], null);
    $this->xrefs->attach($record, $this->maximo, 'A-0101005');

    $this->putJson(($this->url)('A-0101005'), ['values' => ['common' => ['speed' => 12]]], $this->getAuthenticationHeaders())
        ->assertOk()
        ->assertJsonPath('data.uuid', $record->uuid)
        ->assertJsonPath('data.values.common.speed', 12)
        ->assertJsonPath('data.revision', 2)
        ->assertHeader('ETag', '"2"');

    expect(Record::query()->where('entity_type_id', $this->type->id)->count())->toBe(1);
});

it('accepts an external id with slashes and backslashes', function () {
    $tag = 'PIAF-US\\Lakeview/Line 2/CW-01';

    $this->putJson(($this->url)($tag), ['code' => 'LKV-CW'], $this->getAuthenticationHeaders())->assertCreated();

    expect($this->xrefs->resolve($this->maximo, $this->type->id, $tag)?->code)->toBe('LKV-CW');
});

it('refuses a stale If-Match revision with 412', function () {
    $record = $this->writer->create($this->type, ['code' => 'LKV-E1'], null);
    $this->xrefs->attach($record, $this->maximo, 'A-1');
    $this->writer->update($record, ['label' => 'Changed'], 1, null);

    $this->putJson(($this->url)('A-1'), ['label' => 'Mine'], $this->getAuthenticationHeaders() + ['If-Match' => '"1"'])
        ->assertStatus(412)
        ->assertJsonPath('code', 'stale_revision')
        ->assertJsonPath('current_revision', 2);

    expect($record->fresh()->label)->toBe('Changed');
});

it('rejects field rule violations with 422 and writes nothing', function () {
    $this->putJson(($this->url)('A-2'), ['code' => 'X', 'values' => ['common' => ['speed' => 'fast']]], $this->getAuthenticationHeaders())
        ->assertUnprocessable()
        ->assertJsonPath('success', false)
        ->assertJsonValidationErrors(['values.common.speed']);

    $this->putJson(($this->url)('A-2'), ['values' => 'nope'], $this->getAuthenticationHeaders())
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['values']);

    expect(Record::query()->where('entity_type_id', $this->type->id)->count())->toBe(0)
        ->and($this->xrefs->resolve($this->maximo, $this->type->id, 'A-2'))->toBeNull();
});

it('returns 404 for an unknown source system or entity type and 422 for an inactive system', function () {
    $headers = $this->getAuthenticationHeaders();

    $this->putJson(($this->url)('A-3', 'zzz_none'), ['code' => 'X'], $headers)->assertNotFound()->assertJsonPath('code', 'not_found');
    $this->putJson(($this->url)('A-3', null, 'zzz_none'), ['code' => 'X'], $headers)->assertNotFound()->assertJsonPath('code', 'not_found');

    $this->maximo->update(['is_active' => false]);

    $this->putJson(($this->url)('A-3'), ['code' => 'X'], $headers)->assertUnprocessable()->assertJsonPath('code', 'inactive');
});

it('denies an api key without the upsert scope', function () {
    $this->putJson(($this->url)('A-4'), ['code' => 'X'], $this->getAuthenticationHeaders('custom', ['api.masterdata.records']))->assertForbidden();
});

it('passes an api key with only the upsert scope', function () {
    $this->putJson(($this->url)('A-4'), ['code' => 'X'], $this->getAuthenticationHeaders('custom', ['api.masterdata.records.upsert']))
        ->assertCreated();
});

it('denies the create or the update without the entity type grant', function () {
    $record = $this->writer->create($this->type, ['code' => 'LKV-E1'], null);
    $this->xrefs->attach($record, $this->maximo, 'A-5');
    $headers = $this->getAuthenticationHeaders();
    ($this->restrictApiUser)([RecordAction::View->value]);

    $this->putJson(($this->url)('A-5'), ['label' => 'x'], $headers)->assertForbidden()->assertJsonPath('code', 'forbidden_entity_type');
    $this->putJson(($this->url)('A-6'), ['code' => 'NEW'], $headers)->assertForbidden()->assertJsonPath('code', 'forbidden_entity_type');

    expect($record->fresh()->label)->not->toBe('x');
});

it('holds both branches on an approval type for a principal who cannot approve, once per external id', function () {
    $this->type->update(['approval_required' => true]);
    $record = $this->writer->create($this->type, ['code' => 'LKV-E1', 'label' => 'Old'], null);
    $this->xrefs->attach($record, $this->maximo, 'A-7');
    $headers = $this->getAuthenticationHeaders();
    ($this->restrictApiUser)([RecordAction::View->value, RecordAction::Create->value, RecordAction::Edit->value]);

    $updated = $this->putJson(($this->url)('A-7'), ['label' => 'New'], $headers)
        ->assertAccepted()
        ->assertJsonPath('code', 'pending_approval');

    $created = $this->putJson(($this->url)('A-8'), ['code' => 'LKV-E9', 'label' => 'Pallet Labeler'], $headers)
        ->assertAccepted()
        ->assertJsonPath('code', 'pending_approval');

    $this->putJson(($this->url)('A-8'), ['code' => 'LKV-E9', 'label' => 'Pallet Labeler'], $headers)
        ->assertConflict()
        ->assertJsonPath('code', 'already_pending');

    $pending = ChangeRequest::query()->where('entity_type_id', $this->type->id)->where('status', ChangeRequestStatus::Pending->value)->orderBy('id')->get();

    expect($pending)->toHaveCount(2)
        ->and($pending->pluck('origin')->unique()->all())->toBe(['api'])
        ->and($updated->json('change_request_id'))->toBe($pending[0]->id)
        ->and($created->json('change_request_id'))->toBe($pending[1]->id)
        ->and($record->fresh()->label)->toBe('Old')
        ->and(Record::query()->where('entity_type_id', $this->type->id)->where('code', 'LKV-E9')->exists())->toBeFalse();

    $approver = Admin::factory()->create();

    app(ChangeRequestService::class)->approve($pending[1], $approver, null);

    expect($this->xrefs->resolve($this->maximo, $this->type->id, 'A-8')?->code)->toBe('LKV-E9');
});
