<?php

use Webkul\MasterDataManagement\Enums\EntityTypeStatus;
use Webkul\MasterDataManagement\Models\EntityType;
use Webkul\MasterDataManagement\Models\Record;
use Webkul\MasterDataManagement\Repositories\EntityTypeRepository;
use Webkul\MasterDataManagement\Services\RecordPresenter;
use Webkul\MasterDataManagement\Services\RecordWriter;

/**
 * @return array{0: EntityType, 1: Record, 2: Record, 3: Record, 4: Record}
 */
function regionTree(string $code): array
{
    $type = app(EntityTypeRepository::class)->create([
        'code'            => $code,
        'code_strategy'   => 'source_id',
        'status'          => EntityTypeStatus::Active->value,
        'name'            => 'Region',
        'is_hierarchical' => true,
    ]);

    $writer = app(RecordWriter::class);

    $world = $writer->create($type, ['code' => 'WORLD', 'label' => 'World'], null);
    $europe = $writer->create($type, ['code' => 'EU', 'label' => 'Europe', 'parent' => $world->uuid], null);
    $asia = $writer->create($type, ['code' => 'AS', 'label' => 'Asia', 'parent' => $world->uuid], null);
    $germany = $writer->create($type, ['code' => 'DE', 'label' => '<b>Germany</b>', 'parent' => $europe->uuid], null);

    return [$type, $world, $europe, $asia, $germany];
}

function parentFilterUrl(EntityType $type, string $value): string
{
    return route('admin.masterdata.records.index', $type->code).'?'.http_build_query(['filters' => ['parent' => [$value]]]);
}

it('shows the parent trail and child count of each record of a hierarchical type', function () {
    $this->loginAsAdmin();

    [$type, $world, $europe, , $germany] = regionTree($this->uniqueCode('region'));

    $response = $this->ajaxGrid(route('admin.masterdata.records.index', $type->code))->assertOk();

    expect(collect($response->json('columns'))->pluck('index')->all())->toContain('parent', 'children');

    $rows = collect($response->json('records'))->keyBy('code');

    expect($rows['WORLD']['parent'])->toContain(trans('masterdata::app.records.datagrid.top-level'))
        ->and($rows['DE']['parent'])->toContain('World')
        ->and($rows['DE']['parent'])->toContain('Europe')
        ->and($rows['DE']['parent'])->toContain(e(route('admin.masterdata.records.edit', $europe->uuid)))
        ->and($rows['WORLD']['children'])->toContain('>2<')
        ->and($rows['WORLD']['children'])->toContain(e(app(RecordPresenter::class)->childrenUrl($type, $world->uuid)))
        ->and($rows['EU']['children'])->toContain('>1<')
        ->and($rows['DE']['children'])->not->toContain('<a');
});

it('escapes record labels in the parent trail', function () {
    $this->loginAsAdmin();

    [$type, , , , $germany] = regionTree($this->uniqueCode('region'));

    app(RecordWriter::class)->create($type, ['code' => 'BER', 'label' => 'Berlin', 'parent' => $germany->uuid], null);

    $rows = collect($this->ajaxGrid(route('admin.masterdata.records.index', $type->code))->json('records'))->keyBy('code');

    expect($rows['BER']['parent'])->not->toContain('<b>Germany</b>')
        ->and($rows['BER']['parent'])->toContain('&lt;b&gt;Germany&lt;/b&gt;');
});

it('filters the grid to the direct children of a parent', function () {
    $this->loginAsAdmin();

    [$type, $world, $europe] = regionTree($this->uniqueCode('region'));

    $codes = fn (string $value): array => collect($this->ajaxGrid(parentFilterUrl($type, $value))->assertOk()->json('records'))
        ->pluck('code')->sort()->values()->all();

    expect($codes($world->uuid))->toBe(['AS', 'EU'])
        ->and($codes($europe->uuid))->toBe(['DE'])
        ->and($codes('root'))->toBe(['WORLD'])
        ->and($codes('00000000-0000-4000-8000-000000000000'))->toBe([]);
});

