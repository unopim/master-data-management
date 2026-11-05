<?php

use Illuminate\Support\Facades\DB;
use Webkul\MasterDataManagement\Models\EntityType;
use Webkul\MasterDataManagement\Models\Field;
use Webkul\MasterDataManagement\Repositories\EntityTypeRepository;
use Webkul\MasterDataManagement\Services\FieldService;
use Webkul\MasterDataManagement\Services\RecordWriter;

it('shows name, code, parents, status, schema version and record totals per entity type', function () {
    $this->loginAsAdmin();

    $prefix = $this->uniqueCode('gridcols');
    $repository = app(EntityTypeRepository::class);
    $locale = core()->getRequestedLocaleCode();

    $site = $repository->create(['code' => $prefix.'_site', $locale => ['name' => 'Site']]);
    $area = $repository->create(['code' => $prefix.'_area', $locale => ['name' => 'Area']]);
    $line = $repository->create([
        'code'                    => $prefix.'_line',
        'icon'                    => 'icon-catalog',
        'status'                  => 'active',
        'mastering_style'         => 'centralized',
        'is_hierarchical'         => true,
        'allowed_parent_type_ids' => [$site->id, $area->id],
        $locale                   => ['name' => 'Production Line'],
    ]);

    foreach (['Line A', 'Line B', 'Line C'] as $label) {
        app(RecordWriter::class)->create($line, ['label' => $label], null);
    }

    $rows = collect($this->ajaxGrid(route('admin.masterdata.entity_types.index', ['filters' => ['code' => [$prefix]]]))->assertOk()->json('records'))->keyBy('id');

    $lineRow = $rows->get($line->id);
    $siteRow = $rows->get($site->id);

    expect($rows)->toHaveCount(3)
        ->and($lineRow['name'])->toContain('Production Line')->toContain('icon-catalog')
        ->and($lineRow['code'])->toContain($line->code)
        ->and($lineRow['allowed_parent_type_ids'])->toBe('Site, Area')
        ->and($lineRow['status'])->toContain('label-active')->toContain(trans('masterdata::app.entity-types.statuses.active'))
        ->and($lineRow['mastering_style'])->toBe(trans('masterdata::app.entity-types.mastering-styles.centralized'))
        ->and($lineRow['schema_version'])->toBe(trans('masterdata::app.entity-types.datagrid.schema-version-value', ['version' => 0]))
        ->and($lineRow['records_count'])->toContain('>3<')
        ->and($siteRow['records_count'])->toContain('>0<')
        ->and($siteRow['status'])->toContain('label-pending')
        ->and($siteRow['allowed_parent_type_ids'])->toBe('');
});

it('offers delete only for entity types without records', function () {
    $this->loginAsAdmin();

    $prefix = $this->uniqueCode('gridact');
    $repository = app(EntityTypeRepository::class);

    $busy = $repository->create(['code' => $prefix.'_busy']);
    $empty = $repository->create(['code' => $prefix.'_empty']);
    app(RecordWriter::class)->create($busy, ['label' => 'Row'], null);

    $actions = collect($this->ajaxGrid(route('admin.masterdata.entity_types.index', ['filters' => ['code' => [$prefix]]]))->assertOk()->json('records'))
        ->mapWithKeys(fn (array $row): array => [$row['id'] => collect($row['actions'])->pluck('index')->all()]);

    expect($actions[$busy->id])->toBe(['edit'])
        ->and($actions[$empty->id])->toBe(['edit', 'delete']);
});

it('filters entity types by status', function () {
    $this->loginAsAdmin();

    $prefix = $this->uniqueCode('gridstatus');
    $repository = app(EntityTypeRepository::class);

    $draft = $repository->create(['code' => $prefix.'_draft']);
    $active = $repository->create(['code' => $prefix.'_active', 'status' => 'active']);
    $archived = $repository->create(['code' => $prefix.'_archived', 'status' => 'archived']);

    $ids = fn (string $status): array => collect($this->ajaxGrid(route('admin.masterdata.entity_types.index', ['filters' => ['code' => [$prefix], 'status' => [$status]]]))->assertOk()->json('records'))
        ->pluck('id')
        ->all();

    expect($ids('active'))->toBe([$active->id])
        ->and($ids('draft'))->toBe([$draft->id])
        ->and($ids('archived'))->toBe([$archived->id]);
});

it('searches entity types by name and code', function () {
    $this->loginAsAdmin();

    $prefix = $this->uniqueCode('gridsearch');
    $type = app(EntityTypeRepository::class)->create([
        'code'                              => $prefix.'_vendor',
        core()->getRequestedLocaleCode()    => ['name' => 'Zebra Vendor '.$prefix],
    ]);

    $byCode = collect($this->ajaxGrid(route('admin.masterdata.entity_types.index', ['filters' => ['all' => [$prefix.'_vendor']]]))->assertOk()->json('records'))->pluck('id')->all();
    $byName = collect($this->ajaxGrid(route('admin.masterdata.entity_types.index', ['filters' => ['all' => ['Zebra Vendor '.$prefix]]]))->assertOk()->json('records'))->pluck('id')->all();

    expect($byCode)->toBe([$type->id])
        ->and($byName)->toBe([$type->id]);
});

