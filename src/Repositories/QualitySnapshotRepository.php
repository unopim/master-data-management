<?php

namespace Webkul\MasterDataManagement\Repositories;

use Carbon\CarbonInterface;
use Illuminate\Database\Query\Builder;
use Webkul\Core\Eloquent\Repository;
use Webkul\MasterDataManagement\Contracts\QualitySnapshot;

class QualitySnapshotRepository extends Repository
{
    /**
     * The aggregate columns a snapshot stores.
     */
    public const array METRIC_COLUMNS = ['record_count', 'completeness_avg', 'validity_rate', 'duplicate_rate', 'open_tasks'];

    /**
     * Get model class name.
     */
    public function model(): string
    {
        return QualitySnapshot::class;
    }

    /**
     * Store the snapshots, replacing those already taken for the same entity type and day, so a rerun is idempotent.
     *
     * @param  list<array<string, mixed>>  $rows  Each with entity_type_id, snapshot_date and the metric columns.
     */
    public function store(array $rows): void
    {
        if ($rows === []) {
            return;
        }

        $now = now();

        $rows = array_map(fn (array $row): array => $row + ['created_at' => $now, 'updated_at' => $now], $rows);

        $this->model->newQuery()->upsert($rows, ['entity_type_id', 'snapshot_date'], [...self::METRIC_COLUMNS, 'updated_at']);
    }

    /**
     * The latest snapshot of each entity type with the one taken before it, keyed by entity type id.
     *
     * @param  list<int>  $entityTypeIds
     * @return array<int, array{latest: array<string, mixed>, previous: array<string, mixed>|null}>
     */
    public function latestWithPrevious(array $entityTypeIds): array
    {
        if ($entityTypeIds === []) {
            return [];
        }

        $latestDates = $this->baseQuery()
            ->whereIn('entity_type_id', $entityTypeIds)
            ->groupBy('entity_type_id')
            ->select('entity_type_id')
            ->selectRaw('max('.$this->wrap('snapshot_date').') as latest_date');

        $previousDates = $this->baseQuery()
            ->joinSub($latestDates, 'latest_dates', 'latest_dates.entity_type_id', '=', 'mdm_quality_snapshots.entity_type_id')
            ->whereColumn('mdm_quality_snapshots.snapshot_date', '<', 'latest_dates.latest_date')
            ->groupBy('mdm_quality_snapshots.entity_type_id')
            ->select('mdm_quality_snapshots.entity_type_id')
            ->selectRaw('max('.$this->wrap('mdm_quality_snapshots.snapshot_date').') as latest_date');

        $latest = $this->rowsOn($latestDates);

        $previous = $this->rowsOn($previousDates);

        $pairs = [];

        foreach ($latest as $typeId => $row) {
            $pairs[$typeId] = ['latest' => $row, 'previous' => $previous[$typeId] ?? null];
        }

        return $pairs;
    }

    /**
     * The snapshots of the entity type taken on or after the day, oldest first.
     *
     * @return list<array<string, mixed>>
     */
    public function trend(int $entityTypeId, CarbonInterface $since): array
    {
        return $this->baseQuery()
            ->where('entity_type_id', $entityTypeId)
            ->where('snapshot_date', '>=', $since->toDateString())
            ->oldest('snapshot_date')
            ->get(['entity_type_id', 'snapshot_date', ...self::METRIC_COLUMNS])
            ->map(fn (object $row): array => $this->normalize($row))
            ->values()
            ->all();
    }

    /**
     * The snapshot rows matching the entity type and day pairs the subquery selects, keyed by entity type id.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function rowsOn(Builder $dates): array
    {
        return $this->baseQuery()
            ->joinSub($dates, 'picked', function ($join): void {
                $join->on('picked.entity_type_id', '=', 'mdm_quality_snapshots.entity_type_id')
                    ->on('picked.latest_date', '=', 'mdm_quality_snapshots.snapshot_date');
            })
            ->get(array_map(fn (string $column): string => 'mdm_quality_snapshots.'.$column, ['entity_type_id', 'snapshot_date', ...self::METRIC_COLUMNS]))
            ->mapWithKeys(fn (object $row): array => [(int) $row->entity_type_id => $this->normalize($row)])
            ->all();
    }

    /**
     * Cast a snapshot row read through the query builder, whose numbers come back as strings on some engines.
     *
     * @return array<string, mixed>
     */
    protected function normalize(object $row): array
    {
        $rate = fn (mixed $value): ?float => $value === null ? null : round((float) $value, 2);

        return [
            'entity_type_id'   => (int) $row->entity_type_id,
            'snapshot_date'    => substr((string) $row->snapshot_date, 0, 10),
            'record_count'     => (int) $row->record_count,
            'completeness_avg' => $rate($row->completeness_avg),
            'validity_rate'    => $rate($row->validity_rate),
            'duplicate_rate'   => $rate($row->duplicate_rate),
            'open_tasks'       => (int) $row->open_tasks,
        ];
    }

    protected function baseQuery(): Builder
    {
        return $this->model->newQuery()->toBase();
    }

    protected function wrap(string $column): string
    {
        return $this->model->getConnection()->getQueryGrammar()->wrap($column);
    }
}
