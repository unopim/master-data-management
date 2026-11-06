<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Webkul\MasterDataManagement\Enums\GrantPrincipal;
use Webkul\MasterDataManagement\Models\EntityType;
use Webkul\MasterDataManagement\Repositories\AccessGrantRepository;
use Webkul\MasterDataManagement\Repositories\CodeListRepository;
use Webkul\MasterDataManagement\Repositories\CodeRepository;
use Webkul\MasterDataManagement\Repositories\EntityTypeRepository;
use Webkul\MasterDataManagement\Repositories\RoleEntityPermissionRepository;
use Webkul\MasterDataManagement\Security\AccessGrantResolver;
use Webkul\MasterDataManagement\Security\EntityTypePermissions;
use Webkul\MasterDataManagement\Services\CodeListPublisher;
use Webkul\MasterDataManagement\Services\FieldService;
use Webkul\MasterDataManagement\Services\RecordWriter;
use Webkul\User\Models\Admin;
use Webkul\User\Models\Role;

beforeEach(function () {
    $types = app(EntityTypeRepository::class);
    $this->writer = app(RecordWriter::class);
    $fields = app(FieldService::class);

    $list = app(CodeListRepository::class)->createWithDraft(['code' => $this->uniqueCode('criticality'), 'en_US' => ['name' => 'Criticality']]);
    $draft = $list->versions()->firstOrFail();

    foreach (['high' => 'High', 'medium' => 'Medium', 'low' => 'Low'] as $sort => $label) {
        app(CodeRepository::class)->create(['version_id' => $draft->id, 'code' => $sort, 'sort' => strlen($sort), 'en_US' => ['label' => $label]]);
    }

    app(CodeListPublisher::class)->publish($draft);

    $this->site = $types->create(['code' => $this->uniqueCode('site'), 'status' => 'active', 'code_strategy' => 'source_id']);
    $this->type = $types->create(['code' => $this->uniqueCode('pump'), 'status' => 'active', 'code_strategy' => 'source_id']);

    $fields->create($this->type, ['code' => 'name', 'type' => 'text', 'is_searchable' => true]);
    $fields->create($this->type, ['code' => 'capacity', 'type' => 'integer', 'is_searchable' => true]);
    $fields->create($this->type, ['code' => 'commissioned', 'type' => 'date', 'is_searchable' => true]);
    $fields->create($this->type, ['code' => 'critical', 'type' => 'boolean', 'is_searchable' => true]);
    $fields->create($this->type, ['code' => 'criticality', 'type' => 'select', 'code_list_id' => $list->id, 'is_searchable' => true]);
    $fields->create($this->type, ['code' => 'tags', 'type' => 'multiselect', 'code_list_id' => $list->id, 'is_searchable' => true]);
    $fields->create($this->type, ['code' => 'site', 'type' => 'reference', 'reference_entity_type_id' => $this->site->id, 'is_searchable' => true]);
    $fields->create($this->type, ['code' => 'notes', 'type' => 'text']);

    $this->plantA = $this->writer->create($this->site, ['code' => 'PLANT-A', 'label' => 'Plant A'], null);
    $this->plantB = $this->writer->create($this->site, ['code' => 'PLANT-B', 'label' => 'Plant B'], null);

    $seed = [
        ['P-1', 'Alpha Feed Pump', 100, '2020-01-15', true, 'high', ['high', 'low'], $this->plantA],
        ['P-2', 'Beta Transfer Pump', 250, '2021-06-01', false, 'medium', ['medium'], $this->plantB],
        ['P-3', 'Gamma Feed Pump', 400, '2022-03-10', true, 'low', ['low'], $this->plantA],
        ['P-4', 'Delta Booster', null, null, null, null, null, null],
    ];

    foreach ($seed as [$code, $name, $capacity, $date, $critical, $criticality, $tags, $site]) {
        $this->writer->create($this->type, ['code' => $code, 'label' => $code, 'values' => ['common' => array_filter([
            'name'         => $name,
            'capacity'     => $capacity,
            'commissioned' => $date,
            'critical'     => $critical,
            'criticality'  => $criticality,
            'tags'         => $tags,
            'site'         => $site?->uuid,
            'notes'        => 'secret '.$code,
        ], fn (mixed $value): bool => $value !== null)]], null);
    }

    $this->grid = fn (array $query = [], ?EntityType $type = null) => $this->ajaxGrid(
        route('admin.masterdata.records.index', ($type ?? $this->type)->code).($query === [] ? '' : '?'.http_build_query($query))
    );

    $this->codes = fn (array $query = [], ?EntityType $type = null): array => collect(($this->grid)($query, $type)->assertOk()->json('records'))
        ->pluck('code')->all();

    $this->sorted = fn (array $query = []): array => collect(($this->codes)($query))->sort()->values()->all();
});