it('runs the same number of queries regardless of how many entity types the page shows', function () {
    $this->loginAsAdmin();

    $prefix = $this->uniqueCode('gridqueries');
    $repository = app(EntityTypeRepository::class);
    $parent = $repository->create(['code' => $prefix.'_root']);

    $createTypes = function (int $count) use ($repository, $parent, $prefix): void {
        for ($i = 0; $i < $count; $i++) {
            $type = $repository->create(['code' => $prefix.'_t'.uniqid(), 'allowed_parent_type_ids' => [$parent->id]]);
            app(RecordWriter::class)->create($type, ['label' => 'Row'], null);
        }
    };

    $countQueries = function () use ($prefix): int {
        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->ajaxGrid(route('admin.masterdata.entity_types.index', ['filters' => ['code' => [$prefix]]]))->assertOk()->json('records');

        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $count;
    };

    $createTypes(2);
    $countQueries();
    $withTwo = $countQueries();

    $createTypes(3);
    $withFive = $countQueries();

    expect($withFive)->toBe($withTwo);
});

it('mass deletes entity types without records and reports the ones it skipped', function () {
    $this->loginAsAdmin();

    $prefix = $this->uniqueCode('gridmass');
    $repository = app(EntityTypeRepository::class);

    $busy = $repository->create(['code' => $prefix.'_busy']);
    $free = $repository->create(['code' => $prefix.'_free']);
    app(RecordWriter::class)->create($busy, ['label' => 'Row'], null);

    $this->postJson(route('admin.masterdata.entity_types.mass_delete'), ['indices' => [$busy->id, $free->id]])
        ->assertOk()
        ->assertJsonPath('skipped', [$busy->code])
        ->assertJsonPath('message', trans('masterdata::app.entity-types.mass-delete-success', ['count' => 1])
            .' '.trans('masterdata::app.entity-types.mass-delete-skipped', ['codes' => $busy->code]));

    expect(EntityType::query()->whereKey($busy->id)->exists())->toBeTrue()
        ->and(EntityType::query()->whereKey($free->id)->exists())->toBeFalse();
});

it('refuses a mass delete when every selected entity type has records', function () {
    $this->loginAsAdmin();

    $type = app(EntityTypeRepository::class)->create(['code' => $this->uniqueCode('gridbusy')]);
    app(RecordWriter::class)->create($type, ['label' => 'Row'], null);

    $this->postJson(route('admin.masterdata.entity_types.mass_delete'), ['indices' => [$type->id]])
        ->assertStatus(400)
        ->assertJsonPath('skipped', [$type->code]);

    expect(EntityType::query()->whereKey($type->id)->exists())->toBeTrue();
});

it('forbids mass delete without the delete acl', function () {
    $type = app(EntityTypeRepository::class)->create(['code' => $this->uniqueCode('gridacl')]);

    $this->loginWithPermissions('custom', ['dashboard', 'masterdata', 'masterdata.entity_types']);

    $this->postJson(route('admin.masterdata.entity_types.mass_delete'), ['indices' => [$type->id]])->assertForbidden();

    $actions = collect($this->ajaxGrid(route('admin.masterdata.entity_types.index', ['filters' => ['code' => [$type->code]]]))->assertOk()->json('records'))->first()['actions'];

    expect($actions)->toBe([])
        ->and(EntityType::query()->whereKey($type->id)->exists())->toBeTrue();
});

it('counts the active fields of each entity type and leaves archived fields out', function () {
    $this->loginAsAdmin();

    $prefix = $this->uniqueCode('gridfields');
    $repository = app(EntityTypeRepository::class);

    $type = $repository->create(['code' => $prefix.'_typed']);
    $bare = $repository->create(['code' => $prefix.'_bare']);
    $baseline = Field::query()->where('entity_type_id', $bare->id)->where('is_archived', false)->count();

    app(FieldService::class)->create($type, ['code' => 'colour', 'type' => 'text']);
    app(FieldService::class)->create($type, ['code' => 'weight', 'type' => 'text']);
    app(FieldService::class)->create($type, ['code' => 'retired', 'type' => 'text', 'is_archived' => true]);

    $rows = collect($this->ajaxGrid(route('admin.masterdata.entity_types.index', ['filters' => ['code' => [$prefix]]]))->assertOk()->json('records'))->keyBy('id');

    expect($rows->get($type->id)['fields_count'])->toContain('>'.($baseline + 2).'<')
        ->and($rows->get($bare->id)['fields_count'])->toContain('>'.$baseline.'<');
});
