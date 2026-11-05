<?php

use AzureOss\Storage\BlobFlysystem\AzureBlobStorageAdapter;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Webkul\DataTransfer\Models\JobTrack;
use Webkul\DataTransfer\Repositories\JobInstancesRepository;
use Webkul\MasterDataManagement\Export\ExportConsumers;
use Webkul\MasterDataManagement\Export\RecordExporter;
use Webkul\MasterDataManagement\Repositories\EntityTypeRepository;
use Webkul\User\Models\Admin;

it('resolves the azure disk from its environment driven config', function () {
    expect(config('filesystems.disks.azure'))->toMatchArray([
        'driver'    => 'azure-storage-blob',
        'container' => env('AZURE_STORAGE_CONTAINER', 'masterdata'),
    ]);

    config()->set('filesystems.disks.azure.connection_string', 'DefaultEndpointsProtocol=http;AccountName=devstoreaccount1;AccountKey=Eby8vdM02xNOcqFlqUwJPLlmEtlCDXJ1OUzFT50uSRZ6IFsuFq2UVErCz4I6tq/K1SZFPTOtr/KBHBeksoGMGw==;BlobEndpoint=http://127.0.0.1:10000/devstoreaccount1;');
    config()->set('filesystems.disks.azure.container', 'masterdata-test');

    Storage::forgetDisk('azure');

    $disk = Storage::disk('azure');

    expect($disk)->toBeInstanceOf(FilesystemAdapter::class)
        ->and($disk->getAdapter())->toBeInstanceOf(AzureBlobStorageAdapter::class);

    Storage::forgetDisk('azure');
});

it('publishes the export file to the configured export disk', function () {
    $this->freezeTime();

    Storage::fake('private');
    Storage::fake('azure');

    config()->set('masterdata.export_disk', 'azure');

    $type = app(EntityTypeRepository::class)->create(['code' => $this->uniqueCode('line')]);

    $job = app(JobInstancesRepository::class)->create([
        'code'                => $type->code.'_azure',
        'entity_type'         => ExportConsumers::EXPORTER,
        'type'                => 'export',
        'action'              => 'export',
        'validation_strategy' => '',
        'filters'             => [
            RecordExporter::FILTER_ENTITY_TYPE => $type->code,
            RecordExporter::FILTER_FORMAT      => 'Jsonl',
        ],
    ]);

    $track = JobTrack::factory()->create([
        'type'             => 'export',
        'action'           => 'export',
        'job_instances_id' => $job->id,
        'user_id'          => Admin::factory()->create()->id,
    ]);

    $exporter = app(RecordExporter::class)->setExport($track);

    $privatePath = $exporter->writeExportFile([[['code' => 'LINE-1', 'label' => 'Filler Line North', 'values' => [], 'xrefs' => []]]]);

    $published = Storage::disk('azure')->allFiles();

    expect(Storage::disk('private')->exists($privatePath))->toBeTrue()
        ->and($published)->toBe([sprintf('masterdata/exports/%s/%s/%s', $type->code, now()->format('Y/m/d'), basename($privatePath))])
        ->and(Storage::disk('azure')->get($published[0]))->toContain('"code":"LINE-1"');
});
