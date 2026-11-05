<?php

use Webkul\MasterDataManagement\Enums\EntityTypeStatus;
use Webkul\MasterDataManagement\Repositories\EntityTypeRepository;
use Webkul\MasterDataManagement\Repositories\SourceSystemRepository;
use Webkul\MasterDataManagement\Services\RecordWriter;

it('shows a helper text under the source system fields', function () {
    $this->loginAsAdmin();

    $system = app(SourceSystemRepository::class)->create(['code' => $this->uniqueCode('erp'), 'name' => 'ERP', 'type' => 'erp']);

    $this->get(route('admin.masterdata.source_systems.edit', $system->id))
        ->assertOk()
        ->assertSee(trans('masterdata::app.help.source-systems.priority'))
        ->assertSee(trans('masterdata::app.help.source-systems.is-active'))
        ->assertSee(trans('masterdata::app.help.source-systems.external-id-is-personal'), false);
});

it('shows a helper text under the entity type create fields', function () {
    $this->loginAsAdmin();

    $this->get(route('admin.masterdata.entity_types.create'))
        ->assertOk()
        ->assertSee(trans('masterdata::app.help.entity-types.code'))
        ->assertSee(trans('masterdata::app.help.entity-types.code-strategy'))
        ->assertSee(trans('masterdata::app.help.entity-types.approval-required'));
});

it('explains the empty relations, merges and children of a record', function () {
    $this->loginAsAdmin();

    $department = app(EntityTypeRepository::class)->create([
        'code'            => $this->uniqueCode('department'),
        'code_strategy'   => 'source_id',
        'status'          => EntityTypeStatus::Active->value,
        'name'            => 'Department',
        'is_hierarchical' => true,
    ]);

    app(EntityTypeRepository::class)->create([
        'code'                    => $this->uniqueCode('line'),
        'code_strategy'           => 'source_id',
        'status'                  => EntityTypeStatus::Active->value,
        'name'                    => 'Line',
        'is_hierarchical'         => true,
        'allowed_parent_type_ids' => [$department->id],
    ]);

    $record = app(RecordWriter::class)->create($department, ['code' => 'PACK', 'label' => 'Packing'], null);

    $this->get(route('admin.masterdata.records.edit', $record->uuid))
        ->assertOk()
        ->assertSee(trans('masterdata::app.records.relations.none-configured', ['type' => 'Department']))
        ->assertSee(trans('masterdata::app.records.merge.empty-info'))
        ->assertSee(trans('masterdata::app.hierarchy.empty-children-types', ['children' => 'Department, Line', 'type' => 'Department']));
});

it('names the possible relation types when a record has no links yet', function () {
    $this->loginAsAdmin();

    $repository = app(EntityTypeRepository::class);

    $filler = $repository->create(['code' => $this->uniqueCode('filler'), 'code_strategy' => 'source_id', 'status' => EntityTypeStatus::Active->value, 'name' => 'Filler']);

    $checkweigher = $repository->create(['code' => $this->uniqueCode('checkweigher'), 'code_strategy' => 'source_id', 'status' => EntityTypeStatus::Active->value, 'name' => 'Checkweigher']);

    $this->postJson(route('admin.masterdata.relation_types.store', $filler->id), [
        'code'              => $this->uniqueCode('feeds'),
        'to_entity_type_id' => $checkweigher->id,
        'cardinality'       => 'many',
        'label'             => [core()->getDefaultLocaleCodeFromDefaultChannel() => 'Feeds'],
    ])->assertOk();

    $record = app(RecordWriter::class)->create($filler, ['code' => 'FIL-1', 'label' => 'Filler 1'], null);

    $this->get(route('admin.masterdata.records.edit', $record->uuid))
        ->assertOk()
        ->assertDontSee(trans('masterdata::app.records.relations.none-configured', ['type' => 'Filler']))
        ->assertSee(trans('masterdata::app.records.relations.possible', ['types' => 'Feeds']))
        ->assertSee(trans('masterdata::app.records.relations.no-types-inbound'));
});
