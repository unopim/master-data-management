<?php

use Webkul\AdminApi\Tests\Traits\ApiHelperTrait;
use Webkul\MasterDataManagement\Enums\GrantPrincipal;
use Webkul\MasterDataManagement\Models\Hierarchy;
use Webkul\MasterDataManagement\Models\HierarchyNode;
use Webkul\MasterDataManagement\Repositories\AccessGrantRepository;
use Webkul\MasterDataManagement\Repositories\EntityTypeRepository;
use Webkul\MasterDataManagement\Repositories\HierarchyRepository;
use Webkul\MasterDataManagement\Repositories\RoleEntityPermissionRepository;
use Webkul\MasterDataManagement\Security\AccessGrantResolver;
use Webkul\MasterDataManagement\Security\EntityTypePermissions;
use Webkul\MasterDataManagement\Services\HierarchyService;
use Webkul\MasterDataManagement\Services\RecordWriter;
use Webkul\User\Models\Admin;
use Webkul\User\Models\Role;

uses(ApiHelperTrait::class);

beforeEach(function () {
    $this->type = app(EntityTypeRepository::class)->create(['code' => $this->uniqueCode('site'), 'status' => 'active', 'code_strategy' => 'source_id', 'is_hierarchical' => true]);
    $this->writer = app(RecordWriter::class);
    $this->hierarchies = app(HierarchyRepository::class);
    $this->storeUrl = route('admin.masterdata.hierarchies.store', $this->type->id);

    $this->alternate = fn (string $code = 'sales'): Hierarchy => $this->hierarchies->create(['entity_type_id' => $this->type->id, 'code' => $code, 'en_US' => ['label' => ucfirst($code)]])->fresh();
});

it('shows the hierarchies tab with the primary hierarchy in its grid', function () {
    $this->loginAsAdmin();

    $this->get(route('admin.masterdata.entity_types.edit', ['id' => $this->type->id, 'hierarchies' => 1]))
        ->assertOk()
        ->assertSee(trans('masterdata::app.hierarchies.title'))
        ->assertSee('v-masterdata-hierarchies', false);

    $this->ajaxGrid(route('admin.masterdata.hierarchies.index', $this->type->id))
        ->assertOk()
        ->assertJsonPath('records.0.code', Hierarchy::PRIMARY_CODE);
});

it('creates, updates and deletes an alternate hierarchy', function () {
    $this->loginAsAdmin();

    $this->postJson($this->storeUrl, ['code' => 'sales', 'label' => ['en_US' => 'Sales Territories']])
        ->assertOk()
        ->assertJsonStructure(['redirect_url', 'message']);

    $hierarchy = Hierarchy::query()->where('entity_type_id', $this->type->id)->where('code', 'sales')->sole();

    expect($hierarchy->is_primary)->toBeFalse()
        ->and($hierarchy->translate('en_US')->label)->toBe('Sales Territories');

    $this->putJson(route('admin.masterdata.hierarchies.update', [$this->type->id, $hierarchy->id]), ['label' => ['en_US' => 'Sales']])->assertOk();

    expect($hierarchy->fresh()->translate('en_US')->label)->toBe('Sales');

    $this->deleteJson(route('admin.masterdata.hierarchies.destroy', [$this->type->id, $hierarchy->id]))->assertOk();

    expect(Hierarchy::query()->whereKey($hierarchy->id)->exists())->toBeFalse();
});

it('rejects invalid hierarchies and keeps the primary one', function () {
    $this->loginAsAdmin();

    ($this->alternate)('sales');

    $this->postJson($this->storeUrl, ['code' => 'sales', 'label' => ['en_US' => 'Again']])->assertJsonValidationErrors('code');
    $this->postJson($this->storeUrl, ['code' => Hierarchy::PRIMARY_CODE, 'label' => ['en_US' => 'Main']])->assertJsonValidationErrors('code');
    $this->postJson($this->storeUrl, ['code' => 'Bad Code', 'label' => ['en_US' => 'Bad']])->assertJsonValidationErrors('code');
    $this->postJson($this->storeUrl, ['code' => 'nolabel'])->assertJsonValidationErrors('label');

    $primary = app(HierarchyService::class)->ensurePrimary($this->type);

    $this->deleteJson(route('admin.masterdata.hierarchies.destroy', [$this->type->id, $primary->id]))->assertUnprocessable();

    expect(Hierarchy::query()->whereKey($primary->id)->exists())->toBeTrue();
});

it('denies the hierarchy routes without the entity type edit permission', function () {
    $this->loginWithPermissions('custom', ['dashboard', 'masterdata', 'masterdata.entity_types']);

    $this->postJson($this->storeUrl, ['code' => 'sales', 'label' => ['en_US' => 'Sales']])->assertForbidden();

    $this->getJson(route('admin.masterdata.hierarchies.index', $this->type->id))->assertForbidden();

    expect(Hierarchy::query()->where('entity_type_id', $this->type->id)->where('code', 'sales')->exists())->toBeFalse();
});