it('keeps the grid flat for a type outside any hierarchy', function () {
    $this->loginAsAdmin();

    $type = app(EntityTypeRepository::class)->create(['code' => $this->uniqueCode('plant'), 'code_strategy' => 'source_id', 'status' => EntityTypeStatus::Active->value]);

    app(RecordWriter::class)->create($type, ['code' => 'P-1', 'label' => 'Plant'], null);

    $response = $this->ajaxGrid(route('admin.masterdata.records.index', $type->code))->assertOk();

    expect(collect($response->json('columns'))->pluck('index')->all())->not->toContain('parent')
        ->and(collect($response->json('columns'))->pluck('index')->all())->not->toContain('children');
});

it('offers top level browsing on the index of a hierarchical type only', function () {
    $this->loginAsAdmin();

    [$type] = regionTree($this->uniqueCode('region'));

    $flat = app(EntityTypeRepository::class)->create(['code' => $this->uniqueCode('plant'), 'code_strategy' => 'source_id', 'status' => EntityTypeStatus::Active->value]);

    $this->get(route('admin.masterdata.records.index', $type->code))
        ->assertOk()
        ->assertSee(e(app(RecordPresenter::class)->childrenUrl($type, 'root')), false);

    $this->get(route('admin.masterdata.records.index', $flat->code))
        ->assertOk()
        ->assertDontSee(trans('masterdata::app.records.index.top-level'));
});

it('links a record page to the grid of its children by type', function () {
    $this->loginAsAdmin();

    [$type, $world] = regionTree($this->uniqueCode('region'));

    $this->get(route('admin.masterdata.records.edit', $world->uuid))
        ->assertOk()
        ->assertSee(e(app(RecordPresenter::class)->childrenUrl($type, $world->uuid)), false)
        ->assertSee(trans('masterdata::app.hierarchy.browse-children', ['type' => 'Region']));
});

it('counts and links children of another entity type', function () {
    $this->loginAsAdmin();

    $repository = app(EntityTypeRepository::class);
    $writer = app(RecordWriter::class);

    $site = $repository->create(['code' => $this->uniqueCode('site'), 'code_strategy' => 'source_id', 'status' => EntityTypeStatus::Active->value, 'is_hierarchical' => true]);
    $department = $repository->create(['code' => $this->uniqueCode('dept'), 'code_strategy' => 'source_id', 'status' => EntityTypeStatus::Active->value, 'is_hierarchical' => true, 'allowed_parent_type_ids' => [$site->id]]);
    $line = $repository->create(['code' => $this->uniqueCode('line'), 'code_strategy' => 'source_id', 'status' => EntityTypeStatus::Active->value, 'is_hierarchical' => true, 'allowed_parent_type_ids' => [$department->id]]);

    $plant = $writer->create($site, ['code' => 'S-1', 'label' => 'Plant'], null);
    $assembly = $writer->create($department, ['code' => 'D-1', 'label' => 'Assembly', 'parent' => $plant->uuid], null);
    $writer->create($department, ['code' => 'D-2', 'label' => 'Paint', 'parent' => $plant->uuid], null);
    $writer->create($line, ['code' => 'L-1', 'label' => 'Line One', 'parent' => $assembly->uuid], null);

    $sites = collect($this->ajaxGrid(route('admin.masterdata.records.index', $site->code))->json('records'))->keyBy('code');

    expect($sites['S-1']['children'])->toContain('>2<')
        ->and($sites['S-1']['children'])->toContain(e(route('admin.masterdata.records.edit', $plant->uuid)));

    $departments = collect($this->ajaxGrid(route('admin.masterdata.records.index', $department->code))->json('records'))->keyBy('code');

    expect($departments['D-1']['parent'])->toContain('Plant')
        ->and($departments['D-1']['children'])->toContain('>1<')
        ->and($departments['D-1']['children'])->toContain(e(app(RecordPresenter::class)->childrenUrl($line, $assembly->uuid)))
        ->and($departments['D-2']['children'])->not->toContain('<a');

    $this->get(route('admin.masterdata.records.index', $department->code))
        ->assertOk()
        ->assertDontSee(trans('masterdata::app.records.index.top-level'));

    $lines = $this->ajaxGrid(route('admin.masterdata.records.index', $line->code))->json();

    expect(collect($lines['columns'])->pluck('index')->all())->toContain('parent')->not->toContain('children');

    $codes = collect($this->ajaxGrid(app(RecordPresenter::class)->childrenUrl($department, $plant->uuid))->json('records'))->pluck('code')->sort()->values()->all();

    expect($codes)->toBe(['D-1', 'D-2']);
});
