<?php

namespace Webkul\MasterDataManagement\Services;

use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Webkul\MasterDataManagement\Contracts\ChangeGate;
use Webkul\MasterDataManagement\Enums\ChangeOperation;
use Webkul\MasterDataManagement\Enums\ChangeRequestStatus;
use Webkul\MasterDataManagement\Events\ChangeRequestSubmitted;
use Webkul\MasterDataManagement\Exceptions\ChangeRequestPendingException;
use Webkul\MasterDataManagement\Importers\Record\Importer;
use Webkul\MasterDataManagement\Matching\MatchInbox;
use Webkul\MasterDataManagement\Models\ChangeRequest;
use Webkul\MasterDataManagement\Models\Record;
use Webkul\MasterDataManagement\Repositories\ChangeRequestRepository;
use Webkul\MasterDataManagement\Security\ChangeApprovals;
use Webkul\MasterDataManagement\ValueObjects\ChangeSet;
use Webkul\MasterDataManagement\ValueObjects\GateResult;

/**
 * Holds writes to entity types that require approval in `mdm_change_requests` until an approver decides them.
 *
 * One target holds at most one pending request: a unique `pending_key`, cleared when the request is decided, refuses
 * a second one even when two submits race, on every database engine.
 */
class NativeChangeGate implements ChangeGate
{
    public function __construct(
        protected ChangeApprovals $changeApprovals,
        protected ChangeRequestRepository $changeRequestRepository,
    ) {}

    /**
     * Apply the change when the type needs no approval or the author may approve it, else store it as pending.
     *
     * @throws ChangeRequestPendingException
     * @throws ValidationException
     */
    public function submit(ChangeSet $set): GateResult
    {
        if (! $this->changeApprovals->requiresApproval($set->entityType, $set->author, $set->automated, $set->record, $set->parent)) {
            return GateResult::apply();
        }

        try {
            $changeRequest = DB::transaction(fn (): ChangeRequest => $this->changeRequestRepository->create([
                'entity_type_id' => $set->entityType->id,
                'record_id'      => $set->record?->id,
                'parent_id'      => $set->operation === ChangeOperation::Create ? ($set->after['parent_id'] ?? null) : null,
                'operation'      => $set->operation,
                'status'         => ChangeRequestStatus::Pending,
                'base_revision'  => $set->operation === ChangeOperation::Create ? null : $set->baseRevision,
                'before'         => $set->before,
                'after'          => $set->after,
                'input'          => $set->input,
                'origin'         => $set->source?->origin,
                'context'        => $set->source?->context ?: null,
                'requested_by'   => $set->author?->id,
                'pending_key'    => $this->pendingKey($set),
            ]));
        } catch (UniqueConstraintViolationException) {
            throw ChangeRequestPendingException::withMessages(['status' => trans('masterdata::app.approvals.already-pending')]);
        }

        event(new ChangeRequestSubmitted($changeRequest));

        return GateResult::pending((string) $changeRequest->id);
    }

    /**
     * The target a held change claims: the match inbox row it came from, else the record and kind of change (and,
     * for crosswalk changes, the source id). A create carrying the external id it links once approved claims that
     * id, so feeding the same source row again does not queue a second create; any other create claims nothing.
     */
    protected function pendingKey(ChangeSet $set): ?string
    {
        $candidateId = $set->source?->context[MatchInbox::CONTEXT_KEY] ?? null;

        if (is_numeric($candidateId)) {
            return hash('sha256', 'match_candidate|'.(int) $candidateId);
        }

        $link = $set->source?->context[Importer::CONTEXT_XREF] ?? null;

        if (! $set->record instanceof Record && is_array($link)) {
            return hash('sha256', implode('|', [
                'create_xref',
                (int) $set->entityType->id,
                (int) ($link[XrefWriter::INPUT_SYSTEM] ?? 0),
                (string) ($link[XrefWriter::INPUT_EXTERNAL_ID] ?? ''),
            ]));
        }

        if (! $set->record instanceof Record) {
            return null;
        }

        $parts = ['record', (int) $set->record->id, $set->operation->value];

        if ($set->operation->touchesXrefs()) {
            $parts[] = (string) json_encode($set->input);
        }

        return hash('sha256', implode('|', $parts));
    }
}
