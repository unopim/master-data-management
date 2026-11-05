<?php

use Webkul\AdminApi\Tests\Traits\ApiHelperTrait;
use Webkul\MasterDataManagement\Enums\ChangeRequestStatus;
use Webkul\MasterDataManagement\Enums\RecordAction;
use Webkul\MasterDataManagement\Enums\RecordStatus;
use Webkul\MasterDataManagement\Models\ChangeRequest;
use Webkul\MasterDataManagement\Models\Record;
use Webkul\MasterDataManagement\Repositories\EntityTypeRepository;
use Webkul\MasterDataManagement\Repositories\RoleEntityPermissionRepository;
use Webkul\MasterDataManagement\Security\EntityTypePermissions;
use Webkul\MasterDataManagement\Services\FieldService;
use Webkul\MasterDataManagement\Services\RecordWriter;
use Webkul\User\Models\Admin;
use Webkul\User\Models\Role;

uses(ApiHelperTrait::class);

beforeEach(function () {
    $this->types = app(EntityTypeRepository::class);
    $this->type = $this->types->create(['code' => $this->uniqueCode('line'), 'status' => 'active', 'code_strategy' => 'source_id']);
    $this->writer = app(RecordWriter::class);
    app(FieldService::class)->create($this->type, ['code' => 'speed', 'type' => 'integer']);

    $this->storeUrl = fn (): string => route('admin.api.masterdata.records.store', $this->type->code);
    $this->recordUrl = fn (string $uuid, string $action = 'update'): string => route('admin.api.masterdata.records.'.$action, [$this->type->code, $uuid]);

    $this->restrictApiUser = function (array $actions, array $permissions = []): Admin {
        $admin = Admin::query()->latest('id')->firstOrFail();
        $role = Role::factory()->create(['permission_type' => 'custom', 'permissions' => $permissions]);
        $admin->forceFill(['type' => 'api', 'role_id' => $role->id])->save();

        app(RoleEntityPermissionRepository::class)->syncForEntityType($this->type->id, [$role->id => $actions]);
        app(EntityTypePermissions::class)->flush();

        return $admin;
    };
});

it('creates a record through the api', function () {
    $response = $this->postJson(($this->storeUrl)(), ['code' => 'L-1', 'label' => 'Line 1', 'values' => ['common' => ['speed' => 5]]], $this->getAuthenticationHeaders())
        ->assertCreated()
        ->assertJsonPath('data.code', 'L-1')
        ->assertJsonPath('data.values.common.speed', 5)
        ->assertHeader('ETag', '"1"');

    $record = Record::query()->where('entity_type_id', $this->type->id)->where('code', 'L-1')->firstOrFail();

    expect($response->headers->get('Location'))->toBe(route('admin.api.masterdata.records.show', [$this->type->code, $record->uuid]))
        ->and($record->created_by)->not->toBeNull();
});

it('rejects field rule violations with 422', function () {
    $this->postJson(($this->storeUrl)(), ['code' => 'L-1', 'values' => ['common' => ['speed' => 'fast']]], $this->getAuthenticationHeaders())
        ->assertUnprocessable()
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', 'Validation failed.')
        ->assertJsonValidationErrors(['values.common.speed']);

    $this->postJson(($this->storeUrl)(), ['code' => 'L-2', 'values' => ['common' => ['nope' => 1]]], $this->getAuthenticationHeaders())
        ->assertUnprocessable();

    expect(Record::query()->where('entity_type_id', $this->type->id)->count())->toBe(0);
});

it('updates a record at the revision of the If-Match header', function () {
    $record = $this->writer->create($this->type, ['code' => 'L-1', 'label' => 'Old'], null);

    $this->putJson(($this->recordUrl)($record->uuid), ['label' => 'New'], $this->getAuthenticationHeaders() + ['If-Match' => '"1"'])
        ->assertOk()
        ->assertJsonPath('data.label', 'New')
        ->assertJsonPath('data.revision', 2)
        ->assertHeader('ETag', '"2"');
});

it('requires an If-Match header on update and delete', function () {
    $record = $this->writer->create($this->type, ['code' => 'L-1'], null);
    $headers = $this->getAuthenticationHeaders();

    $this->putJson(($this->recordUrl)($record->uuid), ['label' => 'New'], $headers)
        ->assertStatus(428)
        ->assertJsonPath('code', 'precondition_required');

    $this->deleteJson(($this->recordUrl)($record->uuid, 'delete'), [], $headers + ['If-Match' => 'abc'])
        ->assertStatus(428)
        ->assertJsonPath('code', 'precondition_required');
});

it('refuses a stale If-Match with 412 and the current revision', function () {
    $record = $this->writer->create($this->type, ['code' => 'L-1'], null);
    $this->writer->update($record, ['label' => 'Changed'], 1, null);

    $this->putJson(($this->recordUrl)($record->uuid), ['label' => 'Mine'], $this->getAuthenticationHeaders() + ['If-Match' => '1'])
        ->assertStatus(412)
        ->assertJsonPath('code', 'stale_revision')
        ->assertJsonPath('current_revision', 2);

    expect($record->fresh()->label)->toBe('Changed');
});

