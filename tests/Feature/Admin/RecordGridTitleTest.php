<?php

use Webkul\MasterDataManagement\Enums\EntityTypeStatus;
use Webkul\MasterDataManagement\Repositories\EntityTypeRepository;

it('switches entity type from the record grid title', function () {
    $this->loginAsAdmin();

    $repository = app(EntityTypeRepository::class);
    $department = $repository->create(['code' => $this->uniqueCode('dept'), 'code_strategy' => 'source_id', 'status' => EntityTypeStatus::Active->value, 'name' => 'Department']);
    $plant = $repository->create(['code' => $this->uniqueCode('plant'), 'code_strategy' => 'source_id', 'status' => EntityTypeStatus::Active->value, 'name' => 'Plant']);
    $draft = $repository->create(['code' => $this->uniqueCode('draftie')]);

    $this->get(route('admin.masterdata.records.index', $department->code))
        ->assertOk()
        ->assertSee('aria-label="'.trans('masterdata::app.records.index.switch-type').'"', false)
        ->assertSee(route('admin.masterdata.records.index', $plant->code), false)
        ->assertDontSee(route('admin.masterdata.records.index', $draft->code), false)
        ->assertDontSee('class="secondary-button"', false);
});

it('escapes the entity type name in the page title', function () {
    $this->loginAsAdmin();

    $type = app(EntityTypeRepository::class)->create(['code' => $this->uniqueCode('dept'), 'code_strategy' => 'source_id', 'status' => EntityTypeStatus::Active->value, 'name' => '<b>R&D</b>']);

    $this->get(route('admin.masterdata.records.index', $type->code))
        ->assertOk()
        ->assertDontSee('<b>R&D</b>', false)
        ->assertSee('&lt;b&gt;R&amp;D&lt;/b&gt;', false);
});
