<?php

use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Webkul\AdminApi\Tests\Traits\ApiHelperTrait;
use Webkul\MasterDataManagement\Enums\GrantAction;
use Webkul\MasterDataManagement\Enums\GrantPrincipal;
use Webkul\MasterDataManagement\Enums\RecordAction;
use Webkul\MasterDataManagement\Export\RecordRowBuilder;
use Webkul\MasterDataManagement\Models\ChangeRequest;
use Webkul\MasterDataManagement\Models\Record;
use Webkul\MasterDataManagement\Repositories\AccessGrantRepository;
use Webkul\MasterDataManagement\Repositories\EntityTypeRepository;
use Webkul\MasterDataManagement\Repositories\MatchCandidateRepository;
use Webkul\MasterDataManagement\Repositories\RecordRepository;
use Webkul\MasterDataManagement\Repositories\RoleEntityPermissionRepository;
use Webkul\MasterDataManagement\Repositories\SourceSystemRepository;
use Webkul\MasterDataManagement\Security\AccessGrantResolver;
use Webkul\MasterDataManagement\Security\ChangeApprovals;
use Webkul\MasterDataManagement\Security\EntityTypePermissions;
use Webkul\MasterDataManagement\Services\RecordWriter;
use Webkul\User\Models\Admin;
use Webkul\User\Models\Role;

uses(ApiHelperTrait::class);

beforeEach(function () {
    $types = app(EntityTypeRepository::class);
    $this->writer = app(RecordWriter::class);

    $this->site = $types->create(['code' => $this->uniqueCode('site'), 'status' => 'active', 'code_strategy' => 'source_id', 'is_hierarchical' => true]);
    $this->equipment = $types->create([
        'code'                    => $this->uniqueCode('equipment'),
        'status'                  => 'active',
        'code_strategy'           => 'source_id',
        'is_hierarchical'         => true,
        'is_scoped'               => true,
        'allowed_parent_type_ids' => [$this->site->id],
    ]);
    $this->spare = $types->create(['code' => $this->uniqueCode('spare'), 'status' => 'active', 'code_strategy' => 'source_id']);

    $this->siteA = $this->writer->create($this->site, ['code' => 'SITE-A'], null);
    $this->siteB = $this->writer->create($this->site, ['code' => 'SITE-B'], null);
    $this->eqA1 = $this->writer->create($this->equipment, ['code' => 'EQ-A1', 'parent' => $this->siteA->uuid], null);
    $this->eqA2 = $this->writer->create($this->equipment, ['code' => 'EQ-A2', 'parent' => $this->siteA->uuid], null);
    $this->eqB1 = $this->writer->create($this->equipment, ['code' => 'EQ-B1', 'parent' => $this->siteB->uuid], null);
    $this->writer->create($this->spare, ['code' => 'SP-1'], null);

    $this->grants = app(AccessGrantRepository::class);

    $this->customAdmin = function (array $attributes = []): Admin {
        $role = Role::factory()->create(['permission_type' => 'custom', 'permissions' => [
            'masterdata', 'masterdata.records', 'masterdata.records.view', 'masterdata.records.create',
            'masterdata.records.edit', 'masterdata.records.delete', 'masterdata.approvals', RecordAction::Approve->aclKey(),
        ]]);

        $all = ['view', 'create', 'edit', 'delete', 'merge', 'approve'];

        foreach ([$this->site, $this->equipment, $this->spare] as $type) {
            app(RoleEntityPermissionRepository::class)->syncForEntityType($type->id, [$role->id => $all]);
        }

        app(EntityTypePermissions::class)->flush();

        return Admin::factory()->create($attributes + ['role_id' => $role->id]);
    };

    $this->grant = function (GrantPrincipal $type, int $principalId, Record $root, array $actions, ?int $entityTypeId = null): void {
        $this->grants->create([
            'principal_type' => $type,
            'principal_id'   => $principalId,
            'entity_type_id' => $entityTypeId,
            'record_id'      => $root->id,
            'actions'        => $actions,
        ]);

        app(AccessGrantResolver::class)->flush();
    };

    $this->gridCodes = fn (): array => collect($this->ajaxGrid(route('admin.masterdata.records.index', $this->equipment->code))->assertOk()->json('records'))
        ->pluck('code')->sort()->values()->all();
});