it('places and removes a record in an alternate hierarchy from the record page', function () {
    $this->loginAsAdmin();

    $hierarchy = ($this->alternate)();
    $north = $this->writer->create($this->type, ['code' => 'NORTH'], null);
    $site = $this->writer->create($this->type, ['code' => 'SITE-1'], null);

    $this->get(route('admin.masterdata.records.edit', $site->uuid))
        ->assertOk()
        ->assertSee(trans('masterdata::app.hierarchies.record.title'))
        ->assertSee(route('admin.masterdata.records.hierarchies.update', [$site->uuid, $hierarchy->id]), false);

    $this->getJson(route('admin.masterdata.records.hierarchies.options', [$site->uuid, $hierarchy->id]))
        ->assertOk()
        ->assertJsonFragment(['id' => $north->uuid])
        ->assertJsonMissing(['id' => $site->uuid]);

    $this->putJson(route('admin.masterdata.records.hierarchies.update', [$site->uuid, $hierarchy->id]), ['parent' => $north->uuid])
        ->assertOk()
        ->assertJsonStructure(['redirect_url', 'message']);

    expect(HierarchyNode::query()->where('hierarchy_id', $hierarchy->id)->where('record_id', $site->id)->value('parent_id'))->toBe($north->id);

    $this->putJson(route('admin.masterdata.records.hierarchies.update', [$north->uuid, $hierarchy->id]), ['parent' => $site->uuid])
        ->assertJsonValidationErrors('parent');

    $this->deleteJson(route('admin.masterdata.records.hierarchies.destroy', [$site->uuid, $hierarchy->id]))->assertOk();

    expect(HierarchyNode::query()->where('hierarchy_id', $hierarchy->id)->where('record_id', $site->id)->exists())->toBeFalse();
});

it('denies placing records without the record edit permission', function () {
    $hierarchy = ($this->alternate)();
    $site = $this->writer->create($this->type, ['code' => 'SITE-1'], null);

    $this->loginWithPermissions('custom', ['dashboard', 'masterdata', 'masterdata.records', 'masterdata.records.view']);

    $this->putJson(route('admin.masterdata.records.hierarchies.update', [$site->uuid, $hierarchy->id]), ['parent' => null])->assertForbidden();

    expect(HierarchyNode::query()->where('hierarchy_id', $hierarchy->id)->exists())->toBeFalse();
});

it('shows the tree of a hierarchy one level at a time', function () {
    $this->loginAsAdmin();

    $root = $this->writer->create($this->type, ['code' => 'ROOT'], null);
    $child = $this->writer->create($this->type, ['code' => 'CHILD', 'parent' => $root->uuid], null);

    $hierarchy = ($this->alternate)();
    app(HierarchyService::class)->place($hierarchy, $root, $child);

    $primary = $this->hierarchies->primaryFor($this->type->id);

    $this->get(route('admin.masterdata.records.tree', ['entity_type' => $this->type->code, 'hierarchy' => $hierarchy->id]))
        ->assertOk()
        ->assertSee('v-masterdata-tree-level', false);

    $nodesUrl = route('admin.masterdata.records.tree.nodes', $this->type->code);

    $this->getJson($nodesUrl.'?hierarchy='.$primary->id)
        ->assertOk()
        ->assertJsonPath('nodes.0.code', 'ROOT')
        ->assertJsonPath('nodes.0.children', 1)
        ->assertJsonCount(1, 'nodes');

    $this->getJson($nodesUrl.'?hierarchy='.$primary->id.'&parent='.$root->uuid)
        ->assertOk()
        ->assertJsonPath('nodes.0.code', 'CHILD');

    $this->getJson($nodesUrl.'?hierarchy='.$hierarchy->id)
        ->assertOk()
        ->assertJsonPath('nodes.0.code', 'CHILD')
        ->assertJsonPath('nodes.0.children', 1);
});

it('keeps the tree within the subtrees granted to a scoped admin', function () {
    $scoped = app(EntityTypeRepository::class)->create(['code' => $this->uniqueCode('plant'), 'status' => 'active', 'code_strategy' => 'source_id', 'is_hierarchical' => true, 'is_scoped' => true]);

    $plantA = $this->writer->create($scoped, ['code' => 'PLANT-A'], null);
    $this->writer->create($scoped, ['code' => 'PLANT-B'], null);
    $this->writer->create($scoped, ['code' => 'UNIT-A1', 'parent' => $plantA->uuid], null);

    $role = Role::factory()->create(['permission_type' => 'custom', 'permissions' => ['masterdata', 'masterdata.records', 'masterdata.records.view', 'masterdata.records.edit']]);
    app(RoleEntityPermissionRepository::class)->syncForEntityType($scoped->id, [$role->id => ['view', 'edit']]);
    app(EntityTypePermissions::class)->flush();

    $admin = Admin::factory()->create(['role_id' => $role->id]);

    app(AccessGrantRepository::class)->create([
        'principal_type' => GrantPrincipal::User,
        'principal_id'   => $admin->id,
        'entity_type_id' => null,
        'record_id'      => $plantA->id,
        'actions'        => ['view', 'edit'],
    ]);
    app(AccessGrantResolver::class)->flush();

    $primary = app(HierarchyService::class)->ensurePrimary($scoped);

    $this->loginAsAdmin($admin);

    $codes = collect($this->getJson(route('admin.masterdata.records.tree.nodes', $scoped->code).'?hierarchy='.$primary->id)->assertOk()->json('nodes'))->pluck('code')->all();

    expect($codes)->toBe(['PLANT-A']);
});
