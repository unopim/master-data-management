<?php

use Illuminate\Support\Facades\DB;
use Webkul\AdminApi\Tests\Traits\ApiHelperTrait;
use Webkul\MasterDataManagement\Enums\RecordAction;
use Webkul\MasterDataManagement\Models\EntityType;
use Webkul\MasterDataManagement\Repositories\CodeListRepository;
use Webkul\MasterDataManagement\Repositories\CodeRepository;
use Webkul\MasterDataManagement\Repositories\EntityTypeRepository;
use Webkul\MasterDataManagement\Repositories\RoleEntityPermissionRepository;
use Webkul\MasterDataManagement\Repositories\SourceSystemRepository;
use Webkul\MasterDataManagement\Security\EntityTypePermissions;
use Webkul\MasterDataManagement\Services\CodeListPublisher;
use Webkul\MasterDataManagement\Services\FieldService;
use Webkul\MasterDataManagement\Services\RecordWriter;
use Webkul\MasterDataManagement\Services\XrefService;
use Webkul\User\Models\Admin;
use Webkul\User\Models\Role;

uses(ApiHelperTrait::class);

beforeEach(function () {
    $this->types = app(EntityTypeRepository::class);
    $this->type = $this->types->create(['code' => $this->uniqueCode('line'), 'status' => 'active', 'code_strategy' => 'source_id', 'en_US' => ['name' => 'Line']]);
    $this->writer = app(RecordWriter::class);
    $this->fields = app(FieldService::class);

    $this->indexUrl = fn (array $query = [], ?EntityType $type = null): string => route('admin.api.masterdata.records.index', ['type' => ($type ?? $this->type)->code] + $query);

    $this->restrictApiUser = function (array $actionsByType): void {
        $admin = Admin::query()->latest('id')->firstOrFail();
        $role = Role::factory()->create(['permission_type' => 'custom', 'permissions' => []]);
        $admin->forceFill(['type' => 'api', 'role_id' => $role->id])->save();

        foreach ($actionsByType as $typeId => $actions) {
            app(RoleEntityPermissionRepository::class)->syncForEntityType($typeId, [$role->id => $actions]);
        }

        app(EntityTypePermissions::class)->flush();
    };
});

it('paginates records by cursor', function () {
    $uuids = collect(range(1, 6))->map(fn (int $i): string => $this->writer->create($this->type, ['code' => 'L-'.$i], null)->uuid)->all();
    $headers = $this->getAuthenticationHeaders();

    $seen = [];
    $cursor = null;

    foreach (range(1, 3) as $page) {
        $response = $this->getJson(($this->indexUrl)(array_filter(['limit' => 2, 'cursor' => $cursor])), $headers)->assertOk();

        expect($response->json('data'))->toHaveCount(2);

        $seen = [...$seen, ...array_column($response->json('data'), 'uuid')];
        $cursor = $response->json('meta.next_cursor');

        $page < 3 ? expect($cursor)->toBeString() : expect($cursor)->toBeNull();
    }

    expect($seen)->toBe($uuids);
});

it('rejects an out of range limit', function () {
    $this->getJson(($this->indexUrl)(['limit' => 101]), $this->getAuthenticationHeaders())
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['limit']);
});

it('filters records by a searchable field through the index table', function () {
    $this->fields->create($this->type, ['code' => 'serial', 'type' => 'text', 'is_searchable' => true]);
    $this->fields->create($this->type, ['code' => 'notes', 'type' => 'text']);
    $match = $this->writer->create($this->type, ['code' => 'A', 'values' => ['common' => ['serial' => 'SN-1', 'notes' => 'x']]], null);
    $this->writer->create($this->type, ['code' => 'B', 'values' => ['common' => ['serial' => 'SN-2']]], null);
    $headers = $this->getAuthenticationHeaders();

    $this->getJson(($this->indexUrl)(['filter' => ['serial' => 'SN-1']]), $headers)
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.uuid', $match->uuid);

    $this->getJson(($this->indexUrl)(['filter' => ['notes' => 'x']]), $headers)
        ->assertUnprocessable()
        ->assertJsonPath('success', false)
        ->assertJsonPath('code', 'filter_not_searchable');
});

it('filters records by code, status and update time', function () {
    $this->writer->create($this->type, ['code' => 'A'], null);
    $draft = $this->writer->create($this->type, ['code' => 'B', 'status' => 'draft'], null);
    $headers = $this->getAuthenticationHeaders();

    $this->getJson(($this->indexUrl)(['filter' => ['code' => 'B']]), $headers)
        ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.uuid', $draft->uuid);

    $this->getJson(($this->indexUrl)(['filter' => ['status' => 'draft']]), $headers)
        ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.uuid', $draft->uuid);

    $this->getJson(($this->indexUrl)(['filter' => ['updated_since' => now()->addDay()->toIso8601String()]]), $headers)
        ->assertOk()->assertJsonCount(0, 'data');

    $this->getJson(($this->indexUrl)(['filter' => ['status' => 'nope']]), $headers)
        ->assertUnprocessable()->assertJsonValidationErrors(['filter.status']);
});

