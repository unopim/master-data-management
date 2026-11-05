<?php

namespace Webkul\MasterDataManagement\Services;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Webkul\MasterDataManagement\Enums\ChangeOperation;
use Webkul\MasterDataManagement\Enums\ChangeRequestStatus;
use Webkul\MasterDataManagement\Events\ChangeRequestDecided;
use Webkul\MasterDataManagement\Exceptions\ChangeRequestConflictException;
use Webkul\MasterDataManagement\Exceptions\StaleRecordException;
use Webkul\MasterDataManagement\Exceptions\XrefConflictException;
use Webkul\MasterDataManagement\Models\ChangeRequest;
use Webkul\MasterDataManagement\Models\Record;
use Webkul\MasterDataManagement\Repositories\ChangeRequestRepository;
use Webkul\MasterDataManagement\Repositories\RecordRepository;
use Webkul\MasterDataManagement\Repositories\SourceSystemRepository;
use Webkul\MasterDataManagement\ValueObjects\GateResult;
use Webkul\MasterDataManagement\ValueObjects\RecordWriteResult;
use Webkul\MasterDataManagement\ValueObjects\SourceContext;
use Webkul\User\Models\Admin;

/**
 * Decides held record changes: approval replays the stored writer input through the record writer at the revision
 * the requester read, so a record changed since the request is never overwritten. Held crosswalk links and unlinks
 * replay through the crosswalk writer; they do not depend on the record revision.
 */
class ChangeRequestService
{
    public function __construct(
        protected ChangeRequestRepository $changeRequestRepository,
        protected RecordRepository $recordRepository,
        protected RecordWriter $recordWriter,
        protected XrefWriter $xrefWriter,
        protected RecordMerger $recordMerger,
        protected SourceSystemRepository $sourceSystemRepository,
    ) {}

    /**
     * Whether the record changed since the request was made, so approving it would be refused.
     */
    public function hasConflict(ChangeRequest $changeRequest, ?Record $record): bool
    {
        if ($changeRequest->operation === ChangeOperation::Create) {
            return false;
        }

        if (! $record instanceof Record) {
            return true;
        }

        return ! $changeRequest->operation->touchesXrefs() && (int) $record->revision !== (int) $changeRequest->base_revision;
    }

    /**
     * Apply the held change through the record writer and record the approver.
     *
     * A record changed since the request marks the request stale and is refused.
     *
     * @throws ChangeRequestConflictException
     * @throws ValidationException
     * @throws AuthorizationException
     */
    public function approve(ChangeRequest $changeRequest, Admin $by, ?string $comment): ChangeRequest
    {
        $changeRequest = DB::transaction(function () use ($changeRequest, $by, $comment): ChangeRequest {
            $locked = $this->lockPending($changeRequest);

            $record = $locked->record_id === null ? null : $this->recordRepository->find($locked->record_id);

            if ($this->hasConflict($locked, $record)) {
                return $this->close($locked, ChangeRequestStatus::Stale, $by, $comment);
            }

            try {
                $result = $this->apply($locked, $record, $by);
            } catch (StaleRecordException) {
                return $this->close($locked, ChangeRequestStatus::Stale, $by, $comment);
            }

            throw_if($result->isPending(), AuthorizationException::class);

            $locked->record_id = $result->record->id;

            return $this->close($locked, ChangeRequestStatus::Approved, $by, $comment);
        });

        throw_if($changeRequest->status === ChangeRequestStatus::Stale, ChangeRequestConflictException::class, $changeRequest);

        return $changeRequest;
    }

    /**
     * Close the held change without applying it.
     *
     * @throws ValidationException
     */
    public function reject(ChangeRequest $changeRequest, Admin $by, ?string $comment): ChangeRequest
    {
        return DB::transaction(fn (): ChangeRequest => $this->close($this->lockPending($changeRequest), ChangeRequestStatus::Rejected, $by, $comment));
    }