it('adds a column and filter for each searchable field only', function () {
    $this->loginAsAdmin();

    $columns = collect(($this->grid)()->assertOk()->json('columns'))->keyBy('index');

    expect($columns->keys()->all())->toContain('field_name', 'field_capacity', 'field_commissioned', 'field_critical', 'field_criticality', 'field_tags', 'field_site')
        ->and($columns->has('field_notes'))->toBeFalse()
        ->and($columns['field_name']['filterable'])->toBeTrue()
        ->and($columns['field_capacity']['attribute_type'])->toBe('integer')
        ->and(collect($columns['field_capacity']['operators'])->pluck('value')->all())->toContain('within_range', 'gte', 'lte')
        ->and($columns['field_commissioned']['type'])->toBe('date_range')
        ->and(collect($columns['field_commissioned']['operators'])->pluck('value')->all())->toContain('within_range')
        ->and($columns['field_name']['attribute_type'])->toBe('text')
        ->and(collect($columns['field_name']['operators'])->pluck('value')->all())->toContain('has')
        ->and($columns['field_critical']['type'])->toBe('dropdown')
        ->and($columns['field_critical']['options']['type'])->toBe('basic')
        ->and($columns['field_criticality']['type'])->toBe('dropdown')
        ->and($columns['field_criticality']['options']['type'])->toBe('searchable')
        ->and($columns['field_criticality']['options']['params'])->toBe(['field' => 'criticality'])
        ->and($columns['field_tags']['options']['type'])->toBe('searchable')
        ->and($columns['field_tags']['sortable'])->toBeFalse()
        ->and($columns['field_site']['options']['params'])->toBe(['field' => 'site'])
        ->and($columns['field_site']['sortable'])->toBeFalse();

    $rows = collect(($this->grid)()->json('records'))->keyBy('code');

    expect($rows['P-1']['field_name'])->toBe('Alpha Feed Pump')
        ->and($rows['P-1']['field_capacity'])->toBe('100')
        ->and($rows['P-1']['field_criticality'])->toBe('High')
        ->and($rows['P-1']['field_tags'])->toBe('High, Low')
        ->and($rows['P-1']['field_site'])->toBe('Plant A')
        ->and($rows['P-1']['field_critical'])->toBe(trans('admin::app.components.datagrid.filters.boolean-options.true'))
        ->and($rows['P-1'])->not->toHaveKey('field_notes')
        ->and(json_encode($rows->all()))->not->toContain('secret');
});

it('filters a text field by contains, equality and blank', function () {
    $this->loginAsAdmin();

    expect(($this->sorted)(['filters' => ['field_name' => ['feed']]]))->toBe(['P-1', 'P-3'])
        ->and(($this->sorted)(['filters' => ['field_name' => [['operator' => 'has', 'value' => 'PUMP']]]]))->toBe(['P-1', 'P-2', 'P-3'])
        ->and(($this->sorted)(['filters' => ['field_name' => [['operator' => 'eq', 'value' => 'beta transfer pump']]]]))->toBe(['P-2'])
        ->and(($this->sorted)(['filters' => ['field_name' => [['operator' => 'like', 'value' => 'gam']]]]))->toBe(['P-3'])
        ->and(($this->sorted)(['filters' => ['field_capacity' => [['operator' => 'blank', 'value' => '']]]]))->toBe(['P-4'])
        ->and(($this->sorted)(['filters' => ['field_capacity' => [['operator' => 'not_blank', 'value' => '']]]]))->toBe(['P-1', 'P-2', 'P-3'])
        ->and(($this->sorted)(['filters' => ['field_name' => ['100%']]]))->toBe([]);
});

it('filters a number field by range and comparison', function () {
    $this->loginAsAdmin();

    expect(($this->sorted)(['filters' => ['field_capacity' => [['operator' => 'within_range', 'value' => '200', 'value2' => '400']]]]))->toBe(['P-2', 'P-3'])
        ->and(($this->sorted)(['filters' => ['field_capacity' => [['operator' => 'lt', 'value' => '250']]]]))->toBe(['P-1'])
        ->and(($this->sorted)(['filters' => ['field_capacity' => [['operator' => 'gte', 'value' => '250']]]]))->toBe(['P-2', 'P-3'])
        ->and(($this->sorted)(['filters' => ['field_capacity' => [['operator' => 'eq', 'value' => '400']]]]))->toBe(['P-3']);
});

it('filters a date field by range and by before or after', function () {
    $this->loginAsAdmin();

    expect(($this->sorted)(['filters' => ['field_commissioned' => [['2021-01-01', '2022-12-31']]]]))->toBe(['P-2', 'P-3'])
        ->and(($this->sorted)(['filters' => ['field_commissioned' => [['operator' => 'within_range', 'value' => '2020-01-01', 'value2' => '2021-06-01']]]]))->toBe(['P-1', 'P-2'])
        ->and(($this->sorted)(['filters' => ['field_commissioned' => [['operator' => 'lt', 'value' => '2021-01-01']]]]))->toBe(['P-1'])
        ->and(($this->sorted)(['filters' => ['field_commissioned' => [['operator' => 'gt', 'value' => '2021-06-01']]]]))->toBe(['P-3']);
});

