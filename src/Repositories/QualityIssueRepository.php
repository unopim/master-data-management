<?php

namespace Webkul\MasterDataManagement\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Webkul\Core\Eloquent\Repository;
use Webkul\MasterDataManagement\Contracts\QualityIssue;
use Webkul\MasterDataManagement\Enums\QualityIssueStatus;
use Webkul\MasterDataManagement\Models\QualityIssue as QualityIssueModel;
use Webkul\MasterDataManagement\Quality\DetectedIssue;

class QualityIssueRepository extends Repository
{
    /**
     * The resolution of an issue a later check no longer found.
     */
    public const string RESOLUTION_AUTO = 'auto';

    /**
     * The resolution of an issue a steward closed.
     */
    public const string RESOLUTION_MANUAL = 'manual';

    /**
     * Get model class name.
     */
    public function model(): string
    {
        return QualityIssue::class;
    }

    /**
     * Bring the stored issues of the given rules involving the records in line with a fresh evaluation.
     *
     * New findings are inserted open, resolved ones found again reopen, ignored ones stay ignored, and open issues
     * the evaluation no longer reports are resolved automatically. An issue involves a record as its owner or as
     * the other record of a pair. Concurrent evaluations of one record insert each finding once.
     *
     * @param  list<int>  $recordIds
     * @param  list<string>  $rules
     * @param  array<string, DetectedIssue>  $detected  Keyed by DetectedIssue::identity().
     */
    public function syncFindings(int $entityTypeId, array $recordIds, array $rules, array $detected): void
    {
        if ($recordIds === [] || $rules === []) {
            return;
        }

        DB::transaction(function () use ($entityTypeId, $recordIds, $rules, $detected): void {
            $existing = $this->model->newQuery()
                ->whereIn('rule', $rules)
                ->where(fn (Builder $query) => $query->whereIn('record_id', $recordIds)->orWhereIn('related_record_id', $recordIds))
                ->get()
                ->keyBy(fn (QualityIssueModel $issue): string => DetectedIssue::identityOf((int) $issue->record_id, $issue->rule, $issue->rule_key));

            $now = now();

            $inserts = [];

            foreach ($detected as $identity => $finding) {
                $issue = $existing->get($identity);

                if (! $issue instanceof QualityIssueModel) {
                    $inserts[] = [
                        'entity_type_id'    => $entityTypeId,
                        'record_id'         => $finding->recordId,
                        'related_record_id' => $finding->relatedRecordId,
                        'rule'              => $finding->rule,
                        'rule_key'          => $finding->key,
                        'severity'          => $finding->severity->value,
                        'status'            => QualityIssueStatus::Open->value,
                        'details'           => json_encode($finding->details, JSON_THROW_ON_ERROR),
                        'detected_at'       => $now,
                        'created_at'        => $now,
                        'updated_at'        => $now,
                    ];

                    continue;
                }

                if ($issue->status === QualityIssueStatus::Ignored) {
                    continue;
                }

                $issue->fill([
                    'severity'          => $finding->severity,
                    'details'           => $finding->details,
                    'related_record_id' => $finding->relatedRecordId,
                ]);

                if ($issue->status === QualityIssueStatus::Resolved) {
                    $issue->fill([
                        'status'      => QualityIssueStatus::Open,
                        'detected_at' => $now,
                        'resolved_at' => null,
                        'resolved_by' => null,
                        'resolution'  => null,
                    ]);
                }

                if ($issue->isDirty()) {
                    $issue->save();
                }
            }

            $fixed = $existing
                ->filter(fn (QualityIssueModel $issue, string $identity): bool => $issue->status === QualityIssueStatus::Open && ! isset($detected[$identity]))
                ->modelKeys();

            if ($fixed !== []) {
                $this->model->newQuery()->whereKey($fixed)->update([
                    'status'      => QualityIssueStatus::Resolved->value,
                    'resolution'  => self::RESOLUTION_AUTO,
                    'resolved_at' => $now,
                    'resolved_by' => null,
                ]);
            }

            foreach (array_chunk($inserts, 500) as $chunk) {
                $this->model->newQuery()->insertOrIgnore($chunk);
            }
        });
    }

    /**
     * Close an issue by a steward's decision: resolved or ignored.
     */
    public function decide(QualityIssueModel $issue, QualityIssueStatus $status, int $adminId): QualityIssueModel
    {
        $issue->fill([
            'status'      => $status,
            'resolution'  => self::RESOLUTION_MANUAL,
            'resolved_at' => now(),
            'resolved_by' => $adminId,
        ])->save();

        return $issue;
    }

    /**
     * Count the open issues per entity type and severity in one grouped query.
     *
     * @param  list<int>  $entityTypeIds
     * @return array<int, array<string, int>> Severity counts keyed by entity type id.
     */
    public function openCountsByEntityType(array $entityTypeIds): array
    {
        if ($entityTypeIds === []) {
            return [];
        }

        $counts = [];

        $this->model->newQuery()
            ->toBase()
            ->whereIn('entity_type_id', $entityTypeIds)
            ->where('status', QualityIssueStatus::Open->value)
            ->groupBy('entity_type_id', 'severity')
            ->select('entity_type_id', 'severity')
            ->selectRaw('count(*) as total')
            ->get()
            ->each(function (object $row) use (&$counts): void {
                $counts[(int) $row->entity_type_id][(string) $row->severity] = (int) $row->total;
            });

        return $counts;
    }

    /**
     * The open and ignored issues involving the record, as owner or as the other record of a pair, newest first,
     * with the owner's and the other record's code and uuid.
     *
     * @return list<object>
     */
    public function involvingRecord(int $recordId, int $limit = 50): array
    {
        return DB::table('mdm_dq_issues')
            ->join('mdm_records as owner', 'owner.id', '=', 'mdm_dq_issues.record_id')
            ->leftJoin('mdm_records as related', 'related.id', '=', 'mdm_dq_issues.related_record_id')
            ->where(fn (\Illuminate\Contracts\Database\Query\Builder $query) => $query->where('mdm_dq_issues.record_id', $recordId)->orWhere('mdm_dq_issues.related_record_id', $recordId))
            ->whereIn('mdm_dq_issues.status', [QualityIssueStatus::Open->value, QualityIssueStatus::Ignored->value])
            ->orderByDesc('mdm_dq_issues.id')
            ->limit($limit)
            ->get([
                'mdm_dq_issues.id',
                'mdm_dq_issues.record_id',
                'mdm_dq_issues.related_record_id',
                'mdm_dq_issues.rule',
                'mdm_dq_issues.severity',
                'mdm_dq_issues.status',
                'mdm_dq_issues.details',
                'mdm_dq_issues.detected_at',
                'owner.code as owner_code',
                'owner.uuid as owner_uuid',
                'related.code as related_code',
                'related.uuid as related_uuid',
            ])
            ->all();
    }

    /**
     * Delete the quality issues raised on the records or naming them as the related record.
     *
     * @param  list<int>  $recordIds
     */
    public function deleteForRecords(array $recordIds): void
    {
        $this->model->newQuery()
            ->whereIn('record_id', $recordIds)
            ->orWhereIn('related_record_id', $recordIds)
            ->toBase()
            ->delete();
    }
}