    /**
     * Replay the stored writer input; the approver may approve, so the change gate lets it through.
     *
     * @throws ValidationException
     * @throws StaleRecordException
     */
    protected function apply(ChangeRequest $changeRequest, ?Record $record, Admin $by): RecordWriteResult
    {
        $source = new SourceContext('approval');

        $revision = (int) $changeRequest->base_revision;

        return match ($changeRequest->operation) {
            ChangeOperation::Create     => $this->recordWriter->submitCreate($changeRequest->entityType, $changeRequest->input ?? [], $by, $source),
            ChangeOperation::Update     => $this->recordWriter->submitUpdate($record, $changeRequest->input ?? [], $revision, $by, $source),
            ChangeOperation::Retire     => $this->recordWriter->submitRetire($record, $revision, $by, $source),
            ChangeOperation::Reactivate => $this->recordWriter->submitReactivate($record, $revision, $by, $source),
            ChangeOperation::LinkXref   => new RecordWriteResult($record, $this->applyLink($changeRequest, $record, $by, $source)),
            ChangeOperation::UnlinkXref => new RecordWriteResult($record, $this->applyUnlink($changeRequest, $record, $by, $source)),
            ChangeOperation::Merge      => $this->applyMerge($changeRequest, $record, $revision, $by, $source),
            ChangeOperation::Unmerge    => $this->recordMerger->unmerge($record, $by, $source),
        };
    }

    /**
     * Replay a held link; a source system deleted since turns the request stale, an id another record took since is
     * refused on the decision form.
     *
     * @throws ValidationException
     * @throws StaleRecordException
     */
    protected function applyLink(ChangeRequest $changeRequest, Record $record, Admin $by, SourceContext $source): GateResult
    {
        $input = $changeRequest->input ?? [];

        $system = $this->sourceSystemRepository->find((int) ($input[XrefWriter::INPUT_SYSTEM] ?? 0))
            ?? throw new StaleRecordException($record, (int) $changeRequest->base_revision);

        try {
            return $this->xrefWriter->link($record, $system, (string) ($input[XrefWriter::INPUT_EXTERNAL_ID] ?? ''), $by, $source);
        } catch (XrefConflictException $exception) {
            throw ValidationException::withMessages(['comment' => $exception->getMessage()]);
        }
    }

    /**
     * Replay a held unlink; an entry no longer active on the record turns the request stale.
     *
     * @throws ValidationException
     * @throws StaleRecordException
     */
    protected function applyUnlink(ChangeRequest $changeRequest, Record $record, Admin $by, SourceContext $source): GateResult
    {
        return $this->xrefWriter->unlink($record, (int) (($changeRequest->input ?? [])[XrefWriter::INPUT_XREF] ?? 0), $by, $source)
            ?? throw new StaleRecordException($record, (int) $changeRequest->base_revision);
    }

    /**
     * Replay a held merge; a merged record deleted since turns the request stale.
     *
     * @throws ValidationException
     * @throws StaleRecordException
     */
    protected function applyMerge(ChangeRequest $changeRequest, Record $survivor, int $revision, Admin $by, SourceContext $source): RecordWriteResult
    {
        $input = $changeRequest->input ?? [];

        $merged = $this->recordRepository->find((int) ($input[RecordMerger::INPUT_MERGED] ?? 0))
            ?? throw new StaleRecordException($survivor, $revision);

        return $this->recordMerger->merge(
            $survivor,
            $merged,
            (array) ($input[RecordMerger::INPUT_CHOICES] ?? []),
            $revision,
            (int) ($input[RecordMerger::INPUT_MERGED_REVISION] ?? 0),
            $by,
            $source,
        );
    }

    /**
     * Lock the request row and refuse a request that was decided meanwhile.
     *
     * @throws ValidationException
     */
    protected function lockPending(ChangeRequest $changeRequest): ChangeRequest
    {
        $locked = $this->changeRequestRepository->findForUpdate((int) $changeRequest->id);

        if (! $locked instanceof ChangeRequest || ! $locked->isPending()) {
            throw ValidationException::withMessages(['comment' => trans('masterdata::app.approvals.not-pending')]);
        }

        return $locked;
    }

    /**
     * Store the decision on the request.
     */
    protected function close(ChangeRequest $changeRequest, ChangeRequestStatus $status, Admin $by, ?string $comment): ChangeRequest
    {
        $changeRequest->fill([
            'status'      => $status,
            'decided_by'  => $by->id,
            'decided_at'  => now(),
            'comment'     => $comment === '' ? null : $comment,
            'pending_key' => null,
        ])->save();

        event(new ChangeRequestDecided($changeRequest));

        return $changeRequest;
    }
}
