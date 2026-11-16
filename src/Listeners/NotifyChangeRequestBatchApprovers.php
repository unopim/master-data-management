<?php

namespace Webkul\MasterDataManagement\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Support\Facades\Cache;
use Webkul\MasterDataManagement\Events\ChangeRequestBatchSubmitted;
use Webkul\MasterDataManagement\Repositories\ChangeRequestRepository;
use Webkul\MasterDataManagement\Security\AccessGrantResolver;
use Webkul\MasterDataManagement\Security\ChangeApprovals;
use Webkul\MasterDataManagement\Services\ChangeRequestNotifier;

/**
 * Tells each approver once how many of the changes an import run or mass action held wait for them, linking to the
 * approvals list. Each change counts only for the admins who may decide it, by the same rule as a single request.
 */
class NotifyChangeRequestBatchApprovers implements ShouldQueue
{
    /**
     * The notification type the batch approval notifications are stored with.
     */
    public const string TYPE = 'masterdata.approval.batch';

    /**
     * The cache key prefix that marks a batch as announced, so a completed import later marked failed is not sent twice.
     */
    public const string SENT_KEY = 'masterdata:change-request-batch-sent:';

    public function __construct(
        protected ChangeRequestRepository $changeRequestRepository,
        protected ChangeApprovals $changeApprovals,
        protected AccessGrantResolver $accessGrantResolver,
        protected ChangeRequestNotifier $notifier,
        protected Translator $translator,
    ) {}

    /**
     * Notify the approvers of the batch once, one notice per approver, grouped by how many changes wait for them.
     */
    public function handle(ChangeRequestBatchSubmitted $event): void
    {
        if (! Cache::add(self::SENT_KEY.$event->batch, true, now()->addDays(7))) {
            return;
        }

        $counts = $this->approverCounts($event->batch);

        $key = 'masterdata::app.approvals.notification.batch.'.$event->source;

        if (! $this->translator->has($key)) {
            $key = 'masterdata::app.approvals.notification.batch.default';
        }

        foreach ($this->groupByCount($counts) as $count => $adminIds) {
            $this->notifier->send(
                type: self::TYPE,
                adminIds: $adminIds,
                route: 'admin.masterdata.approvals.index',
                routeParams: [],
                title: trans('masterdata::app.approvals.notification.batch.title', ['count' => $count]),
                description: trans($key, ['name' => (string) $event->name, 'count' => $count]),
                action: trans('masterdata::app.approvals.notification.open-approvals'),
                context: [ChangeRequestBatchSubmitted::CONTEXT_KEY => $event->batch, 'count' => $count],
            );
        }
    }

    /**
     * How many pending changes of the batch each admin may decide. The approvers of a change on an unscoped type
     * depend only on the type and the requester, so they are looked up once per pair.
     *
     * @return array<int, int>
     */
    protected function approverCounts(string $batch): array
    {
        $counts = [];

        $approversByPair = [];

        foreach ($this->changeRequestRepository->pendingInBatch($batch) as $changeRequest) {
            $entityTypeId = (int) $changeRequest->entity_type_id;

            if ($this->accessGrantResolver->isScoped($entityTypeId)) {
                $adminIds = $this->changeApprovals->approverIds($changeRequest);
            } else {
                $adminIds = $approversByPair[$entityTypeId.'|'.$changeRequest->requested_by] ??= $this->changeApprovals->approverIds($changeRequest);
            }

            foreach ($adminIds as $adminId) {
                $counts[$adminId] = ($counts[$adminId] ?? 0) + 1;
            }
        }

        return $counts;
    }

    /**
     * @param  array<int, int>  $counts
     * @return array<int, list<int>>
     */
    protected function groupByCount(array $counts): array
    {
        $groups = [];

        foreach ($counts as $adminId => $count) {
            $groups[$count][] = $adminId;
        }

        return $groups;
    }
}
