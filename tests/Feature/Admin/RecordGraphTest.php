<?php

use Illuminate\Support\Facades\DB;
use Webkul\MasterDataManagement\Enums\GrantPrincipal;
use Webkul\MasterDataManagement\Models\Record;
use Webkul\MasterDataManagement\Repositories\AccessGrantRepository;
use Webkul\MasterDataManagement\Repositories\EntityTypeRepository;
use Webkul\MasterDataManagement\Repositories\RelationRepository;
use Webkul\MasterDataManagement\Repositories\RelationTypeRepository;
use Webkul\MasterDataManagement\Repositories\RoleEntityPermissionRepository;
use Webkul\MasterDataManagement\Repositories\SourceSystemRepository;
use Webkul\MasterDataManagement\Security\AccessGrantResolver;
use Webkul\MasterDataManagement\Security\EntityTypePermissions;
use Webkul\MasterDataManagement\Services\RecordWriter;
use Webkul\MasterDataManagement\Services\XrefService;
use Webkul\User\Models\Admin;
use Webkul\User\Models\Role;

beforeEach(function () {
    $this->type = app(EntityTypeRepository::class)->create(['code' => $this->uniqueCode('site'), 'status' => 'active', 'code_strategy' => 'source_id', 'is_hierarchical' => true]);
    $this->writer = app(RecordWriter::class);

    $this->relationType = app(RelationTypeRepository::class)->create([
        'code'                => $this->uniqueCode('supplies'),
        'from_entity_type_id' => $this->type->id,
        'to_entity_type_id'   => $this->type->id,
        'cardinality'         => 'many',
        'en_US'               => ['label' => 'Supplies'],
    ]);

    $this->relate = fn (Record $from, Record $to) => app(RelationRepository::class)->create([
        'relation_type_id' => $this->relationType->id,
        'from_record_id'   => $from->id,
        'to_record_id'     => $to->id,
    ]);

    $this->record = fn (string $code, ?Record $parent = null): Record => $this->writer->create($this->type, ['code' => $code] + ($parent instanceof Record ? ['parent' => $parent->uuid] : []), null);

    $this->graphUrl = fn (Record $record, array $query = []): string => route('admin.masterdata.records.graph', ['uuid' => $record->uuid] + $query);
});

it('returns the relations, hierarchy and source ids around a record', function () {
    $this->loginAsAdmin();

    $parent = ($this->record)('PARENT');
    $center = ($this->record)('CENTER', $parent);
    $child = ($this->record)('CHILD', $center);
    $partner = ($this->record)('PARTNER');
    $far = ($this->record)('FAR');

    ($this->relate)($center, $partner);
    ($this->relate)($partner, $far);

    $erp = app(SourceSystemRepository::class)->create(['code' => $this->uniqueCode('erp'), 'name' => 'ERP', 'type' => 'erp']);
    app(XrefService::class)->attach($center, $erp, 'S-1');

    $response = $this->getJson(($this->graphUrl)($center))->assertOk();

    $codes = collect($response->json('nodes'))->pluck('code')->all();

    expect($response->json('center'))->toBe($center->uuid)
        ->and($codes)->toContain('CENTER', 'PARENT', 'CHILD', 'PARTNER', 'S-1')
        ->and($codes)->not->toContain('FAR');

    $edges = collect($response->json('edges'));

    expect($edges->firstWhere('kind', 'relation'))->toMatchArray(['source' => $center->uuid, 'target' => $partner->uuid, 'label' => 'Supplies'])
        ->and($edges->where('kind', 'parent')->pluck('target')->all())->toContain($center->uuid, $child->uuid)
        ->and($edges->where('kind', 'source')->count())->toBe(1);

    $twoHops = collect($this->getJson(($this->graphUrl)($center, ['hops' => 2]))->assertOk()->json('nodes'))->pluck('code');

    expect($twoHops)->toContain('FAR');
});

