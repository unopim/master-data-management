<?php

namespace Webkul\MasterDataManagement\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\MasterDataManagement\DataGrids\ChangeRequestDataGrid;
use Webkul\MasterDataManagement\Enums\GrantAction;
use Webkul\MasterDataManagement\Exceptions\ChangeRequestConflictException;
use Webkul\MasterDataManagement\Http\Controllers\Concerns\ResolvesAdmin;
use Webkul\MasterDataManagement\Http\Controllers\Concerns\RespondsWithRedirect;
use Webkul\MasterDataManagement\Http\Requests\ChangeRequestDecisionRequest;
use Webkul\MasterDataManagement\Models\ChangeRequest;
use Webkul\MasterDataManagement\Repositories\ChangeRequestRepository;
use Webkul\MasterDataManagement\Repositories\RecordRepository;
use Webkul\MasterDataManagement\Security\AccessGrantResolver;
use Webkul\MasterDataManagement\Security\ChangeApprovals;
use Webkul\MasterDataManagement\Services\ChangeRequestDiff;
use Webkul\MasterDataManagement\Services\ChangeRequestService;
use Webkul\User\Models\Admin;

/**
 * The approvals pages: record changes held for approval, their field-level diff and the approve / reject decisions.
 *
 * Every request is gated by the role's view grant on its entity type; deciding needs the approve permission together
 * with the role's approve grant or the type's data ownership, and never one's own request.
 */
class ApprovalController extends Controller
{
    use ResolvesAdmin, RespondsWithRedirect;

    public function __construct(
        protected ChangeRequestRepository $changeRequestRepository,
        protected RecordRepository $recordRepository,
        protected ChangeRequestService $changeRequestService,
        protected ChangeRequestDiff $changeRequestDiff,
        protected ChangeApprovals $changeApprovals,
        protected AccessGrantResolver $accessGrantResolver,
    ) {}

    /**
     * List the change requests of the entity types the admin may view.
     */
    public function index(Request $request): View|JsonResponse
    {
        if ($request->ajax()) {
            return resolve(ChangeRequestDataGrid::class)->toJson();
        }

        return view('masterdata::approvals.index');
    }

    /**
     * Review one change request: what it changes, whether the record moved on since, and the decision.
     */
    public function show(int $id): View
    {
        $changeRequest = $this->changeRequest($id);

        $admin = $this->admin();

        $record = $changeRequest->record_id === null ? null : $this->recordRepository->find($changeRequest->record_id);

        return view('masterdata::approvals.show', [
            'changeRequest' => $changeRequest,
            'typeName'      => $changeRequest->entityType->name ?: $changeRequest->entityType->code,
            'record'        => $record,
            'rows'          => $this->changeRequestDiff->rows($changeRequest, $admin),
            'conflict'      => $changeRequest->isPending() && $this->changeRequestService->hasConflict($changeRequest, $record),
            'canDecide'     => $admin instanceof Admin && $this->changeApprovals->canDecide($admin, $changeRequest),
        ]);
    }

    /**
     * Apply the held change; a record changed since the request is refused and the request turns stale.
     *
     * The refusal answers with the request page to reload, since the admin form only follows a redirect on success.
     *
     * @throws ValidationException
     */
    public function approve(ChangeRequestDecisionRequest $request, int $id): JsonResponse
    {
        $changeRequest = $this->decidable($id);

        try {
            $changeRequest = $this->changeRequestService->approve($changeRequest, $this->requireAdmin(), $request->validated('comment'));
        } catch (ChangeRequestConflictException $exception) {
            return $this->redirectResponse(route('admin.masterdata.approvals.show', $exception->changeRequest->id), $exception->getMessage(), 'error');
        }

        return $this->decided($changeRequest, trans('masterdata::app.approvals.approve-success'));
    }

    /**
     * Close the held change without applying it.
     *
     * @throws ValidationException
     */
    public function reject(ChangeRequestDecisionRequest $request, int $id): JsonResponse
    {
        $changeRequest = $this->changeRequestService->reject($this->decidable($id), $this->requireAdmin(), $request->validated('comment'));

        return $this->decided($changeRequest, trans('masterdata::app.approvals.reject-success'));
    }

    /**
     * Find a change request of an entity type the admin may view, else 404 / 403.
     *
     * On a scoped type a request whose record, or proposed parent for a create, lies outside the admin's view
     * grants is a 404 like an unknown one.
     */
    protected function changeRequest(int $id): ChangeRequest
    {
        $changeRequest = $this->changeRequestRepository
            ->with(['entityType.translations', 'entityType.dataOwner:id,name', 'requester:id,name', 'decider:id,name'])
            ->find($id) ?? abort(404);

        Gate::authorize('masterdata.records.viewAny', $changeRequest->entityType);

        $visible = $changeRequest->record_id !== null
            ? $this->recordRepository->find($changeRequest->record_id) !== null
            : $this->accessGrantResolver->allowsUnder($this->requireAdmin(), GrantAction::View, (int) $changeRequest->entity_type_id, $changeRequest->parent);

        abort_unless($visible, 404);

        return $changeRequest;
    }

    /**
     * Find a change request the admin may decide, else 404 / 403.
     */
    protected function decidable(int $id): ChangeRequest
    {
        $changeRequest = $this->changeRequest($id);

        abort_unless($this->changeApprovals->canDecide($this->requireAdmin(), $changeRequest), 403);

        return $changeRequest;
    }

    /**
     * The signed-in admin every decision is recorded against.
     */
    protected function requireAdmin(): Admin
    {
        return $this->admin() ?? abort(403);
    }

    /**
     * Respond to a decision by returning to the reviewed request.
     */
    protected function decided(ChangeRequest $changeRequest, string $message): JsonResponse
    {
        return $this->redirectResponse(route('admin.masterdata.approvals.show', $changeRequest->id), $message);
    }
}