it('limits the record grid, hierarchy and export to a role grant', function () {
    $admin = ($this->customAdmin)();
    ($this->grant)(GrantPrincipal::Role, $admin->role_id, $this->siteA, ['view']);

    $this->loginAsAdmin($admin);

    expect(($this->gridCodes)())->toBe(['EQ-A1', 'EQ-A2']);

    $children = app(RecordRepository::class)->childrenOf($this->siteB->fresh(), 100, $admin);
    expect($children)->toBeEmpty();

    $rows = app(RecordRowBuilder::class)->build($this->equipment, [$this->eqA1->id, $this->eqB1->id], $admin);
    expect(array_column($rows, 'code'))->toBe(['EQ-A1']);
});

it('revokes a role grant when the admin moves to another role', function () {
    $admin = ($this->customAdmin)();
    ($this->grant)(GrantPrincipal::Role, $admin->role_id, $this->siteB, ['view']);

    $this->loginAsAdmin($admin);

    expect(($this->gridCodes)())->toBe(['EQ-B1']);

    $other = ($this->customAdmin)();
    $admin->forceFill(['role_id' => $other->role_id])->save();
    $admin->unsetRelation('role');
    app(AccessGrantResolver::class)->flush();

    expect(($this->gridCodes)())->toBe([]);
});

it('limits access to a direct user grant and unions it with other grants', function () {
    $admin = ($this->customAdmin)();
    ($this->grant)(GrantPrincipal::User, $admin->id, $this->siteA, ['view']);

    $this->loginAsAdmin($admin);

    expect(($this->gridCodes)())->toBe(['EQ-A1', 'EQ-A2']);

    ($this->grant)(GrantPrincipal::Role, $admin->role_id, $this->eqB1, ['view']);

    expect(($this->gridCodes)())->toBe(['EQ-A1', 'EQ-A2', 'EQ-B1']);
});

it('applies an entity type grant only to that type', function () {
    $other = app(EntityTypeRepository::class)->create(['code' => $this->uniqueCode('tool'), 'status' => 'active', 'code_strategy' => 'source_id', 'is_hierarchical' => true, 'is_scoped' => true, 'allowed_parent_type_ids' => [$this->site->id]]);
    $tool = $this->writer->create($other, ['code' => 'TOOL-A', 'parent' => $this->siteA->uuid], null);

    $admin = ($this->customAdmin)();
    app(RoleEntityPermissionRepository::class)->syncForEntityType($other->id, [$admin->role_id => ['view']]);
    app(EntityTypePermissions::class)->flush();
    ($this->grant)(GrantPrincipal::User, $admin->id, $this->siteA, ['view'], $this->equipment->id);

    $repository = app(RecordRepository::class);

    expect($repository->scopedQuery($this->equipment->id, $admin)->pluck('code')->sort()->values()->all())->toBe(['EQ-A1', 'EQ-A2'])
        ->and($repository->scopedQuery($other->id, $admin)->pluck('code')->all())->toBe([])
        ->and($repository->visibleUuids([$tool->uuid, $this->eqA1->uuid], $admin))->toBe([$this->eqA1->uuid]);
});

it('shows a no-grant admin no scoped records but leaves unscoped types open', function () {
    $admin = ($this->customAdmin)();

    $this->loginAsAdmin($admin);

    expect(($this->gridCodes)())->toBe([]);

    $spares = collect($this->ajaxGrid(route('admin.masterdata.records.index', $this->spare->code))->json('records'))->pluck('code')->all();
    expect($spares)->toBe(['SP-1']);

    $sites = collect($this->ajaxGrid(route('admin.masterdata.records.index', $this->site->code))->json('records'))->pluck('code')->sort()->values()->all();
    expect($sites)->toBe(['SITE-A', 'SITE-B']);
});

