<?php

namespace Webkul\MasterDataManagement\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Webkul\MasterDataManagement\Enums\ChangeRequestStatus;
use Webkul\MasterDataManagement\Events\ChangeRequestDecided;
use Webkul\MasterDataManagement\Services\ChangeRequestNotifier;
use Webkul\User\Models\Admin;

/**
 * Tells the admin who asked for a held change that it was approved or rejected, with the approver's comment. An
 * automated change has nobody to tell, and an admin who decided their own request already knows.
 */
class NotifyChangeRequestRequester implements ShouldQueue
{
    /**
     * The notification type the decision notifications are stored with.
     */
    public const string TYPE = 'masterdata.decision';

    public function __construct(protected ChangeRequestNotifier $notifier) {}

    /**
     * Notify the requester of an approved or rejected change request.
     */
    public function handle(ChangeRequestDecided $event): void
    {
        $changeRequest = $event->changeRequest;

        $status = $changeRequest->status;

        if (
            ! in_array($status, [ChangeRequestStatus::Approved, ChangeRequestStatus::Rejected], true)
            || $changeRequest->requested_by === null
            || $changeRequest->requested_by === $changeRequest->decided_by
        ) {
            return;
        }

        $changeRequest->loadMissing(['entityType.translations', 'requester', 'decider:id,name']);

        $requester = $changeRequest->requester;

        if (! $requester instanceof Admin || $requester->type !== 'user' || ! $requester->status) {
            return;
        }

        $type = $changeRequest->entityType;

        $this->notifier->send(
            type: self::TYPE,
            adminIds: [(int) $requester->id],
            route: 'admin.masterdata.approvals.show',
            routeParams: ['id' => (int) $changeRequest->id],
            title: trans('masterdata::app.approvals.notification.'.$status->value.'-title', ['id' => $changeRequest->id]),
            description: trans('masterdata::app.approvals.notification.'.$status->value.'-description', [
                'decider'   => (string) $changeRequest->decider?->name,
                'operation' => trans('masterdata::app.approvals.operations.'.$changeRequest->operation->value),
                'type'      => $type->name ?: $type->code,
            ]),
            action: trans('masterdata::app.approvals.notification.open-request'),
            context: ['change_request_id' => (int) $changeRequest->id, 'status' => $status->value],
            comment: $changeRequest->comment,
        );
    }
}
