<?php

namespace Webkul\MasterDataManagement\Demo\Services;

use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Webkul\DataTransfer\Helpers\Import;
use Webkul\DataTransfer\Models\JobInstances;
use Webkul\DataTransfer\Repositories\JobInstancesRepository;
use Webkul\DataTransfer\Services\SampleFiles;
use Webkul\MasterDataManagement\Export\RecordExporter;

/**
 * Seeds the demo's ready-to-run job instances: the analytical exports, and imports whose file is already stored on
 * the job, so a presenter opens one and presses Import Now. An import file is the job type's packaged sample unless
 * the dataset names one shipped with the demo.
 */
class JobInstaller
{
    public const string IMPORT_DIRECTORY = 'demo/imports/masterdata';

    public function __construct(
        protected JobInstancesRepository $jobInstancesRepository,
        protected SampleFiles $sampleFiles,
    ) {}

    /**
     * Create the dataset's export and import jobs; existing imports are reset to their demo file and settings.
     *
     * @param  array<string, mixed>  $dataset
     * @return array{export_jobs: int, import_jobs: int}
     */
    public function install(array $dataset): array
    {
        foreach ($dataset['export_jobs'] as $job) {
            $this->ensureExportJob($job['code'], $job['entity_type'], $job['format']);
        }

        foreach ($dataset['import_jobs'] as $job) {
            $this->ensureImportJob($job);
        }

        return [
            'export_jobs' => count($dataset['export_jobs']),
            'import_jobs' => count($dataset['import_jobs']),
        ];
    }

    protected function ensureExportJob(string $code, string $entityType, string $format): void
    {
        if ($this->jobInstancesRepository->findOneByField('code', $code)) {
            return;
        }

        $this->jobInstancesRepository->create([
            'code'                => $code,
            'entity_type'         => 'masterdata_records',
            'type'                => 'export',
            'action'              => 'export',
            'validation_strategy' => '',
            'filters'             => [
                RecordExporter::FILTER_ENTITY_TYPE            => $entityType,
                RecordExporter::FILTER_FORMAT                 => $format,
                RecordExporter::FILTER_CHANGED_SINCE_LAST_RUN => '0',
                RecordExporter::FILTER_STATUSES               => ['active'],
            ],
        ]);
    }

    /**
     * @param  array{code: string, job_type: string, filters: array<string, string>, sample?: string}  $job
     *
     * @throws RuntimeException
     */
    protected function ensureImportJob(array $job): void
    {
        $attributes = [
            'entity_type'         => $job['job_type'],
            'type'                => 'import',
            'action'              => Import::ACTION_APPEND,
            'validation_strategy' => Import::VALIDATION_STRATEGY_SKIP_ERRORS,
            'allowed_errors'      => 0,
            'field_separator'     => ',',
            'file_path'           => $this->storeFile($job),
            'filters'             => $job['filters'],
        ];

        $existing = $this->jobInstancesRepository->findOneByField('code', $job['code']);

        if ($existing instanceof JobInstances) {
            $this->jobInstancesRepository->update($attributes, $existing->id);

            return;
        }

        $this->jobInstancesRepository->create(['code' => $job['code']] + $attributes);
    }

    /**
     * Copy the job's file onto the private disk the importer reads from.
     *
     * @param  array{code: string, job_type: string, sample?: string}  $job
     *
     * @throws RuntimeException
     */
    protected function storeFile(array $job): string
    {
        $source = isset($job['sample'])
            ? __DIR__.'/../Resources/samples/'.$job['sample']
            : $this->sampleFiles->path('importers', $job['job_type']);

        if (! is_string($source) || ! is_file($source)) {
            throw new RuntimeException(sprintf('No sample file for the demo import job "%s".', $job['code']));
        }

        $target = self::IMPORT_DIRECTORY.'/'.$job['code'].'.csv';

        Storage::disk('private')->put($target, (string) file_get_contents($source));

        return $target;
    }
}