it('loads xrefs and relations with a constant number of queries', function () {
    $site = $this->types->create(['code' => $this->uniqueCode('site'), 'status' => 'active', 'code_strategy' => 'source_id']);
    $this->fields->create($this->type, ['code' => 'site', 'type' => 'reference', 'reference_entity_type_id' => $site->id]);
    $plant = $this->writer->create($site, ['code' => 'PLANT'], null);
    $erp = app(SourceSystemRepository::class)->create(['code' => $this->uniqueCode('erp'), 'name' => 'ERP', 'type' => 'erp']);
    $xrefs = app(XrefService::class);

    $seed = function (int $count) use ($plant, $erp, $xrefs): void {
        foreach (range(1, $count) as $i) {
            $record = $this->writer->create($this->type, ['code' => uniqid('L'), 'values' => ['common' => ['site' => $plant->uuid]]], null);
            $xrefs->attach($record, $erp, 'E-'.$record->id);
        }
    };

    $headers = $this->getAuthenticationHeaders();

    $countQueries = function () use ($headers): array {
        DB::flushQueryLog();
        DB::enableQueryLog();

        $response = $this->getJson(($this->indexUrl)(['limit' => 100, 'include' => 'xrefs,relations']), $headers)->assertOk();

        DB::disableQueryLog();

        return [count(DB::getQueryLog()), $response];
    };

    $seed(2);
    $countQueries();
    [$few] = $countQueries();

    $seed(48);
    [$many, $response] = $countQueries();

    expect($response->json('data'))->toHaveCount(50)
        ->and($response->json('data.0.relations.site'))->toBe([$plant->uuid])
        ->and($response->json("data.0.ids.{$erp->code}"))->toHaveCount(1)
        ->and($many)->toBe($few);
});

it('omits xrefs and relations unless included', function () {
    $this->writer->create($this->type, ['code' => 'A'], null);

    $response = $this->getJson(($this->indexUrl)(), $this->getAuthenticationHeaders())->assertOk();

    expect($response->json('data.0'))->not->toHaveKey('relations')
        ->and($response->json('data.0'))->not->toHaveKey('ids');
});

it('sends the revision as the etag of a record', function () {
    $record = $this->writer->create($this->type, ['code' => 'A'], null);
    $record = $this->writer->update($record, ['label' => 'Changed'], $record->revision, null);

    $this->getJson(route('admin.api.masterdata.records.show', [$this->type->code, $record->uuid]), $this->getAuthenticationHeaders())
        ->assertOk()
        ->assertHeader('ETag', '"'.$record->revision.'"')
        ->assertJsonPath('data.revision', $record->revision);
});

it('adds relations to a shown record on request', function () {
    $site = $this->types->create(['code' => $this->uniqueCode('site'), 'status' => 'active', 'code_strategy' => 'source_id']);
    $this->fields->create($this->type, ['code' => 'site', 'type' => 'reference', 'reference_entity_type_id' => $site->id]);
    $plant = $this->writer->create($site, ['code' => 'PLANT'], null);
    $record = $this->writer->create($this->type, ['code' => 'A', 'values' => ['common' => ['site' => $plant->uuid]]], null);
    $headers = $this->getAuthenticationHeaders();

    $this->getJson(route('admin.api.masterdata.records.show', [$this->type->code, $record->uuid, 'include' => 'relations']), $headers)
        ->assertOk()
        ->assertJsonPath('data.relations.site', [$plant->uuid])
        ->assertJsonPath('data.references.site', $plant->uuid);

    expect($this->getJson(route('admin.api.masterdata.records.show', [$this->type->code, $record->uuid]), $headers)->json('data'))
        ->not->toHaveKey('relations');
});

it('lists entity types with their fields', function () {
    $parent = $this->types->create(['code' => $this->uniqueCode('site'), 'status' => 'active']);
    $this->type->update(['allowed_parent_type_ids' => [$parent->id], 'is_hierarchical' => true, 'label_field_code' => 'name']);
    $this->fields->create($this->type, ['code' => 'name', 'type' => 'text', 'is_required' => true, 'en_US' => ['label' => 'Name']]);
    $this->fields->create($this->type, ['code' => 'site', 'type' => 'reference', 'reference_entity_type_id' => $parent->id]);
    $headers = $this->getAuthenticationHeaders();

    $response = $this->getJson(route('admin.api.masterdata.entity_types.index'), $headers)->assertOk();

    $listed = collect($response->json('data'))->firstWhere('code', $this->type->code);

    expect($listed['names']['en_US'])->toBe('Line')
        ->and($listed['allowed_parent_types'])->toBe([$parent->code])
        ->and($listed['label_field_code'])->toBe('name')
        ->and(array_column($listed['fields'], 'code'))->toBe(['name', 'site'])
        ->and($listed['fields'][0]['labels']['en_US'])->toBe('Name')
        ->and($listed['fields'][0]['flags']['is_required'])->toBeTrue()
        ->and($listed['fields'][1]['reference_entity_type'])->toBe($parent->code);

    $this->getJson(route('admin.api.masterdata.entity_types.get', $this->type->code), $headers)
        ->assertOk()
        ->assertJsonPath('data.code', $this->type->code)
        ->assertJsonPath('data.fields.0.code', 'name');

    $this->getJson(route('admin.api.masterdata.entity_types.get', 'zzz_none'), $headers)
        ->assertNotFound()
        ->assertJsonPath('code', 'entity_type_not_found');
});