it('filters option fields by any of the chosen values', function () {
    $this->loginAsAdmin();

    expect(($this->sorted)(['filters' => ['field_criticality' => ['high', 'medium']]]))->toBe(['P-1', 'P-2'])
        ->and(($this->sorted)(['filters' => ['field_tags' => ['low']]]))->toBe(['P-1', 'P-3'])
        ->and(($this->sorted)(['filters' => ['field_critical' => ['1']]]))->toBe(['P-1', 'P-3'])
        ->and(($this->sorted)(['filters' => ['field_critical' => ['0']]]))->toBe(['P-2'])
        ->and(($this->sorted)(['filters' => ['field_site' => [$this->plantA->uuid]]]))->toBe(['P-1', 'P-3'])
        ->and(($this->sorted)(['filters' => ['field_site' => [$this->plantA->uuid, $this->plantB->uuid]]]))->toBe(['P-1', 'P-2', 'P-3']);
});

it('combines filters on several fields with and', function () {
    $this->loginAsAdmin();

    expect(($this->sorted)(['filters' => [
        'field_name'        => ['pump'],
        'field_criticality' => ['high', 'low'],
        'field_capacity'    => [['operator' => 'gte', 'value' => '200']],
    ]]))->toBe(['P-3'])
        ->and(($this->sorted)(['filters' => [
            'field_site'  => [$this->plantA->uuid],
            'field_tags'  => ['high'],
            'code'        => ['P-1'],
        ]]))->toBe(['P-1']);
});

it('sorts by a searchable field with empty values last in both directions', function () {
    $this->loginAsAdmin();

    expect(($this->codes)(['sort' => ['column' => 'field_capacity', 'order' => 'desc']]))->toBe(['P-3', 'P-2', 'P-1', 'P-4'])
        ->and(($this->codes)(['sort' => ['column' => 'field_capacity', 'order' => 'asc']]))->toBe(['P-1', 'P-2', 'P-3', 'P-4'])
        ->and(($this->codes)(['sort' => ['column' => 'field_name', 'order' => 'asc']]))->toBe(['P-1', 'P-2', 'P-4', 'P-3'])
        ->and(($this->codes)(['sort' => ['column' => 'field_commissioned', 'order' => 'desc']]))->toBe(['P-3', 'P-2', 'P-1', 'P-4'])
        ->and(($this->codes)(['sort' => ['column' => 'field_capacity', 'order' => 'asc'], 'filters' => ['field_name' => ['feed']]]))->toBe(['P-1', 'P-3']);

    $page = ($this->grid)(['sort' => ['column' => 'field_capacity', 'order' => 'desc'], 'pagination' => ['per_page' => 2, 'page' => 2]])->assertOk();

    expect(collect($page->json('records'))->pluck('code')->all())->toBe(['P-1', 'P-4'])
        ->and($page->json('meta.total'))->toBe(4);
});

it('ignores a sort on an unknown or unsortable column', function () {
    $this->loginAsAdmin();

    expect(($this->sorted)(['sort' => ['column' => 'field_notes', 'order' => 'asc']]))->toBe(['P-1', 'P-2', 'P-3', 'P-4'])
        ->and(($this->sorted)(['sort' => ['column' => 'field_tags', 'order' => 'asc']]))->toBe(['P-1', 'P-2', 'P-3', 'P-4']);

    ($this->grid)(['sort' => ['column' => 'values', 'order' => 'sideways']])->assertOk();
});

it('ignores filters on non-searchable fields and rejects malformed ones', function () {
    $this->loginAsAdmin();

    expect(($this->sorted)(['filters' => ['field_notes' => ['secret P-1']]]))->toBe(['P-1', 'P-2', 'P-3', 'P-4']);

    ($this->grid)(['filters' => ['field_capacity' => [['operator' => 'drop table', 'value' => '1']]]])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('filters');

    ($this->grid)(['filters' => ['field_site' => array_map(fn (): string => (string) Str::uuid(), range(1, 30))]])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('filters');
});

it('returns the same rows with elasticsearch enabled or disabled', function () {
    $this->loginAsAdmin();

    $query = ['filters' => ['field_name' => ['pump'], 'field_capacity' => [['operator' => 'gte', 'value' => '100']]], 'sort' => ['column' => 'field_capacity', 'order' => 'desc']];

    config(['elasticsearch.enabled' => false]);
    $withoutSearch = ($this->codes)($query);

    config(['elasticsearch.enabled' => true]);
    $withSearch = ($this->codes)($query);

    expect($withoutSearch)->toBe(['P-3', 'P-2', 'P-1'])
        ->and($withSearch)->toBe($withoutSearch);
});

