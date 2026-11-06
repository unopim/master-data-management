<?php

namespace Webkul\MasterDataManagement\Quality;

use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Webkul\Core\Eloquent\Repository;
use Webkul\MasterDataManagement\Enums\ChangeRequestStatus;
use Webkul\MasterDataManagement\Enums\EntityTypeStatus;
use Webkul\MasterDataManagement\Enums\MatchCandidateStatus;
use Webkul\MasterDataManagement\Repositories\ChangeRequestRepository;
use Webkul\MasterDataManagement\Repositories\EntityTypeRepository;
use Webkul\MasterDataManagement\Repositories\MatchCandidateRepository;
use Webkul\MasterDataManagement\Repositories\QualityIssueRepository;
use Webkul\MasterDataManagement\Repositories\QualitySnapshotRepository;
use Webkul\MasterDataManagement\Repositories\RecordRepository;

/**
 * Takes the day's quality snapshot of every active entity type from grouped SQL aggregates over the stored
 * completeness, quality issues, change requests and match candidates, a chunk of entity types at a time.
 *
 * Rates are percentages of the draft and active records; a type without such records stores none.
 */
class QualitySnapshotCalculator
{
    /**
     * The entity types aggregated per round of grouped queries.
     */
    protected const int CHUNK = 100;

    public function __construct(
        protected EntityTypeRepository $entityTypeRepository,
        protected RecordRepository $recordRepository,
        protected QualityIssueRepository $qualityIssueRepository,
        protected ChangeRequestRepository $changeRequestRepository,
        protected MatchCandidateRepository $matchCandidateRepository,
        protected QualitySnapshotRepository $qualitySnapshotRepository,
    ) {}

    /**
     * Store the snapshot of each active entity type for the day, replacing one already taken; returns the number of
     * entity types captured.
     */
    public function capture(CarbonInterface $date): int
    {
        $day = $date->toDateString();

        $captured = 0;

        $this->entityTypeRepository->getModel()->newQuery()
            ->toBase()
            ->where('status', EntityTypeStatus::Active->value)
            ->select('id')
            ->chunkById(self::CHUNK, function (Collection $types) use ($day, &$captured): void {
                $ids = $types->map(fn (object $type): int => (int) $type->id)->values()->all();

                $this->qualitySnapshotRepository->store($this->compute($ids, $day));

                $captured += count($ids);
            });

        return $captured;
    }

    /**
     * The snapshot rows of the entity types for the day.
     *
     * @param  list<int>  $ids
     * @return list<array<string, mixed>>
     */
    protected function compute(array $ids, string $day): array
    {
        $records = $this->recordRepository->qualityAggregates($ids);

        $issues = $this->qualityIssueRepository->openCountsByEntityType($ids);

        $changeRequests = $this->countsByType($this->changeRequestRepository, $ids, ChangeRequestStatus::Pending->value);

        $candidates = $this->countsByType($this->matchCandidateRepository, $ids, MatchCandidateStatus::Pending->value);

        return array_map(function (int $id) use ($records, $issues, $changeRequests, $candidates, $day): array {
            $aggregate = $records[$id] ?? ['records' => 0, 'average' => null, 'invalid' => 0, 'duplicates' => 0];

            $total = $aggregate['records'];

            $share = fn (int $count): ?float => $total === 0 ? null : round(100 * $count / $total, 2);

            return [
                'entity_type_id'   => $id,
                'snapshot_date'    => $day,
                'record_count'     => $total,
                'completeness_avg' => $total === 0 || $aggregate['average'] === null ? null : round($aggregate['average'], 2),
                'validity_rate'    => $share($total - $aggregate['invalid']),
                'duplicate_rate'   => $share($aggregate['duplicates']),
                'open_tasks'       => array_sum($issues[$id] ?? []) + ($changeRequests[$id] ?? 0) + ($candidates[$id] ?? 0),
            ];
        }, $ids);
    }

    /**
     * Count the rows with the status per entity type in one grouped query.
     *
     * @param  list<int>  $ids
     * @return array<int, int>
     */
    protected function countsByType(Repository $repository, array $ids, string $status): array
    {
        return $repository->getModel()->newQuery()
            ->toBase()
            ->whereIn('entity_type_id', $ids)
            ->where('status', $status)
            ->groupBy('entity_type_id')
            ->select('entity_type_id')
            ->selectRaw('count(*) as total')
            ->pluck('total', 'entity_type_id')
            ->mapWithKeys(fn (mixed $total, mixed $id): array => [(int) $id => (int) $total])
            ->all();
    }
}
