<?php

namespace Webkul\MasterDataManagement\Export;

use Generator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Webkul\DataTransfer\Buffer\FileBuffer;
use Webkul\DataTransfer\Contracts\Exporters\WritesExportFile;
use Webkul\DataTransfer\Contracts\JobTrackBatch as JobTrackBatchContract;
use Webkul\DataTransfer\Helpers\Export;
use Webkul\DataTransfer\Helpers\Exporters\AbstractExporter;
use Webkul\DataTransfer\Jobs\Export\File\FlatItemBuffer as FileExportFileBuffer;
use Webkul\DataTransfer\Repositories\JobTrackBatchRepository;
use Webkul\DataTransfer\Repositories\JobTrackRepository;
use Webkul\MasterDataManagement\Contracts\ExportDestination;
use Webkul\MasterDataManagement\Enums\ExportFormat;
use Webkul\MasterDataManagement\Enums\RecordAction;
use Webkul\MasterDataManagement\Models\EntityType;
use Webkul\MasterDataManagement\Repositories\EntityTypeRepository;
use Webkul\MasterDataManagement\Repositories\RecordRepository;
use Webkul\MasterDataManagement\Repositories\SourceSystemRepository;
use Webkul\MasterDataManagement\Security\EntityTypePermissions;
use Webkul\User\Models\Admin;
use Webkul\User\Repositories\AdminRepository;

/**
 * The `masterdata_records` exporter: the records of one entity type as JSON Lines or CSV, written to the job's
 * download file and published to the `masterdata.export_disk` destination under
 * `{export_path}/{entity_type}/{Y/m/d}/`.
 *
 * Records are read with the row scope of the admin who started the run. The "changed since last run" option keeps
 * the records updated since the start of the job's last completed run, so edits made during that run are not lost.
 */
class RecordExporter extends AbstractExporter implements WritesExportFile
{
    public const BATCH_SIZE = 500;

    public const string FILTER_ENTITY_TYPE = 'entity_type_code';

    public const string FILTER_FORMAT = 'file_format';

    public const string FILTER_CHANGED_SINCE_LAST_RUN = 'changed_since_last_run';

    public const string FILTER_STATUSES = 'statuses';

    protected ?EntityType $entityType = null;

    protected ?Admin $admin = null;

    public function __construct(
        protected JobTrackBatchRepository $exportBatchRepository,
        protected FileExportFileBuffer $exportFileBuffer,
        protected JobTrackRepository $jobTrackRepository,
        protected EntityTypeRepository $entityTypeRepository,
        protected RecordRepository $recordRepository,
        protected SourceSystemRepository $sourceSystemRepository,
        protected AdminRepository $adminRepository,
        protected EntityTypePermissions $entityTypePermissions,
        protected RecordRowBuilder $rowBuilder,
        protected RecordCsvFlattener $csvFlattener,
        protected ExportDestination $destination,
    ) {
        parent::__construct($exportBatchRepository, $exportFileBuffer);
    }

    /**
     * Resolve the exported entity type and the admin whose row scope the run reads with.
     *
     * @throws RuntimeException
     */
    public function initilize(): void
    {
        if ($this->entityType instanceof EntityType) {
            return;
        }

        $code = (string) ($this->getFilters()[self::FILTER_ENTITY_TYPE] ?? '');

        $type = $code === '' ? null : $this->entityTypeRepository->findOneByField('code', $code);

        if (! $type instanceof EntityType) {
            throw new RuntimeException(trans('masterdata::app.exports.records.errors.entity-type-missing', ['code' => $code]));
        }

        $admin = $this->adminRepository->find($this->export->user_id);

        if (! $admin instanceof Admin || ! $this->entityTypePermissions->allows($admin, (int) $type->id, RecordAction::View)) {
            throw new RuntimeException(trans('masterdata::app.exports.records.errors.not-allowed', ['code' => $code]));
        }

        $this->entityType = $type;

        $this->admin = $admin;
    }

    /**
     * Export one queued batch of record ids into the job's row buffer.
     */
    public function exportBatch(JobTrackBatchContract $batch, $filePath): bool
    {
        Event::dispatch('data_transfer.exports.batch.export.before', $batch);

        $this->initilize();

        $ids = array_values(array_map(fn (array $row): int => (int) $row['id'], (array) $batch->data));

        $rows = $this->rowBuilder->build($this->entityType, $ids, $this->admin);

        $this->exportBuffer->write($rows);

        $this->createdItemsCount += count($rows);

        $this->skippedItemsCount += count($ids) - count($rows);

        $this->updateBatchState($batch->id, Export::STATE_PROCESSED);

        Event::dispatch('data_transfer.exports.batch.export.after', $batch);

        return true;
    }

    /**
     * Write the buffered rows as the job's download file, then publish it to the export destination.
     *
     * @param  iterable<int, mixed>  $exportBuffer
     */
    public function writeExportFile(iterable $exportBuffer): string
    {
        $this->initilize();

        $directory = sprintf('exports/%s/%s', $this->export->id, FileBuffer::FOLDER_PREFIX);

        $fileName = $this->getFileName();

        $path = $this->format() === ExportFormat::Csv
            ? $this->writeCsv($exportBuffer, $directory, $fileName)
            : $this->writeJsonl($exportBuffer, $directory.'/'.$fileName);

        $location = $this->destination->publish($path, $this->targetPath($fileName));

        $this->jobLogger?->info(trans('masterdata::app.exports.records.published', ['location' => $location]));

        return $path;
    }