it('keeps the query count of a filtered and sorted page independent of its rows', function () {
    $this->loginAsAdmin();

    $query = [
        'filters'    => ['field_name' => ['pump'], 'field_criticality' => ['high', 'medium', 'low'], 'field_site' => [$this->plantA->uuid, $this->plantB->uuid]],
        'sort'       => ['column' => 'field_capacity', 'order' => 'asc'],
        'pagination' => ['per_page' => 50],
    ];

    $count = function () use ($query): int {
        DB::flushQueryLog();
        DB::enableQueryLog();

        ($this->grid)($query)->assertOk();

        DB::disableQueryLog();

        return count(DB::getQueryLog());
    };

    $count();

    $few = $count();

    foreach (range(5, 14) as $i) {
        $this->writer->create($this->type, ['code' => 'P-'.$i, 'values' => ['common' => [
            'name'        => 'Pump '.$i,
            'capacity'    => $i * 10,
            'criticality' => 'low',
            'tags'        => ['medium'],
            'site'        => $this->plantB->uuid,
        ]]], null);
    }

    expect(($this->codes)($query))->toHaveCount(13)
        ->and($count())->toBe($few);
});

it('offers the codes of a select field and the visible records of a reference field as filter options', function () {
    $this->loginAsAdmin();

    $options = fn (array $query) => $this->getJson(route('admin.masterdata.records.options', ['entity_type' => $this->type->code] + $query));

    expect($options(['field' => 'criticality'])->assertOk()->json('options'))->toBe([
        ['id' => 'low', 'label' => 'Low'],
        ['id' => 'high', 'label' => 'High'],
        ['id' => 'medium', 'label' => 'Medium'],
    ])
        ->and($options(['field' => 'tags', 'query' => 'med'])->json('options'))->toBe([['id' => 'medium', 'label' => 'Medium']])
        ->and($options(['field' => 'criticality', 'identifiers' => ['values' => ['high']]])->json('options'))->toBe([['id' => 'high', 'label' => 'High']])
        ->and(collect($options(['field' => 'site', 'query' => 'plant b'])->json('options'))->pluck('id')->all())->toBe([$this->plantB->uuid]);

    $options(['field' => 'name'])->assertNotFound();
    $options(['field' => 'nope'])->assertNotFound();
});

it('keeps field filters and sorting inside the admin data access scope', function () {
    $types = app(EntityTypeRepository::class);

    $region = $types->create(['code' => $this->uniqueCode('region'), 'status' => 'active', 'code_strategy' => 'source_id', 'is_hierarchical' => true]);
    $asset = $types->create([
        'code'                    => $this->uniqueCode('asset'),
        'status'                  => 'active',
        'code_strategy'           => 'source_id',
        'is_hierarchical'         => true,
        'is_scoped'               => true,
        'allowed_parent_type_ids' => [$region->id],
    ]);

    app(FieldService::class)->create($asset, ['code' => 'name', 'type' => 'text', 'is_searchable' => true]);

    $north = $this->writer->create($region, ['code' => 'NORTH'], null);
    $south = $this->writer->create($region, ['code' => 'SOUTH'], null);

    $this->writer->create($asset, ['code' => 'A-N', 'parent' => $north->uuid, 'values' => ['common' => ['name' => 'Shared Pump']]], null);
    $this->writer->create($asset, ['code' => 'A-S', 'parent' => $south->uuid, 'values' => ['common' => ['name' => 'Shared Pump']]], null);

    $role = Role::factory()->create(['permission_type' => 'custom', 'permissions' => ['masterdata', 'masterdata.records', 'masterdata.records.view']]);

    foreach ([$region, $asset] as $type) {
        app(RoleEntityPermissionRepository::class)->syncForEntityType($type->id, [$role->id => ['view']]);
    }

    app(EntityTypePermissions::class)->flush();

    $admin = Admin::factory()->create(['role_id' => $role->id]);

    app(AccessGrantRepository::class)->create([
        'principal_type' => GrantPrincipal::User,
        'principal_id'   => $admin->id,
        'entity_type_id' => null,
        'record_id'      => $north->id,
        'actions'        => ['view'],
    ]);

    app(AccessGrantResolver::class)->flush();

    $this->loginAsAdmin($admin);

    $response = ($this->grid)(['filters' => ['field_name' => ['shared']], 'sort' => ['column' => 'field_name', 'order' => 'asc']], $asset)->assertOk();

    expect(collect($response->json('records'))->pluck('code')->all())->toBe(['A-N'])
        ->and($response->json('meta.total'))->toBe(1);
});
