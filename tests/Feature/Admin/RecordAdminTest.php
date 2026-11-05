<?php

use Webkul\MasterDataManagement\Enums\EntityTypeStatus;
use Webkul\MasterDataManagement\Repositories\EntityTypeRepository;
use Webkul\MasterDataManagement\Repositories\SourceSystemRepository;
use Webkul\MasterDataManagement\Services\RecordWriter;
use Webkul\MasterDataManagement\Services\XrefService;

it('lists only records of the chosen type', function () {
    $this->loginAsAdmin();

    $line = app(EntityTypeRepository::class)->create(['code' => $this->uniqueCode('line'), 'code_strategy' => 'source_id']);
    $asset = app(EntityTypeRepository::class)->create(['code' => $this->uniqueCode('asset'), 'code_strategy' => 'source_id']);
    $writer = app(RecordWriter::class);

    $record = $writer->create($line, ['code' => 'L-1', 'label' => 'Line One'], null);
    $writer->create($asset, ['code' => 'A-1', 'label' => 'Pump'], null);

    $erp = app(SourceSystemRepository::class)->create(['code' => $this->uniqueCode('erp'), 'name' => 'ERP', 'type' => 'erp']);
    $mes = app(SourceSystemRepository::class)->create(['code' => $this->uniqueCode('mes'), 'name' => 'MES', 'type' => 'mes']);
    app(XrefService::class)->attach($record, $erp, 'E-77');
    app(XrefService::class)->attach($record, $mes, 'M-5');

    $this->get(route('admin.masterdata.records.index', $line->code))->assertOk();

    $rows = $this->ajaxGrid(route('admin.masterdata.records.index', $line->code))->assertOk()->json('records');

    expect($rows)->toHaveCount(1)
        ->and($rows[0]['code'])->toBe('L-1')
        ->and($rows[0]['label'])->toBe('Line One')
        ->and($rows[0]['external_ids'])->toContain($erp->code.':E-77')
        ->and($rows[0]['external_ids'])->toContain($mes->code.':M-5');

    $codes = collect($this->ajaxGrid(route('admin.masterdata.records.index', $asset->code))->json('records'))->pluck('code');

    expect($codes->all())->toBe(['A-1']);
});

it('returns 404 for an unknown entity type', function () {
    $this->loginAsAdmin();

    $this->get(route('admin.masterdata.records.index', 'nope'))->assertNotFound();
});

it('shows xrefs on the record page', function () {
    $this->loginAsAdmin();

    $type = app(EntityTypeRepository::class)->create(['code' => $this->uniqueCode('line'), 'code_strategy' => 'source_id']);
    $record = app(RecordWriter::class)->create($type, ['code' => 'L-1', 'label' => 'Line One', 'values' => ['common' => ['speed' => 'fast']]], null);
    $erp = app(SourceSystemRepository::class)->create(['code' => $this->uniqueCode('erp'), 'name' => 'ERP', 'type' => 'erp']);
    app(XrefService::class)->attach($record, $erp, 'E-77');

    $this->get(route('admin.masterdata.records.edit', $record->uuid))
        ->assertOk()
        ->assertSee('Line One')
        ->assertSee('E-77')
        ->assertSee($erp->code)
        ->assertSee('speed');
});

it('follows a merged record to its survivor and 404s on unknown uuids', function () {
    $this->loginAsAdmin();

    $type = app(EntityTypeRepository::class)->create(['code' => $this->uniqueCode('line'), 'code_strategy' => 'source_id']);
    $writer = app(RecordWriter::class);
    $survivor = $writer->create($type, ['code' => 'L-1', 'label' => 'Survivor'], null);
    $loser = $writer->create($type, ['code' => 'L-2', 'label' => 'Loser'], null);
    $loser->forceFill(['status' => 'merged', 'merged_into_id' => $survivor->id])->save();

    $this->get(route('admin.masterdata.records.edit', $loser->uuid))->assertOk()->assertSee('Survivor');

    $this->get(route('admin.masterdata.records.edit', '00000000-0000-0000-0000-000000000000'))->assertNotFound();
});

it('forbids users without masterdata.records acl', function () {
    $type = app(EntityTypeRepository::class)->create(['code' => $this->uniqueCode('line'), 'code_strategy' => 'source_id']);
    $record = app(RecordWriter::class)->create($type, ['code' => 'L-1', 'label' => 'A'], null);

    $this->loginWithPermissions('custom', ['dashboard']);

    $this->get(route('admin.masterdata.records.index', $type->code))->assertForbidden();
    $this->get(route('admin.masterdata.records.edit', $record->uuid))->assertForbidden();
});

it('lists active entity types with record counts on the dashboard', function () {
    $this->loginAsAdmin();

    $repository = app(EntityTypeRepository::class);
    $active = $repository->create(['code' => $this->uniqueCode('line'), 'code_strategy' => 'source_id', 'status' => EntityTypeStatus::Active->value, 'name' => 'Production Line']);
    $draft = $repository->create(['code' => $this->uniqueCode('draftie')]);
    $writer = app(RecordWriter::class);
    $writer->create($active, ['code' => 'L-1', 'label' => 'A'], null);
    $writer->create($active, ['code' => 'L-2', 'label' => 'B'], null);

    $this->get(route('admin.masterdata.dashboard.index'))
        ->assertOk()
        ->assertSee(route('admin.masterdata.records.index', $active->code), false)
        ->assertDontSee(route('admin.masterdata.records.index', $draft->code), false);
});