    /**
     * The file name `{entity_type}-{YmdHis}-{job track id}.{extension}`, unique per run.
     */
    public function getFileName(): string
    {
        return sprintf(
            '%s-%s-%s.%s',
            preg_replace('/[^A-Za-z0-9_-]/', '', (string) ($this->getFilters()[self::FILTER_ENTITY_TYPE] ?? 'records')),
            Date::now()->format('YmdHis'),
            $this->export->id,
            $this->format()->extension(),
        );
    }

    /**
     * Batches carry record ids only, so no flat file writer is opened up front.
     */
    public function initializeFileBuffer(): FileExportFileBuffer
    {
        return $this->exportFileBuffer;
    }

    /**
     * Stream the ids of the matching records in id order, one chunked query per page.
     */
    protected function getResults(): Generator
    {
        $this->initilize();

        foreach ($this->recordQuery()->select('id')->lazyById(self::BATCH_SIZE, 'id') as $record) {
            yield ['id' => (int) $record->getKey()];
        }
    }

    /**
     * The scoped record query of the exported type with the status and change filters applied.
     */
    protected function recordQuery(): Builder
    {
        $statuses = self::listFilter($this->getFilters()[self::FILTER_STATUSES] ?? null);

        $since = $this->changedSince();

        return $this->recordRepository->scopedQuery((int) $this->entityType->id, $this->admin)
            ->when($statuses !== [], fn (Builder $query) => $query->whereIn('status', $statuses))
            ->when($since !== null, fn (Builder $query) => $query->where('updated_at', '>=', $since));
    }

    /**
     * The start of the job's last completed run when only changed records are exported, otherwise null.
     */
    protected function changedSince(): ?string
    {
        if (($this->getFilters()[self::FILTER_CHANGED_SINCE_LAST_RUN] ?? '0') !== '1') {
            return null;
        }

        $startedAt = $this->jobTrackRepository->getModel()->newQuery()
            ->where('job_instances_id', $this->export->job_instances_id)
            ->where('state', Export::STATE_COMPLETED)
            ->whereKeyNot($this->export->id)
            ->whereNotNull('started_at')
            ->latest('started_at')
            ->value('started_at');

        return $startedAt === null ? null : Date::parse($startedAt)->toDateTimeString();
    }

    /**
     * Write the rows as JSON Lines, one record per line, through a temporary stream.
     *
     * @param  iterable<int, mixed>  $exportBuffer
     *
     * @throws RuntimeException
     */
    protected function writeJsonl(iterable $exportBuffer, string $path): string
    {
        $stream = fopen('php://temp', 'w+b');

        try {
            foreach ($exportBuffer as $rows) {
                foreach ((array) $rows as $row) {
                    $row['values'] = (object) ($row['values'] ?? []);
                    $row['xrefs'] = (object) ($row['xrefs'] ?? []);

                    fwrite($stream, json_encode($row, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL);
                }
            }

            rewind($stream);

            if (! Storage::disk('private')->writeStream($path, $stream)) {
                throw new RuntimeException(trans('masterdata::app.exports.records.errors.publish-failed', ['disk' => 'private', 'path' => $path]));
            }
        } finally {
            fclose($stream);
        }

        return $path;
    }

    /**
     * Write the rows as CSV with fixed columns through the core flat writer, which guards against formula injection.
     *
     * @param  iterable<int, mixed>  $exportBuffer
     */
    protected function writeCsv(iterable $exportBuffer, string $directory, string $fileName): string
    {
        $parameters = $this->getExportParameter();

        $flattener = $this->csvFlattener->layout(
            $this->rowBuilder->exportedFields($this->entityType),
            core()->getAllActiveLocales()->pluck('code')->all(),
            array_map(strval(...), array_keys($this->sourceSystemRepository->namesByCode())),
        );

        $buffer = $this->exportFileBuffer->initialize($directory, $fileName, $parameters);

        foreach ($exportBuffer as $rows) {
            $buffer->addData(array_map($flattener->flatten(...), (array) $rows));
        }

        if ($buffer->count() === 0 && $parameters['writeHeaders']) {
            $buffer->writeHeader($flattener->columns());
        }

        $buffer->writerClose();

        return $buffer->getFilePath()->getFilePath();
    }

    /**
     * The destination path `{export_path}/{entity_type}/{Y/m/d}/{file}`.
     */
    protected function targetPath(string $fileName): string
    {
        return sprintf(
            '%s/%s/%s/%s',
            trim((string) config('masterdata.export_path', 'masterdata/exports'), '/'),
            $this->entityType->code,
            Date::now()->format('Y/m/d'),
            $fileName,
        );
    }

    /**
     * The export format chosen in the job filters, JSON Lines by default.
     */
    protected function format(): ExportFormat
    {
        return ExportFormat::tryFrom((string) ($this->getFilters()[self::FILTER_FORMAT] ?? '')) ?? ExportFormat::Jsonl;
    }

    /**
     * Read a multiselect filter value, stored as a list, a JSON list or comma separated text, as a list of values.
     *
     * @return list<string>
     */
    public static function listFilter(mixed $value): array
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);

            $value = is_array($decoded) ? $decoded : explode(',', $value);
        }

        if (! is_array($value)) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map(
            fn (mixed $item): string => trim((string) (is_array($item) ? ($item['value'] ?? $item['code'] ?? '') : $item)),
            $value,
        ), fn (string $item): bool => $item !== '')));
    }
}
