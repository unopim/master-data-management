<?php

namespace Webkul\MasterDataManagement\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Webkul\MasterDataManagement\Events\ChangeRequestBatchSubmitted;
use Webkul\MasterDataManagement\Events\ChangeRequestSubmitted;
use Webkul\MasterDataManagement\Security\ChangeApprovals;
use Webkul\MasterDataManagement\Services\ChangeRequestNotifier;

/**
 * Tells the admins who may decide a held change that it waits for them, linking the notification to the request. A
 * change held by an import run or a mass action is announced with the rest of its batch instead.
 */
class NotifyChangeRequestApprovers implements ShouldQueue
{
    /**
     * The notification type the approval notifications are stored with.
     */
    public const string TYPE = 'masterdata.approval';

    public function __construct(
        protected ChangeApprovals $changeApprovals,
        protected ChangeRequestNotifier $notifier,
    ) {}

    /**
     * Notify the approvers of the submitted change request, unless it belongs to a batch or nobody may decide it.
     */
    public function handle(ChangeRequestSubmitted $event): void
    {
        $changeRequest = $event->changeRequest->loadMissing(['entityType.translations', 'requester:id,name']);

        if (isset($changeRequest->context[ChangeRequestBatchSubmitted::CONTEXT_KEY])) {
            return;
        }

        $userIds = $this->changeApprovals->approverIds($changeRequest);

        $type = $changeRequest->entityType;

        $replace = [
            'operation' => trans('masterdata::app.approvals.operations.'.$changeRequest->operation->value),
            'type'      => $type->name ?: $type->code,
            'requester' => $changeRequest->requester?->name,
        ];

        $this->notifier->send(
            type: self::TYPE,
            adminIds: $userIds,
            route: 'admin.masterdata.approvals.show',
            routeParams: ['id' => (int) $changeRequest->id],
            title: trans('masterdata::app.approvals.notification.title', ['id' => $changeRequest->id]),
            description: $replace['requester'] === null
                ? trans('masterdata::app.approvals.notification.description-automated', $replace)
                : trans('masterdata::app.approvals.notification.description', $replace),
            action: trans('masterdata::app.approvals.notification.open-request'),
            context: ['change_request_id' => (int) $changeRequest->id],
        );
    }
}