it('answers 409 merged_record for a merged uuid', function () {
    $survivor = $this->writer->create($this->type, ['code' => 'S'], null);
    $loser = $this->writer->create($this->type, ['code' => 'L'], null);
    Record::query()->whereKey($loser->id)->update(['merged_into_id' => $survivor->id, 'status' => RecordStatus::Merged->value]);
    $headers = $this->getAuthenticationHeaders() + ['If-Match' => '"1"'];

    $this->putJson(($this->recordUrl)($loser->uuid), ['label' => 'x'], $headers)
        ->assertStatus(409)
        ->assertJsonPath('code', 'merged_record')
        ->assertJsonPath('merged_into', $survivor->uuid);

    $this->deleteJson(($this->recordUrl)($loser->uuid, 'delete'), [], $headers)
        ->assertStatus(409)
        ->assertJsonPath('merged_into', $survivor->uuid);

    expect($survivor->fresh()->status)->toBe(RecordStatus::Active);
});

it('retires a record and refuses a second retire', function () {
    $record = $this->writer->create($this->type, ['code' => 'L-1'], null);
    $headers = $this->getAuthenticationHeaders();

    $this->deleteJson(($this->recordUrl)($record->uuid, 'delete'), [], $headers + ['If-Match' => '"1"'])
        ->assertOk()
        ->assertJsonPath('data.status', 'retired')
        ->assertHeader('ETag', '"2"');

    $this->deleteJson(($this->recordUrl)($record->uuid, 'delete'), [], $headers + ['If-Match' => '"2"'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['status']);
});

it('returns 404 for an unknown record or type on write', function () {
    $headers = $this->getAuthenticationHeaders() + ['If-Match' => '"1"'];

    $this->putJson(($this->recordUrl)(fake()->uuid()), ['label' => 'x'], $headers)
        ->assertNotFound()
        ->assertJsonPath('code', 'record_not_found');

    $this->postJson(route('admin.api.masterdata.records.store', 'zzz_none'), ['code' => 'x'], $headers)
        ->assertNotFound()
        ->assertJsonPath('code', 'entity_type_not_found');
});

it('denies an api key without the records create scope', function () {
    $record = $this->writer->create($this->type, ['code' => 'L-1'], null);
    $headers = $this->getAuthenticationHeaders('custom', ['api.masterdata.records']);

    $this->postJson(($this->storeUrl)(), ['code' => 'L-2'], $headers)->assertForbidden();
    $this->putJson(($this->recordUrl)($record->uuid), ['label' => 'x'], $headers + ['If-Match' => '"1"'])->assertForbidden();
    $this->deleteJson(($this->recordUrl)($record->uuid, 'delete'), [], $headers + ['If-Match' => '"1"'])->assertForbidden();

    app('auth')->forgetGuards();

    $allowed = $this->getAuthenticationHeaders('custom', ['api.masterdata.records', 'api.masterdata.records.create']);

    $this->postJson(($this->storeUrl)(), ['code' => 'L-2'], $allowed)->assertCreated();
});

it('denies writes without the entity type grant', function () {
    $record = $this->writer->create($this->type, ['code' => 'L-1'], null);
    $headers = $this->getAuthenticationHeaders();
    ($this->restrictApiUser)([RecordAction::View->value]);

    $this->postJson(($this->storeUrl)(), ['code' => 'L-2'], $headers)
        ->assertForbidden()
        ->assertJsonPath('code', 'forbidden_entity_type');

    $this->putJson(($this->recordUrl)($record->uuid), ['label' => 'x'], $headers + ['If-Match' => '"1"'])
        ->assertForbidden()
        ->assertJsonPath('code', 'forbidden_entity_type');
});

it('holds api writes on an approval type for a principal who cannot approve', function () {
    $this->type->update(['approval_required' => true]);
    $record = $this->writer->create($this->type, ['code' => 'L-1', 'label' => 'Old'], null);
    $headers = $this->getAuthenticationHeaders();
    $admin = ($this->restrictApiUser)([RecordAction::View->value, RecordAction::Create->value, RecordAction::Edit->value, RecordAction::Delete->value]);

    $created = $this->postJson(($this->storeUrl)(), ['code' => 'L-2'], $headers)
        ->assertAccepted()
        ->assertJsonPath('code', 'pending_approval');

    $updated = $this->putJson(($this->recordUrl)($record->uuid), ['label' => 'New'], $headers + ['If-Match' => '"1"'])
        ->assertAccepted()
        ->assertJsonPath('code', 'pending_approval');

    $pending = ChangeRequest::query()->where('entity_type_id', $this->type->id)->where('status', ChangeRequestStatus::Pending->value)->orderBy('id')->get();

    expect($pending)->toHaveCount(2)
        ->and($pending->pluck('requested_by')->unique()->all())->toBe([$admin->id])
        ->and($pending->pluck('origin')->unique()->all())->toBe(['api'])
        ->and($created->json('change_request_id'))->toBe($pending[0]->id)
        ->and($updated->json('change_request_id'))->toBe($pending[1]->id)
        ->and(Record::query()->where('entity_type_id', $this->type->id)->where('code', 'L-2')->exists())->toBeFalse()
        ->and($record->fresh()->label)->toBe('Old');
});

it('applies api writes on an approval type for a principal who may approve', function () {
    $this->type->update(['approval_required' => true]);

    $this->postJson(($this->storeUrl)(), ['code' => 'L-2'], $this->getAuthenticationHeaders())->assertCreated();

    expect(ChangeRequest::query()->where('entity_type_id', $this->type->id)->count())->toBe(0);
});
