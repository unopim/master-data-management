<?php

namespace Webkul\MasterDataManagement\Export;

use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Date;
use Webkul\DataTransfer\Helpers\Export;
use Webkul\DataTransfer\Repositories\JobInstancesRepository;
use Webkul\DataTransfer\Repositories\JobTrackRepository;
use Webkul\MasterDataManagement\Enums\ExportFormat;
use Webkul\MasterDataManagement\Models\Record;

/**
 * The `masterdata_records` export jobs that publish a record: those of its entity type whose status filter admits its
 * status, with each job's last completed run and whether the record's current version went out in it.
 *
 * Nothing is tracked per record: the record's `updated_at` is compared with the start of the job's last completed run,
 * the same cut the exporter's "changed since last run" option uses. Two bounded queries, whatever the record count.
 *
 * @phpstan-type ExportConsumer array{id: int, code: string, format: string, delta: bool, last_run_at: ?CarbonInterface, state: string}
 */
class ExportConsumers
{
    /**
     * The exporter code the consuming job instances run.
     */
    public const string EXPORTER = 'masterdata_records';

    /**
     * The most export jobs one record lists.
     */
    public const int LIMIT = 50;

    /**
     * The job never completed a run, so the record has not been published by it yet.
     */
    public const string STATE_NEVER_RUN = 'never_run';

    /**
     * The record changed after the last run started; the next run publishes the change.
     */
    public const string STATE_PENDING = 'pending';

    /**
     * The last full run published the record as it is now.
     */
    public const string STATE_INCLUDED = 'included';

    /**
     * The record did not change since the last delta run, so the version a run published earlier is current.
     */
    public const string STATE_UP_TO_DATE = 'up_to_date';

    public function __construct(
        protected JobInstancesRepository $jobInstancesRepository,
        protected JobTrackRepository $jobTrackRepository,
    ) {}

    /**
     * The export jobs consuming the record, ordered by job code.
     *
     * @return list<ExportConsumer>
     */
    public function forRecord(Record $record): array
    {
        $typeCode = (string) $record->entityType?->code;

        if ($typeCode === '') {
            return [];
        }

        $status = $record->status->value;

        $jobs = $this->jobInstancesRepository->getModel()->newQuery()
            ->where('entity_type', self::EXPORTER)
            ->where('type', 'export')
            ->where('filters->'.RecordExporter::FILTER_ENTITY_TYPE, $typeCode)
            ->orderBy('code')
            ->limit(self::LIMIT)
            ->get(['id', 'code', 'filters'])
            ->filter(function (object $job) use ($status): bool {
                $statuses = RecordExporter::listFilter(((array) $job->filters)[RecordExporter::FILTER_STATUSES] ?? null);

                return $statuses === [] || in_array($status, $statuses, true);
            });

        if ($jobs->isEmpty()) {
            return [];
        }

        $runs = $this->lastRuns($jobs->pluck('id')->map(intval(...))->all());

        $updatedAt = $record->updated_at;

        return $jobs->map(function (object $job) use ($runs, $updatedAt): array {
            $filters = (array) $job->filters;

            $delta = (string) ($filters[RecordExporter::FILTER_CHANGED_SINCE_LAST_RUN] ?? '0') === '1';

            $run = $runs[(int) $job->id] ?? null;

            return [
                'id'          => (int) $job->id,
                'code'        => (string) $job->code,
                'format'      => (ExportFormat::tryFrom((string) ($filters[RecordExporter::FILTER_FORMAT] ?? '')) ?? ExportFormat::Jsonl)->extension(),
                'delta'       => $delta,
                'last_run_at' => $run['completed_at'] ?? null,
                'state'       => $this->state($run['started_at'] ?? null, $updatedAt, $delta),
            ];
        })->values()->all();
    }

    /**
     * The start and end of each job's last completed run, keyed by job instance id, in one grouped query.
     *
     * @param  list<int>  $jobIds
     * @return array<int, array{started_at: ?CarbonInterface, completed_at: ?CarbonInterface}>
     */
    protected function lastRuns(array $jobIds): array
    {
        $rows = $this->jobTrackRepository->getModel()->newQuery()
            ->toBase()
            ->whereIn('job_instances_id', $jobIds)
            ->where('state', Export::STATE_COMPLETED)
            ->whereNotNull('started_at')
            ->groupBy('job_instances_id')
            ->selectRaw('job_instances_id, max(started_at) as started_at, max(completed_at) as completed_at')
            ->get();

        $runs = [];

        foreach ($rows as $row) {
            $runs[(int) $row->job_instances_id] = [
                'started_at'   => $row->started_at === null ? null : Date::parse($row->started_at),
                'completed_at' => $row->completed_at === null ? null : Date::parse($row->completed_at),
            ];
        }

        return $runs;
    }

    /**
     * Whether the record's current version went out in the job's last run.
     */
    protected function state(?CarbonInterface $lastRunStartedAt, ?CarbonInterface $updatedAt, bool $delta): string
    {
        if (! $lastRunStartedAt instanceof CarbonInterface) {
            return self::STATE_NEVER_RUN;
        }

        if ($updatedAt?->greaterThanOrEqualTo($lastRunStartedAt)) {
            return self::STATE_PENDING;
        }

        return $delta ? self::STATE_UP_TO_DATE : self::STATE_INCLUDED;
    }
}