it('lets a full access role see every scoped record', function () {
    $this->loginWithPermissions('all');

    expect(($this->gridCodes)())->toBe(['EQ-A1', 'EQ-A2', 'EQ-B1']);
});

it('answers 404 for a record page outside the grants and refuses a view only update', function () {
    $admin = ($this->customAdmin)();
    ($this->grant)(GrantPrincipal::User, $admin->id, $this->siteA, ['view']);

    $this->loginAsAdmin($admin);

    $this->get(route('admin.masterdata.records.edit', $this->eqB1->uuid))->assertNotFound();
    $this->get(route('admin.masterdata.records.edit', $this->eqA1->uuid))->assertOk();

    $this->putJson(route('admin.masterdata.records.update', $this->eqA1->uuid), ['revision' => $this->eqA1->revision, 'label' => 'Changed'])
        ->assertForbidden();

    expect(Gate::forUser($admin)->allows('update', $this->eqA1->fresh()))->toBeFalse();

    ($this->grant)(GrantPrincipal::User, $admin->id, $this->siteA, ['edit']);

    expect(Gate::forUser($admin)->allows('update', $this->eqA1->fresh()))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('update', $this->eqB1->fresh()))->toBeFalse();
});

it('creates only below a parent inside an edit grant', function () {
    $admin = ($this->customAdmin)();
    ($this->grant)(GrantPrincipal::User, $admin->id, $this->siteA, ['edit']);

    $created = $this->writer->create($this->equipment, ['code' => 'EQ-A3', 'parent' => $this->siteA->uuid], $admin);
    expect($created->exists)->toBeTrue();

    expect(fn () => $this->writer->create($this->equipment, ['code' => 'EQ-B2', 'parent' => $this->siteB->uuid], $admin))
        ->toThrow(ValidationException::class);

    expect(fn () => $this->writer->create($this->equipment, ['code' => 'EQ-ROOT'], $admin))
        ->toThrow(ValidationException::class);

    expect(fn () => $this->writer->update($created->fresh(), ['parent' => $this->siteB->uuid], $created->revision, $admin))
        ->toThrow(ValidationException::class);
});

it('scopes the REST list and record reads by the api key admin', function () {
    $headers = $this->getAuthenticationHeaders();
    $robot = Admin::query()->latest('id')->firstOrFail();
    $role = Role::factory()->create(['permission_type' => 'custom', 'permissions' => []]);
    $robot->forceFill(['type' => 'api', 'role_id' => $role->id])->save();
    app(RoleEntityPermissionRepository::class)->syncForEntityType($this->equipment->id, [$role->id => ['view', 'edit']]);
    app(EntityTypePermissions::class)->flush();
    ($this->grant)(GrantPrincipal::User, $robot->id, $this->siteB, ['view']);

    $codes = collect($this->getJson(route('admin.api.masterdata.records.index', ['type' => $this->equipment->code]), $headers)->assertOk()->json('data'))
        ->pluck('code')->all();

    expect($codes)->toBe(['EQ-B1']);

    $this->getJson(route('admin.api.masterdata.records.show', [$this->equipment->code, $this->eqA1->uuid]), $headers)->assertNotFound();
    $this->getJson(route('admin.api.masterdata.records.show', [$this->equipment->code, $this->eqB1->uuid]), $headers)->assertOk();

    $this->putJson(route('admin.api.masterdata.records.update', [$this->equipment->code, $this->eqB1->uuid]), ['label' => 'X'], $headers + ['If-Match' => '"'.$this->eqB1->revision.'"'])
        ->assertForbidden();
});

