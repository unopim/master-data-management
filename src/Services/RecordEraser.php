<?php

namespace Webkul\MasterDataManagement\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Webkul\HistoryControl\Repositories\AuditRepository;
use Webkul\MasterDataManagement\Enums\RecordStatus;
use Webkul\MasterDataManagement\Events\RecordErased;
use Webkul\MasterDataManagement\Exceptions\StaleRecordException;
use Webkul\MasterDataManagement\Models\Record;
use Webkul\MasterDataManagement\Repositories\ChangeRequestRepository;
use Webkul\MasterDataManagement\Repositories\MatchCandidateRepository;
use Webkul\MasterDataManagement\Repositories\QualityIssueRepository;
use Webkul\MasterDataManagement\Repositories\RecordMergeRepository;
use Webkul\MasterDataManagement\Repositories\RecordRepository;
use Webkul\User\Models\Admin;

/**
 * Permanently removes the personal data of a retired record while keeping the row as an erased tombstone, so its
 * UUID, source IDs, hierarchy and relations still resolve and a source system cannot recreate it on the next sync.
 */
class RecordEraser
{
    /**
     * The history tag shared by records and their source IDs.
     */
    public const string HISTORY_TAG = 'masterdata_record';

    public function __construct(
        protected RecordRepository $recordRepository,
        protected RecordIndexer $recordIndexer,
        protected AuditRepository $auditRepository,
        protected ChangeRequestRepository $changeRequestRepository,
        protected QualityIssueRepository $qualityIssueRepository,
        protected MatchCandidateRepository $matchCandidateRepository,
        protected RecordMergeRepository $recordMergeRepository,
    ) {}

    /**
     * Erase a retired record and the duplicates merged into it at the revision the caller read.
     *
     * Values, label and code are wiped, and so are their copies in history, change requests, merge snapshots,
     * quality issues, match candidates and the search index. Erasing cannot be undone and bypasses the change gate,
     * since a held change request would keep another copy of the data being erased.
     *
     * @throws ValidationException
     * @throws StaleRecordException
     */
    public function erase(Record $record, int $expectedRevision, ?Admin $by): Record
    {
        $this->ensureErasable($record);

        $ids = DB::transaction(function () use ($record, $expectedRevision, $by): array {
            throw_unless($this->recordRepository->claimRevision($record, $expectedRevision), StaleRecordException::class, $record, $expectedRevision);

            $record->refresh();

            $this->ensureErasable($record);

            $ids = $this->recordRepository->mergedFamilyIds((int) $record->id);

            $family = $this->recordRepository->lockForIndexing($ids);

            foreach ($family as $member) {
                $member->forceFill([
                    'code'         => 'erased-'.$member->uuid,
                    'label'        => null,
                    'values'       => null,
                    'status'       => RecordStatus::Erased,
                    'completeness' => null,
                    'dq_score'     => null,
                    'updated_by'   => $by?->id,
                ])->saveQuietly();
            }

            $this->recordIndexer->syncMany($family);
            $this->auditRepository->deleteForHistory(self::HISTORY_TAG, $ids);
            $this->changeRequestRepository->eraseForRecords($ids);
            $this->qualityIssueRepository->deleteForRecords($ids);
            $this->matchCandidateRepository->eraseForRecords($ids);
            $this->recordMergeRepository->eraseForRecords($ids);

            return $ids;
        });

        $record->refresh();

        event(new RecordErased($record, $ids, $by));

        return $record;
    }

    /**
     * Only a retired record may be erased, so erasing always follows a deliberate, reversible retire.
     *
     * @throws ValidationException
     */
    protected function ensureErasable(Record $record): void
    {
        if ($record->status !== RecordStatus::Retired) {
            throw ValidationException::withMessages(['status' => trans('masterdata::app.records.erase.not-retired')]);
        }
    }
}
