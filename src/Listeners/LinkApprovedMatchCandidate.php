<?php

namespace Webkul\MasterDataManagement\Listeners;

use Webkul\MasterDataManagement\Enums\ChangeOperation;
use Webkul\MasterDataManagement\Enums\ChangeRequestStatus;
use Webkul\MasterDataManagement\Events\ChangeRequestDecided;
use Webkul\MasterDataManagement\Matching\MatchInbox;
use Webkul\MasterDataManagement\Models\Record;
use Webkul\MasterDataManagement\Repositories\RecordRepository;
use Webkul\User\Models\Admin;

/**
 * Completes an inbox decision that waited for approval: an approved "create new record" gives the new record the
 * row's external id, an approved link closes the row as matched.
 */
class LinkApprovedMatchCandidate
{
    public function __construct(
        protected MatchInbox $matchInbox,
        protected RecordRepository $recordRepository,
    ) {}

    /**
     * Link and close the inbox row an approved create or link came from.
     */
    public function handle(ChangeRequestDecided $event): void
    {
        $changeRequest = $event->changeRequest;

        $candidateId = $changeRequest->context[MatchInbox::CONTEXT_KEY] ?? null;

        if (
            $changeRequest->status !== ChangeRequestStatus::Approved
            || ! in_array($changeRequest->operation, [ChangeOperation::Create, ChangeOperation::LinkXref], true)
            || $changeRequest->record_id === null
            || ! is_numeric($candidateId)
        ) {
            return;
        }

        $record = $this->recordRepository->find($changeRequest->record_id);

        $decider = $changeRequest->decider;

        if (! $record instanceof Record || ! $decider instanceof Admin) {
            return;
        }

        if ($changeRequest->operation === ChangeOperation::LinkXref) {
            $this->matchInbox->completeApprovedLink((int) $candidateId, $record, $decider);

            return;
        }

        $this->matchInbox->completeApprovedCreate((int) $candidateId, $record, $decider);
    }
}