it('stops at the node limit and says the graph was cut', function () {
    $this->loginAsAdmin();

    $center = ($this->record)('CENTER');

    foreach (range(1, 6) as $i) {
        ($this->relate)($center, ($this->record)('N-'.$i));
    }

    $response = $this->getJson(($this->graphUrl)($center, ['limit' => 4]))->assertOk();

    expect($response->json('nodes'))->toHaveCount(4)
        ->and($response->json('truncated'))->toBeTrue();

    $this->getJson(($this->graphUrl)($center, ['limit' => 1000]))->assertJsonValidationErrors('limit');
    $this->getJson(($this->graphUrl)($center, ['hops' => 3]))->assertJsonValidationErrors('hops');
});

it('reads a graph in a bounded number of queries whatever its fan-out', function () {
    $this->loginAsAdmin();

    $count = function (int $fanOut): int {
        $center = ($this->record)('C-'.$fanOut);

        foreach (range(1, $fanOut) as $i) {
            $neighbour = ($this->record)('R-'.$fanOut.'-'.$i, $center);

            ($this->relate)($center, $neighbour);
            ($this->relate)($neighbour, ($this->record)('F-'.$fanOut.'-'.$i));
        }

        $url = ($this->graphUrl)($center, ['hops' => 2]);

        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->getJson($url)->assertOk();

        $queries = count(DB::getQueryLog());

        DB::disableQueryLog();

        return $queries;
    };

    $count(1);

    $small = $count(2);
    $large = $count(12);

    expect($large)->toBe($small)
        ->and($large)->toBeLessThan(30);
});

it('leaves out neighbours outside the subtrees granted to a scoped admin', function () {
    $scoped = app(EntityTypeRepository::class)->create(['code' => $this->uniqueCode('plant'), 'status' => 'active', 'code_strategy' => 'source_id', 'is_hierarchical' => true, 'is_scoped' => true]);

    $plantA = $this->writer->create($scoped, ['code' => 'PLANT-A'], null);
    $plantB = $this->writer->create($scoped, ['code' => 'PLANT-B'], null);
    $unit = $this->writer->create($scoped, ['code' => 'UNIT-A1', 'parent' => $plantA->uuid], null);

    $type = app(RelationTypeRepository::class)->create([
        'code'                => $this->uniqueCode('feeds'),
        'from_entity_type_id' => $scoped->id,
        'to_entity_type_id'   => $scoped->id,
        'cardinality'         => 'many',
        'en_US'               => ['label' => 'Feeds'],
    ]);

    app(RelationRepository::class)->create(['relation_type_id' => $type->id, 'from_record_id' => $plantA->id, 'to_record_id' => $plantB->id]);

    $role = Role::factory()->create(['permission_type' => 'custom', 'permissions' => ['masterdata', 'masterdata.records', 'masterdata.records.view']]);
    app(RoleEntityPermissionRepository::class)->syncForEntityType($scoped->id, [$role->id => ['view']]);
    app(EntityTypePermissions::class)->flush();

    $admin = Admin::factory()->create(['role_id' => $role->id]);

    app(AccessGrantRepository::class)->create([
        'principal_type' => GrantPrincipal::User,
        'principal_id'   => $admin->id,
        'entity_type_id' => null,
        'record_id'      => $plantA->id,
        'actions'        => ['view'],
    ]);
    app(AccessGrantResolver::class)->flush();

    $this->loginAsAdmin($admin);

    $response = $this->getJson(route('admin.masterdata.records.graph', $plantA->uuid))->assertOk();

    expect(collect($response->json('nodes'))->pluck('code')->all())->toContain('PLANT-A', 'UNIT-A1')->not->toContain('PLANT-B')
        ->and(collect($response->json('edges'))->where('kind', 'relation')->count())->toBe(0);

    $this->getJson(route('admin.masterdata.records.graph', $plantB->uuid))->assertNotFound();

    expect($unit->exists)->toBeTrue();
});

it('denies the graph without the record view permission', function () {
    $center = ($this->record)('CENTER');

    $this->loginWithPermissions('custom', ['dashboard', 'masterdata', 'masterdata.records']);

    $this->getJson(($this->graphUrl)($center))->assertForbidden();
});

it('shows the graph tab on the record page', function () {
    $this->loginAsAdmin();

    $center = ($this->record)('CENTER');

    $this->get(route('admin.masterdata.records.edit', $center->uuid))
        ->assertOk()
        ->assertSee('v-masterdata-record-graph', false)
        ->assertSee(route('admin.masterdata.records.graph', $center->uuid), false);
});