it('shows a code list with the codes of its effective version', function () {
    $list = app(CodeListRepository::class)->createWithDraft(['code' => $this->uniqueCode('status'), 'owner' => 'Ops', 'en_US' => ['name' => 'Status']]);
    $draft = $list->versions()->firstOrFail();
    app(CodeRepository::class)->create(['version_id' => $draft->id, 'code' => 'run', 'sort' => 2, 'en_US' => ['label' => 'Running']]);
    app(CodeRepository::class)->create(['version_id' => $draft->id, 'code' => 'stop', 'sort' => 1, 'en_US' => ['label' => 'Stopped']]);
    app(CodeListPublisher::class)->publish($draft);
    $headers = $this->getAuthenticationHeaders();

    $this->getJson(route('admin.api.masterdata.code_lists.get', $list->code), $headers)
        ->assertOk()
        ->assertJsonPath('data.code', $list->code)
        ->assertJsonPath('data.names.en_US', 'Status')
        ->assertJsonPath('data.owner', 'Ops')
        ->assertJsonPath('data.effective_version.number', 1)
        ->assertJsonPath('data.effective_version.codes.0.code', 'stop')
        ->assertJsonPath('data.effective_version.codes.1.labels.en_US', 'Running')
        ->assertJsonMissingPath('data.versions');

    $this->getJson(route('admin.api.masterdata.code_lists.get', [$list->code, 'include' => 'versions']), $headers)
        ->assertOk()
        ->assertJsonPath('data.versions.0.number', 1)
        ->assertJsonPath('data.versions.0.status', 'published');

    $listed = $this->getJson(route('admin.api.masterdata.code_lists.index', ['limit' => 100]), $headers)->assertOk();

    expect(collect($listed->json('data'))->pluck('code'))->toContain($list->code);

    $this->getJson(route('admin.api.masterdata.code_lists.get', 'zzz_none'), $headers)
        ->assertNotFound()
        ->assertJsonPath('code', 'code_list_not_found');
});

it('denies an api key without the entity types scope', function () {
    $headers = $this->getAuthenticationHeaders('custom', ['api.masterdata.records']);

    $this->getJson(route('admin.api.masterdata.entity_types.index'), $headers)->assertForbidden();
    $this->getJson(route('admin.api.masterdata.code_lists.index'), $headers)->assertForbidden();
    $this->getJson(($this->indexUrl)(), $headers)->assertOk();
});

it('denies records of a type the role has no grant on', function () {
    $other = $this->types->create(['code' => $this->uniqueCode('asset'), 'status' => 'active']);
    $headers = $this->getAuthenticationHeaders();
    ($this->restrictApiUser)([$other->id => [RecordAction::View->value]]);

    $this->getJson(($this->indexUrl)(), $headers)
        ->assertForbidden()
        ->assertJsonPath('code', 'forbidden_entity_type');

    $this->getJson(route('admin.api.masterdata.entity_types.get', $this->type->code), $headers)
        ->assertForbidden()
        ->assertJsonPath('code', 'forbidden_entity_type');

    $this->getJson(($this->indexUrl)([], $other), $headers)->assertOk();

    expect(collect($this->getJson(route('admin.api.masterdata.entity_types.index'), $headers)->json('data'))->pluck('code')->all())
        ->toContain($other->code)
        ->not->toContain($this->type->code);
});

it('returns 404 for every master data route in pim mode', function () {
    $headers = $this->getAuthenticationHeaders();
    $this->setPlatformModules('pim');

    $this->getJson(($this->indexUrl)(), $headers)->assertNotFound();
    $this->getJson(route('admin.api.masterdata.entity_types.index'), $headers)->assertNotFound();
    $this->getJson(route('admin.api.masterdata.code_lists.index'), $headers)->assertNotFound();
});

it('returns 404 entity_type_not_found when listing an unknown type', function () {
    $this->getJson(route('admin.api.masterdata.records.index', 'zzz_none'), $this->getAuthenticationHeaders())
        ->assertNotFound()
        ->assertJsonPath('code', 'entity_type_not_found');
});
