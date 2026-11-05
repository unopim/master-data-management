<?php

use Webkul\MasterDataManagement\Enums\GrantPrincipal;
use Webkul\MasterDataManagement\Models\AccessGrant;
use Webkul\MasterDataManagement\Repositories\AccessGrantRepository;
use Webkul\MasterDataManagement\Repositories\EntityTypeRepository;
use Webkul\MasterDataManagement\Services\RecordWriter;
use Webkul\User\Models\Admin;
use Webkul\User\Models\Role;

beforeEach(function () {
    $this->site = app(EntityTypeRepository::class)->create(['code' => $this->uniqueCode('site'), 'status' => 'active', 'code_strategy' => 'source_id']);
    $this->plant = app(RecordWriter::class)->create($this->site, ['code' => 'PLANT-PUNE', 'label' => 'Pune Plant'], null);
    $this->role = Role::factory()->create(['name' => 'Pune Steward', 'permission_type' => 'custom', 'permissions' => ['dashboard']]);
});

it('lists, adds, updates and deletes data access grants', function () {
    $this->loginWithPermissions('all');

    $this->get(route('admin.masterdata.access.index'))->assertOk();
    $this->get(route('admin.masterdata.access.create'))->assertOk()->assertSee(trans('masterdata::app.access.form.root'));

    $this->postJson(route('admin.masterdata.access.store'), [
        'principal_type' => 'role',
        'role_id'        => $this->role->id,
        'record'         => $this->plant->uuid,
        'actions'        => ['edit'],
    ])->assertOk();

    $grant = AccessGrant::query()->where('principal_type', 'role')->where('principal_id', $this->role->id)->sole();

    expect($grant->record_id)->toBe($this->plant->id)
        ->and($grant->entity_type_id)->toBeNull()
        ->and($grant->actions)->toBe(['edit']);

    $rows = $this->ajaxGrid(route('admin.masterdata.access.index'))->assertOk()->json('records');
    $row = collect($rows)->firstWhere('id', $grant->id);

    expect($row['principal'])->toBe('Pune Steward')
        ->and($row['record_code'])->toContain('PLANT-PUNE');

    $this->get(route('admin.masterdata.access.edit', $grant->id))->assertOk();

    $user = Admin::factory()->create();

    $this->putJson(route('admin.masterdata.access.update', $grant->id), [
        'principal_type' => 'user',
        'admin_id'       => $user->id,
        'entity_type_id' => $this->site->id,
        'record'         => $this->plant->uuid,
        'actions'        => ['approve', 'view'],
    ])->assertOk();

    expect($grant->fresh()->principal_type)->toBe(GrantPrincipal::User)
        ->and($grant->fresh()->principal_id)->toBe($user->id)
        ->and($grant->fresh()->entity_type_id)->toBe($this->site->id)
        ->and($grant->fresh()->actions)->toBe(['view', 'approve']);

    $this->deleteJson(route('admin.masterdata.access.destroy', $grant->id))->assertOk();

    expect(AccessGrant::query()->whereKey($grant->id)->exists())->toBeFalse();
});

it('validates the principal, root and actions of a grant', function () {
    $this->loginWithPermissions('all');

    $this->postJson(route('admin.masterdata.access.store'), ['principal_type' => 'role', 'record' => '00000000-0000-0000-0000-000000000000', 'actions' => []])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['role_id', 'actions']);

    $this->postJson(route('admin.masterdata.access.store'), ['principal_type' => 'user', 'admin_id' => Admin::factory()->create()->id, 'record' => '00000000-0000-0000-0000-000000000000', 'actions' => ['view']])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['record']);

    $this->postJson(route('admin.masterdata.access.store'), ['principal_type' => 'team', 'record' => $this->plant->uuid, 'actions' => ['delete']])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['principal_type', 'actions.0']);
});

it('forbids the data access screen without its acl', function () {
    $this->loginWithPermissions('custom', ['dashboard', 'masterdata']);

    $this->get(route('admin.masterdata.access.index'))->assertForbidden();
    $this->postJson(route('admin.masterdata.access.store'), [])->assertForbidden();
});

it('adds and removes role grants from the role form card', function () {
    $this->loginWithPermissions('all');

    $this->get(route('admin.settings.roles.edit', $this->role->id))
        ->assertOk()
        ->assertSee(trans('masterdata::app.access.role-card.title'));

    $payload = [
        'name'            => 'Pune Steward',
        'permission_type' => 'custom',
        'permissions'     => ['dashboard'],
    ];

    $this->putJson(route('admin.settings.roles.update', $this->role->id), $payload + [
        'masterdata_access' => ['present' => 1, 'record' => $this->plant->uuid, 'actions' => ['view']],
    ])->assertOk();

    $grant = AccessGrant::query()->where('principal_type', 'role')->where('principal_id', $this->role->id)->sole();

    expect($grant->actions)->toBe(['view']);

    $this->get(route('admin.settings.roles.edit', $this->role->id))->assertSee('PLANT-PUNE');

    $this->putJson(route('admin.settings.roles.update', $this->role->id), $payload + [
        'masterdata_access' => ['present' => 1, 'remove' => [$grant->id => $grant->id]],
    ])->assertOk();

    expect(AccessGrant::query()->whereKey($grant->id)->exists())->toBeFalse();
});

