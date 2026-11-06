<?php

namespace Webkul\MasterDataManagement\Quality;

use Carbon\CarbonInterface;
use Illuminate\Contracts\Database\Query\Builder as BuilderContract;
use Illuminate\Database\Query\Builder;
use Webkul\MasterDataManagement\Enums\ChangeRequestStatus;
use Webkul\MasterDataManagement\Enums\MatchCandidateStatus;
use Webkul\MasterDataManagement\Enums\WorkloadAgeBucket;
use Webkul\MasterDataManagement\Repositories\ChangeRequestRepository;
use Webkul\MasterDataManagement\Repositories\MatchCandidateRepository;
use Webkul\MasterDataManagement\Security\RecordScope;
use Webkul\User\Models\Admin;
use Webkul\User\Repositories\AdminRepository;

/**
 * The pending change requests and match candidates waiting on each steward, the data owner of their entity type,
 * counted by age in grouped SQL. Rows are limited to the visible entity types and, on scoped types, to records the
 * admin may view, as on the approvals and matching grids.
 */
class StewardWorkload
{
    public function __construct(
        protected ChangeRequestRepository $changeRequestRepository,
        protected MatchCandidateRepository $matchCandidateRepository,
        protected AdminRepository $adminRepository,
        protected RecordScope $recordScope,
    ) {}

    /**
     * The open tasks per steward as of the moment, most loaded first; tasks of types without a data owner come
     * under a null steward.
     *
     * @param  list<int>|null  $visibleEntityTypeIds  Null when every entity type is visible.
     * @return list<array{steward_id: int|null, steward_name: string|null, change_requests: int, match_candidates: int, buckets: array<string, int>}>
     */
    public function forAdmin(Admin $admin, ?array $visibleEntityTypeIds, CarbonInterface $now): array
    {
        if ($visibleEntityTypeIds === []) {
            return [];
        }

        $changeRequests = $this->grouped($this->changeRequests($admin, $visibleEntityTypeIds), 'mdm_change_requests.created_at', $now);

        $candidates = $this->grouped($this->matchCandidates($admin, $visibleEntityTypeIds), 'mdm_match_candidates.created_at', $now);

        $stewardIds = array_values(array_unique([...array_keys($changeRequests), ...array_keys($candidates)]));

        $names = $this->adminRepository->getModel()->newQuery()
            ->whereKey(array_filter($stewardIds))
            ->pluck('name', 'id')
            ->all();

        $rows = array_map(function (int $stewardId) use ($changeRequests, $candidates, $names): array {
            $buckets = [];

            foreach (WorkloadAgeBucket::cases() as $bucket) {
                $buckets[$bucket->value] = ($changeRequests[$stewardId]['buckets'][$bucket->value] ?? 0) + ($candidates[$stewardId]['buckets'][$bucket->value] ?? 0);
            }

            return [
                'steward_id'       => $stewardId === 0 ? null : $stewardId,
                'steward_name'     => $names[$stewardId] ?? null,
                'change_requests'  => $changeRequests[$stewardId]['total'] ?? 0,
                'match_candidates' => $candidates[$stewardId]['total'] ?? 0,
                'buckets'          => $buckets,
            ];
        }, $stewardIds);

        usort($rows, fn (array $a, array $b): int => [$b['change_requests'] + $b['match_candidates'], $a['steward_id'] ?? PHP_INT_MAX]
            <=> [$a['change_requests'] + $a['match_candidates'], $b['steward_id'] ?? PHP_INT_MAX]);

        return $rows;
    }

    /**
     * The pending change requests the admin may see, joined to their entity type.
     *
     * @param  list<int>|null  $visibleEntityTypeIds
     */
    protected function changeRequests(Admin $admin, ?array $visibleEntityTypeIds): Builder
    {
        $query = $this->changeRequestRepository->getModel()->newQuery()->toBase()
            ->join('mdm_entity_types', 'mdm_entity_types.id', '=', 'mdm_change_requests.entity_type_id')
            ->leftJoin('mdm_records', 'mdm_records.id', '=', 'mdm_change_requests.record_id')
            ->leftJoin('mdm_records as proposed_parents', 'proposed_parents.id', '=', 'mdm_change_requests.parent_id')
            ->where('mdm_change_requests.status', ChangeRequestStatus::Pending->value)
            ->when($visibleEntityTypeIds !== null, fn (Builder $query) => $query->whereIn('mdm_change_requests.entity_type_id', $visibleEntityTypeIds));

        $this->recordScope->applyToRows($query, $admin, 'mdm_change_requests.entity_type_id', 'mdm_records.path', 'mdm_change_requests.record_id');

        return $query->where(fn (BuilderContract $creates) => $creates
            ->whereNotNull('mdm_change_requests.record_id')
            ->orWhere(fn (BuilderContract $placed): BuilderContract => $this->recordScope->applyToRows($placed->whereNull('mdm_change_requests.record_id'), $admin, 'mdm_change_requests.entity_type_id', 'proposed_parents.path')));
    }

    /**
     * The pending match candidates the admin may see, joined to their entity type.
     *
     * @param  list<int>|null  $visibleEntityTypeIds
     */
    protected function matchCandidates(Admin $admin, ?array $visibleEntityTypeIds): Builder
    {
        $query = $this->matchCandidateRepository->getModel()->newQuery()->toBase()
            ->join('mdm_entity_types', 'mdm_entity_types.id', '=', 'mdm_match_candidates.entity_type_id')
            ->leftJoin('mdm_records as matched_records', 'matched_records.id', '=', 'mdm_match_candidates.matched_record_id')
            ->where('mdm_match_candidates.status', MatchCandidateStatus::Pending->value)
            ->when($visibleEntityTypeIds !== null, fn (Builder $query) => $query->whereIn('mdm_match_candidates.entity_type_id', $visibleEntityTypeIds));

        return $this->recordScope->applyToRows($query, $admin, 'mdm_match_candidates.entity_type_id', 'matched_records.path', 'mdm_match_candidates.matched_record_id');
    }

    /**
     * Count the rows per data owner and age bucket; ages are compared against bound timestamps so no engine date
     * function is needed. Rows without an owner are keyed 0.
     *
     * @return array<int, array{total: int, buckets: array<string, int>}>
     */
    protected function grouped(Builder $query, string $createdColumn, CarbonInterface $now): array
    {
        $column = $query->getGrammar()->wrap($createdColumn);

        $query->groupBy('mdm_entity_types.data_owner_id')
            ->select('mdm_entity_types.data_owner_id')
            ->selectRaw('count(*) as total');

        foreach (WorkloadAgeBucket::cases() as $bucket) {
            [$from, $to] = $bucket->window($now);

            $conditions = array_filter([$from === null ? null : $column.' <= ?', $to === null ? null : $column.' > ?']);

            $query->selectRaw(
                'sum(case when '.implode(' and ', $conditions).' then 1 else 0 end) as '.$query->getGrammar()->wrap('bucket_'.$bucket->value),
                array_values(array_filter([$from, $to])),
            );
        }

        $rows = [];

        foreach ($query->get() as $row) {
            $buckets = [];

            foreach (WorkloadAgeBucket::cases() as $bucket) {
                $buckets[$bucket->value] = (int) $row->{'bucket_'.$bucket->value};
            }

            $rows[(int) $row->data_owner_id] = ['total' => (int) $row->total, 'buckets' => $buckets];
        }

        return $rows;
    }
}
