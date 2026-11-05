<?php

use Webkul\MasterDataManagement\Models\EntityType;
use Webkul\MasterDataManagement\Models\Record;
use Webkul\MasterDataManagement\Repositories\SourceSystemRepository;
use Webkul\MasterDataManagement\Services\XrefService;

it('seeds the sample twice without duplicates', function () {
    $this->artisan('masterdata:demo:manufacturing')->assertSuccessful();

    $first = $this->demoCounts();

    expect(array_slice($first, 0, 3))->toBe([6, 3, 36])
        ->and($first[3])->toBeGreaterThanOrEqual(69)
        ->and(array_slice($first, 4))->toBe([2, 3, 5]);

    $this->artisan('masterdata:demo:manufacturing')->assertSuccessful();

    expect($this->demoCounts())->toBe($first);
});

it('resolves an MES line id to its ERP and CMMS ids', function () {
    $this->artisan('masterdata:demo:manufacturing')->assertSuccessful();

    $line = EntityType::query()->where('code', 'line')->firstOrFail();
    $mes = app(SourceSystemRepository::class)->findOneByField('code', 'mes');

    $record = app(XrefService::class)->resolve($mes, $line->id, 'LINE_01');

    expect($record->code)->toBe('LINE-01')
        ->and(Record::query()->find($record->parent_id)->code)->toBe('DEPT-PACKING')
        ->and(app(XrefService::class)->idsFor($record))->toMatchArray([
            'erp' => ['4711-L01'],
            'mes' => ['LINE_01'],
        ])
        ->and(app(XrefService::class)->idsFor($record)['cmms'])->toContain('CM-L1');
});

it('keeps the intended parent code on child records', function () {
    $this->artisan('masterdata:demo:manufacturing')->assertSuccessful();

    $equipment = Record::query()->where('code', 'LINE-02-A1-E2')->firstOrFail();

    expect(Record::query()->find($equipment->parent_id)->code)->toBe('LINE-02-A1')
        ->and($equipment->label)->toBe('Equipment 02-A1-E2');
});
