<?php

namespace Webkul\MasterDataManagement\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Webkul\MasterDataManagement\Events\ChangeRequestSubmitted;
use Webkul\MasterDataManagement\Security\ChangeApprovals;
use Webkul\Notification\Events\NotificationEvent;

/**
 * Tells the admins who may decide a held change that it waits for them, linking the notification to the request.
 */
class NotifyChangeRequestApprovers implements ShouldQueue
{
    /**
     * The notification type the approval notifications are stored with.
     */
    public const string TYPE = 'masterdata.approval';

    public function __construct(protected ChangeApprovals $changeApprovals) {}

    /**
     * Notify the approvers of the submitted change request, unless notifications are off or nobody may decide it.
     */
    public function handle(ChangeRequestSubmitted $event): void
    {
        if (! config('notifications.enabled', true)) {
            return;
        }

        $changeRequest = $event->changeRequest->loadMissing(['entityType.translations', 'requester:id,name']);

        $userIds = $this->changeApprovals->approverIds($changeRequest);

        if ($userIds === []) {
            return;
        }

        $type = $changeRequest->entityType;

        $replace = [
            'operation' => trans('masterdata::app.approvals.operations.'.$changeRequest->operation->value),
            'type'      => $type->name ?: $type->code,
            'requester' => $changeRequest->requester?->name,
        ];

        event(new NotificationEvent([
            'type'         => self::TYPE,
            'route'        => 'admin.masterdata.approvals.show',
            'route_params' => ['id' => (int) $changeRequest->id],
            'title'        => trans('masterdata::app.approvals.notification.title', ['id' => $changeRequest->id]),
            'description'  => $replace['requester'] === null
                ? trans('masterdata::app.approvals.notification.description-automated', $replace)
                : trans('masterdata::app.approvals.notification.description', $replace),
            'context'      => ['change_request_id' => (int) $changeRequest->id],
            'user_ids'     => $userIds,
        ]));
    }
}