it('limits approvers and the approvals inbox to approve and view grants', function () {
    $this->equipment->update(['approval_required' => true]);

    $requester = ($this->customAdmin)();
    app(RoleEntityPermissionRepository::class)->syncForEntityType($this->equipment->id, [$requester->role_id => ['view', 'create', 'edit']]);
    app(EntityTypePermissions::class)->flush();
    $approverA = ($this->customAdmin)();
    $approverB = ($this->customAdmin)();
    ($this->grant)(GrantPrincipal::User, $requester->id, $this->siteA, ['edit']);
    ($this->grant)(GrantPrincipal::User, $requester->id, $this->siteB, ['edit']);
    ($this->grant)(GrantPrincipal::User, $approverA->id, $this->siteA, ['approve']);
    ($this->grant)(GrantPrincipal::User, $approverB->id, $this->siteB, ['approve']);

    $this->writer->update($this->eqA1->fresh(), ['label' => 'Pending A'], $this->eqA1->revision, $requester);
    $this->writer->create($this->equipment, ['code' => 'EQ-B9', 'parent' => $this->siteB->uuid], $requester);

    $onA = ChangeRequest::query()->where('record_id', $this->eqA1->id)->sole();
    $createOnB = ChangeRequest::query()->where('entity_type_id', $this->equipment->id)->whereNull('record_id')->sole();

    $approvals = app(ChangeApprovals::class);

    expect($approvals->approverIds($onA))->toContain($approverA->id)->not->toContain($approverB->id)
        ->and($approvals->approverIds($createOnB))->toContain($approverB->id)->not->toContain($approverA->id)
        ->and($approvals->canDecide($approverB, $onA))->toBeFalse()
        ->and($approvals->canDecide($approverA, $onA))->toBeTrue();

    $this->loginAsAdmin($approverA);

    $ids = collect($this->ajaxGrid(route('admin.masterdata.approvals.index'))->assertOk()->json('records'))->pluck('id')->map(fn ($id): int => (int) $id)->all();

    expect($ids)->toContain($onA->id)->not->toContain($createOnB->id);

    $this->get(route('admin.masterdata.approvals.show', $createOnB->id))->assertNotFound();
    $this->get(route('admin.masterdata.approvals.show', $onA->id))->assertOk();
});

it('shows pending create requests in the approvals inbox to a full access admin', function () {
    $this->equipment->update(['approval_required' => true]);
    $this->spare->update(['approval_required' => true]);

    $requester = ($this->customAdmin)();
    app(RoleEntityPermissionRepository::class)->syncForEntityType($this->equipment->id, [$requester->role_id => ['view', 'create', 'edit']]);
    app(RoleEntityPermissionRepository::class)->syncForEntityType($this->spare->id, [$requester->role_id => ['view', 'create', 'edit']]);
    app(EntityTypePermissions::class)->flush();
    ($this->grant)(GrantPrincipal::User, $requester->id, $this->siteB, ['edit']);

    $this->writer->create($this->equipment, ['code' => 'EQ-B9', 'parent' => $this->siteB->uuid], $requester);
    $this->writer->create($this->spare, ['code' => 'SP-9'], $requester);

    $creates = ChangeRequest::query()->whereNull('record_id')->whereIn('entity_type_id', [$this->equipment->id, $this->spare->id])->pluck('id')->all();

    $this->loginAsAdmin();

    $ids = collect($this->ajaxGrid(route('admin.masterdata.approvals.index'))->assertOk()->json('records'))->pluck('id')->map(fn ($id): int => (int) $id)->all();

    expect($creates)->toHaveCount(2)
        ->and($ids)->toContain(...$creates);
});