it('refuses a role save whose card grant has no visible root and keeps grants when the card is absent', function () {
    $this->loginWithPermissions('all');

    app(AccessGrantRepository::class)->create(['principal_type' => GrantPrincipal::Role, 'principal_id' => $this->role->id, 'record_id' => $this->plant->id, 'actions' => ['view']]);

    $this->putJson(route('admin.settings.roles.update', $this->role->id), [
        'name'              => 'Renamed',
        'permission_type'   => 'custom',
        'permissions'       => ['dashboard'],
        'masterdata_access' => ['present' => 1, 'record' => '00000000-0000-0000-0000-000000000000', 'actions' => ['view']],
    ])->assertUnprocessable()->assertJsonValidationErrors(['masterdata_access.record']);

    expect($this->role->fresh()->name)->toBe('Pune Steward');

    $this->putJson(route('admin.settings.roles.update', $this->role->id), [
        'name'            => 'Renamed',
        'permission_type' => 'custom',
        'permissions'     => ['dashboard'],
    ])->assertOk();

    expect(AccessGrant::query()->where('principal_id', $this->role->id)->where('principal_type', 'role')->count())->toBe(1);
});

it('saves the scoped flag from the entity type form', function () {
    $this->loginWithPermissions('all');

    $this->get(route('admin.masterdata.entity_types.edit', $this->site->id))->assertOk()->assertSee(trans('masterdata::app.access.entity-type.is-scoped'));

    $this->putJson(route('admin.masterdata.entity_types.update', $this->site->id), ['is_scoped' => 1])->assertOk();

    expect($this->site->fresh()->is_scoped)->toBeTrue();
});

it('deletes the grants of a deleted role and leaves other principals alone', function () {
    $this->loginWithPermissions('all');

    $grants = app(AccessGrantRepository::class);
    $other = Role::factory()->create(['permission_type' => 'custom', 'permissions' => ['dashboard']]);

    $grants->create(['principal_type' => GrantPrincipal::Role, 'principal_id' => $this->role->id, 'record_id' => $this->plant->id, 'actions' => ['view']]);
    $kept = [
        $grants->create(['principal_type' => GrantPrincipal::Role, 'principal_id' => $other->id, 'record_id' => $this->plant->id, 'actions' => ['view']])->id,
        $grants->create(['principal_type' => GrantPrincipal::User, 'principal_id' => $this->role->id, 'record_id' => $this->plant->id, 'actions' => ['view']])->id,
    ];

    $this->deleteJson(route('admin.settings.roles.delete', $this->role->id))->assertOk();

    expect(AccessGrant::query()->where('principal_type', 'role')->where('principal_id', $this->role->id)->exists())->toBeFalse()
        ->and(AccessGrant::query()->whereKey($kept)->count())->toBe(2);
});

it('deletes the grants of a deleted admin and leaves other principals alone', function () {
    $this->loginWithPermissions('all');

    $grants = app(AccessGrantRepository::class);
    $user = Admin::factory()->create();
    $other = Admin::factory()->create();

    $grants->create(['principal_type' => GrantPrincipal::User, 'principal_id' => $user->id, 'record_id' => $this->plant->id, 'actions' => ['edit']]);
    $kept = [
        $grants->create(['principal_type' => GrantPrincipal::User, 'principal_id' => $other->id, 'record_id' => $this->plant->id, 'actions' => ['edit']])->id,
        $grants->create(['principal_type' => GrantPrincipal::Role, 'principal_id' => $user->id, 'record_id' => $this->plant->id, 'actions' => ['edit']])->id,
    ];

    $this->deleteJson(route('admin.settings.users.delete', $user->id))->assertOk();

    expect(AccessGrant::query()->where('principal_type', 'user')->where('principal_id', $user->id)->exists())->toBeFalse()
        ->and(AccessGrant::query()->whereKey($kept)->count())->toBe(2);
});

it('deletes grants left by roles and admins deleted before the cleanup listener', function () {
    $grants = app(AccessGrantRepository::class);
    $role = Role::factory()->create();
    $user = Admin::factory()->create();
    $goneId = (int) max(Role::query()->max('id'), Admin::query()->max('id')) + 1000;

    $kept = [
        $grants->create(['principal_type' => GrantPrincipal::Role, 'principal_id' => $role->id, 'record_id' => $this->plant->id, 'actions' => ['view']])->id,
        $grants->create(['principal_type' => GrantPrincipal::User, 'principal_id' => $user->id, 'record_id' => $this->plant->id, 'actions' => ['view']])->id,
    ];
    $orphans = [
        $grants->create(['principal_type' => GrantPrincipal::Role, 'principal_id' => $goneId, 'record_id' => $this->plant->id, 'actions' => ['view']])->id,
        $grants->create(['principal_type' => GrantPrincipal::User, 'principal_id' => $goneId, 'record_id' => $this->plant->id, 'actions' => ['view']])->id,
    ];

    (require __DIR__.'/../../../src/Database/Migrations/2026_11_11_000002_delete_orphan_mdm_access_grants.php')->up();

    expect(AccessGrant::query()->whereKey($kept)->count())->toBe(2)
        ->and(AccessGrant::query()->whereKey($orphans)->exists())->toBeFalse();
});
