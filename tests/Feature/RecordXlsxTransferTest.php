<?php

use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Webkul\DataTransfer\Helpers\Import;
use Webkul\DataTransfer\Jobs\Import\ImportTrackBatch;
use Webkul\DataTransfer\Models\JobInstances;
use Webkul\DataTransfer\Models\JobTrack;
use Webkul\DataTransfer\Repositories\JobInstancesRepository;
use Webkul\MasterDataManagement\Enums\ExportFormat;
use Webkul\MasterDataManagement\Export\ExportConsumers;
use Webkul\MasterDataManagement\Export\RecordExporter;
use Webkul\MasterDataManagement\Export\RecordRowBuilder;
use Webkul\MasterDataManagement\Importers\Record\Importer as RecordImporter;
use Webkul\MasterDataManagement\Models\Record;
use Webkul\MasterDataManagement\Repositories\EntityTypeRepository;
use Webkul\MasterDataManagement\Services\FieldService;
use Webkul\MasterDataManagement\Services\RecordWriter;

beforeEach(function () {
    Storage::fake('masterdata_export');

    config()->set('masterdata.export_disk', 'masterdata_export');

    $this->written = [];

    $this->admin = $this->loginAsAdmin();

    $this->type = app(EntityTypeRepository::class)->create([
        'code'          => $this->uniqueCode('site'),
        'status'        => 'active',
        'code_strategy' => 'source_id',
        'en_US'         => ['name' => 'Site'],
    ]);

    app(FieldService::class)->create($this->type, ['code' => 'city', 'type' => 'text', 'en_US' => ['label' => 'City']]);
    app(FieldService::class)->create($this->type, ['code' => 'motto', 'type' => 'text', 'value_per_locale' => true, 'en_US' => ['label' => 'Motto']]);

    $this->writer = app(RecordWriter::class);

    $this->first = $this->writer->create($this->type, ['code' => 'S-1', 'label' => 'North', 'values' => ['common' => ['city' => 'Oslo'], 'locale_specific' => ['en_US' => ['motto' => 'Cold']]]], $this->admin);
    $this->second = $this->writer->create($this->type, ['code' => 'S-2', 'label' => 'South', 'values' => ['common' => ['city' => 'Rome']]], $this->admin);

    $this->exportXlsx = function (): string {
        $job = app(JobInstancesRepository::class)->create([
            'code'                => $this->type->code.'_xlsx',
            'entity_type'         => ExportConsumers::EXPORTER,
            'type'                => 'export',
            'action'              => 'export',
            'validation_strategy' => '',
            'filters'             => [
                RecordExporter::FILTER_ENTITY_TYPE => $this->type->code,
                RecordExporter::FILTER_FORMAT      => ExportFormat::Xlsx->value,
            ],
        ]);

        $track = JobTrack::factory()->create([
            'type'             => 'export',
            'action'           => 'export',
            'job_instances_id' => $job->id,
            'user_id'          => $this->admin->id,
        ]);

        $rows = app(RecordRowBuilder::class)->build($this->type, [(int) $this->first->id, (int) $this->second->id], $this->admin);

        return $this->written[] = app(RecordExporter::class)->setExport($track)->writeExportFile([$rows]);
    };
});

afterEach(function () {
    Storage::disk('private')->delete($this->written);
});

it('offers xlsx as a record export format', function () {
    $formats = array_column(config('exporters.masterdata_records.filters.fields.1.options'), 'value');

    expect($formats)->toContain(ExportFormat::Xlsx->value)
        ->and(ExportFormat::Xlsx->extension())->toBe('xlsx');
});

it('writes the record export as an xlsx workbook with the csv column layout', function () {
    $path = ($this->exportXlsx)();

    expect($path)->toEndWith('.xlsx')
        ->and(Storage::disk('private')->exists($path))->toBeTrue();

    $sheet = IOFactory::load(Storage::disk('private')->path($path))->getActiveSheet()->toArray();

    $header = $sheet[0];

    expect($header)->toContain('code', 'label', 'value_city', 'value_motto_en_US', 'completeness')
        ->and(array_combine($header, $sheet[1])['value_city'])->toBe('Oslo')
        ->and(array_combine($header, $sheet[2])['code'])->toBe('S-2');
});

it('imports an exported xlsx file back unchanged, ignoring the read-only export columns', function () {
    $path = ($this->exportXlsx)();

    $first = $this->first->refresh();

    $this->writer->update($first, ['values' => ['common' => ['city' => 'Bergen'], 'locale_specific' => ['en_US' => ['motto' => 'Warm']]]], (int) $first->revision, $this->admin);

    $instance = JobInstances::query()->create([
        'code'                => $this->type->code.'_xlsx_import',
        'entity_type'         => 'masterdata_records',
        'type'                => 'import',
        'action'              => Import::ACTION_APPEND,
        'validation_strategy' => Import::VALIDATION_STRATEGY_STOP_ON_ERROR,
        'allowed_errors'      => 0,
        'field_separator'     => ',',
        'file_path'           => $path,
        'filters'             => [RecordImporter::FILTER_ENTITY_TYPE => $this->type->code],
    ]);

    $track = JobTrack::query()->create([
        'state'               => Import::STATE_PENDING,
        'type'                => 'import',
        'action'              => Import::ACTION_APPEND,
        'validation_strategy' => Import::VALIDATION_STRATEGY_STOP_ON_ERROR,
        'allowed_errors'      => 0,
        'field_separator'     => ',',
        'file_path'           => $path,
        'meta'                => $instance->toArray(),
        'job_instances_id'    => $instance->id,
        'user_id'             => $this->admin->id,
    ]);

    ImportTrackBatch::dispatchSync($track);

    $first = Record::query()->findOrFail($this->first->id);

    expect($track->refresh()->state)->toBe(Import::STATE_COMPLETED)
        ->and((int) $track->summary['updated'])->toBe(1)
        ->and((int) $track->summary['skipped'])->toBe(0)
        ->and($first->values['common']['city'] ?? null)->toBe('Oslo')
        ->and($first->values['locale_specific']['en_US']['motto'] ?? null)->toBe('Cold')
        ->and(Record::query()->where('entity_type_id', $this->type->id)->count())->toBe(2);
});