it('hides match inbox rows matched to a record outside the grants', function () {
    $admin = ($this->customAdmin)(['status' => 1]);
    $admin->role->update(['permissions' => [...$admin->role->permissions, 'masterdata.matching']]);
    ($this->grant)(GrantPrincipal::User, $admin->id, $this->siteA, ['view']);

    $system = app(SourceSystemRepository::class)->create(['code' => $this->uniqueCode('erp'), 'name' => 'ERP', 'type' => 'erp']);
    $candidates = app(MatchCandidateRepository::class);
    $onA = $candidates->create(['source_system_id' => $system->id, 'entity_type_id' => $this->equipment->id, 'external_id' => 'X-A', 'status' => 'matched', 'matched_record_id' => $this->eqA1->id]);
    $onB = $candidates->create(['source_system_id' => $system->id, 'entity_type_id' => $this->equipment->id, 'external_id' => 'X-B', 'status' => 'matched', 'matched_record_id' => $this->eqB1->id]);

    $this->loginAsAdmin($admin);

    $ids = collect($this->ajaxGrid(route('admin.masterdata.matching.index'))->assertOk()->json('records'))->pluck('id')->map(fn ($id): int => (int) $id)->all();

    expect($ids)->toContain($onA->id)->not->toContain($onB->id);

    $this->get(route('admin.masterdata.matching.show', $onB->id))->assertNotFound();
});

it('treats edit and approve as implying view', function () {
    expect(GrantAction::Edit->implied())->toContain(GrantAction::View)
        ->and(GrantAction::Approve->implied())->toContain(GrantAction::View)
        ->and(GrantAction::View->implied())->toBe([GrantAction::View]);
});

it('holds an approver write to a scoped record outside their approve grants', function () {
    $this->equipment->update(['approval_required' => true]);

    $approver = ($this->customAdmin)();
    ($this->grant)(GrantPrincipal::User, $approver->id, $this->siteA, ['edit']);

    $result = $this->writer->submitUpdate($this->eqA1->fresh(), ['label' => 'Held'], $this->eqA1->revision, $approver);

    expect($result->gate->isPending())->toBeTrue()
        ->and($this->eqA1->fresh()->label)->not->toBe('Held')
        ->and(ChangeRequest::query()->where('record_id', $this->eqA1->id)->exists())->toBeTrue();
});

it('writes an approver change directly inside their approve grant', function () {
    $this->equipment->update(['approval_required' => true]);

    $approver = ($this->customAdmin)();
    ($this->grant)(GrantPrincipal::User, $approver->id, $this->siteA, ['edit', 'approve']);

    $this->writer->update($this->eqA1->fresh(), ['label' => 'Direct'], $this->eqA1->revision, $approver);

    expect($this->eqA1->fresh()->label)->toBe('Direct')
        ->and(ChangeRequest::query()->where('record_id', $this->eqA1->id)->exists())->toBeFalse();
});

it('keeps the approver skip on an unscoped type without grants', function () {
    $this->site->update(['approval_required' => true]);

    $approver = ($this->customAdmin)();

    $this->writer->update($this->siteA->fresh(), ['label' => 'Direct'], $this->siteA->revision, $approver);

    expect($this->siteA->fresh()->label)->toBe('Direct')
        ->and(ChangeRequest::query()->where('record_id', $this->siteA->id)->exists())->toBeFalse();
});

it('holds an approver create below a parent outside their approve grants', function () {
    $this->equipment->update(['approval_required' => true]);

    $approver = ($this->customAdmin)();
    ($this->grant)(GrantPrincipal::User, $approver->id, $this->siteA, ['edit', 'approve']);
    ($this->grant)(GrantPrincipal::User, $approver->id, $this->siteB, ['edit']);

    $direct = $this->writer->create($this->equipment, ['code' => 'EQ-A9', 'parent' => $this->siteA->uuid], $approver);
    $held = $this->writer->submitCreate($this->equipment, ['code' => 'EQ-B9', 'parent' => $this->siteB->uuid], $approver);

    expect($direct->exists)->toBeTrue()
        ->and($held->gate->isPending())->toBeTrue()
        ->and(ChangeRequest::query()->whereNull('record_id')->where('parent_id', $this->siteB->id)->exists())->toBeTrue();
});
