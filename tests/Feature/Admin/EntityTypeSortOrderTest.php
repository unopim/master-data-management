<?php

use Illuminate\Support\Facades\Cache;
use Webkul\MasterDataManagement\Models\EntityType;
use Webkul\MasterDataManagement\Repositories\EntityTypeRepository;

it('lists entity types by sort order, then by code', function () {
    $prefix = $this->uniqueCode('order');
    $repository = app(EntityTypeRepository::class);

    $types = collect([['b', 20], ['a', 20], ['z', 5], ['m', 0]])->map(fn (array $definition): EntityType => $repository->create([
        'code'       => $prefix.'_'.$definition[0],
        'status'     => 'active',
        'sort_order' => $definition[1],
    ]));

    $ids = $types->pluck('id')->all();
    $expected = [$prefix.'_m', $prefix.'_z', $prefix.'_a', $prefix.'_b'];

    $codeOptions = collect($repository->codeOptions(1000))->pluck('label')->filter(fn (string $code): bool => str_starts_with($code, $prefix))->values()->all();

    expect($repository->listActive($ids)->pluck('code')->all())->toBe($expected)
        ->and(collect($repository->options($ids))->pluck('label')->all())->toBe($expected)
        ->and($codeOptions)->toBe($expected);
});

it('sorts the entity type grid by sort order, then by code, by default', function () {
    $this->loginAsAdmin();

    $prefix = $this->uniqueCode('gridorder');
    $repository = app(EntityTypeRepository::class);

    foreach ([['second', 10], ['first', 10], ['last', 30], ['zero', 0]] as [$suffix, $sortOrder]) {
        $repository->create(['code' => $prefix.'_'.$suffix, 'sort_order' => $sortOrder]);
    }

    $records = $this->ajaxGrid(route('admin.masterdata.entity_types.index', ['filters' => ['code' => [$prefix]]]))
        ->assertOk()
        ->json('records');

    expect(array_map(fn (array $row): string => strip_tags($row['code']), $records))
        ->toBe([$prefix.'_zero', $prefix.'_first', $prefix.'_second', $prefix.'_last'])
        ->and(array_column($records, 'sort_order'))->toBe([0, 10, 10, 30]);
});

it('stores the sort order from the entity type form', function () {
    $this->loginAsAdmin();

    $code = $this->uniqueCode('sorted');

    $this->postJson(route('admin.masterdata.entity_types.store'), ['code' => $code, 'name' => 'Sorted', 'sort_order' => 25])
        ->assertOk();

    $type = EntityType::query()->where('code', $code)->firstOrFail();

    expect($type->sort_order)->toBe(25);

    $this->putJson(route('admin.masterdata.entity_types.update', $type->id), ['sort_order' => 9999])->assertOk();

    expect($type->refresh()->sort_order)->toBe(9999);

    $this->get(route('admin.masterdata.entity_types.edit', $type->id))
        ->assertOk()
        ->assertSee('name="sort_order"', false)
        ->assertSee(trans('masterdata::app.entity-types.form.sort-order'));
});

it('defaults the sort order to zero', function () {
    $this->loginAsAdmin();

    $code = $this->uniqueCode('unsorted');

    $this->postJson(route('admin.masterdata.entity_types.store'), ['code' => $code, 'name' => 'Unsorted'])->assertOk();

    expect(EntityType::query()->where('code', $code)->firstOrFail()->sort_order)->toBe(0);
});

it('rejects a sort order outside 0 to 9999', function (mixed $sortOrder) {
    $this->loginAsAdmin();

    $type = app(EntityTypeRepository::class)->create(['code' => $this->uniqueCode('badsort')]);

    $this->putJson(route('admin.masterdata.entity_types.update', $type->id), ['sort_order' => $sortOrder])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('sort_order');
})->with(['negative' => -1, 'too large' => 10000, 'decimal' => 1.5, 'text' => 'first', 'empty' => '']);

it('shows each record card with its description and placement, in sort order', function () {
    $this->loginAsAdmin();

    Cache::flush();

    $prefix = $this->uniqueCode('cards');
    $repository = app(EntityTypeRepository::class);
    $locale = core()->getRequestedLocaleCode();

    $site = $repository->create([
        'code'            => $prefix.'_site',
        'status'          => 'active',
        'sort_order'      => 9001,
        'is_hierarchical' => true,
        $locale           => ['name' => 'Card Site', 'description' => 'A factory or plant at one address.'],
    ]);

    $hidden = $repository->create(['code' => $prefix.'_draft', $locale => ['name' => 'Draft Parent']]);

    $repository->create([
        'code'                    => $prefix.'_line',
        'status'                  => 'active',
        'sort_order'              => 9002,
        'is_hierarchical'         => true,
        'allowed_parent_type_ids' => [$site->id, $hidden->id],
        $locale                   => ['name' => 'Card Line'],
    ]);

    $repository->create([
        'code'       => $prefix.'_platform',
        'status'     => 'active',
        'sort_order' => 9003,
        $locale      => ['name' => 'Card Platform', 'description' => 'A technology group of lines.'],
    ]);

    $this->get(route('admin.masterdata.dashboard.index'))
        ->assertOk()
        ->assertSeeInOrder([
            'Card Site',
            'A factory or plant at one address.',
            trans('masterdata::app.dashboard.top-level'),
            'Card Line',
            trans('masterdata::app.dashboard.inside', ['types' => 'Card Site, Draft Parent']),
            'Card Platform',
            'A technology group of lines.',
            trans('masterdata::app.dashboard.standalone'),
        ]);
});
