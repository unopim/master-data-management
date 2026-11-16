<?php

namespace Webkul\MasterDataManagement\Observers;

use Webkul\DataTransfer\Helpers\Import;
use Webkul\DataTransfer\Models\JobTrack;
use Webkul\MasterDataManagement\Events\ChangeRequestBatchSubmitted;
use Webkul\MasterDataManagement\Listeners\AddImportReviewCounts;
use Webkul\MasterDataManagement\Repositories\ChangeRequestRepository;

/**
 * Announces the changes a master data import held before it failed or was cancelled; the core raises no event when a
 * job fails, so the state change on the job track is the only signal. A completed import is announced by
 * `AddImportReviewCounts`, and the batch listener sends each batch once whichever path fires first.
 */
class ImportTrackObserver
{
    public function __construct(protected ChangeRequestRepository $changeRequestRepository) {}

    /**
     * Announce the held changes of an import that just failed or was cancelled.
     */
    public function updated(JobTrack $track): void
    {
        if (
            ! $track->wasChanged('state')
            || ! in_array($track->state, [Import::STATE_FAILED, Import::STATE_CANCELLED], true)
            || $track->type !== 'import'
            || ! AddImportReviewCounts::isMasterDataImport($track->jobInstance?->entity_type)
        ) {
            return;
        }

        $batch = ChangeRequestBatchSubmitted::importKey((int) $track->id);

        if (! $this->changeRequestRepository->hasPendingInBatch($batch)) {
            return;
        }

        event(new ChangeRequestBatchSubmitted($batch, ChangeRequestBatchSubmitted::SOURCE_IMPORT, $track->jobInstance?->code));
    }
}
