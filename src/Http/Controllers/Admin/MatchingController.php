<?php

namespace Webkul\MasterDataManagement\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\MasterDataManagement\DataGrids\MatchCandidateDataGrid;
use Webkul\MasterDataManagement\Enums\EntityTypeStatus;
use Webkul\MasterDataManagement\Http\Controllers\Concerns\ResolvesAdmin;
use Webkul\MasterDataManagement\Http\Controllers\Concerns\RespondsWithRedirect;
use Webkul\MasterDataManagement\Http\Requests\MatchAcceptRequest;
use Webkul\MasterDataManagement\Http\Requests\MatchCandidateRequest;
use Webkul\MasterDataManagement\Http\Requests\MatchCreateRecordRequest;
use Webkul\MasterDataManagement\Matching\MatchInbox;
use Webkul\MasterDataManagement\Matching\MatchSuggestion;
use Webkul\MasterDataManagement\Models\MatchCandidate;
use Webkul\MasterDataManagement\Models\Record;
use Webkul\MasterDataManagement\Repositories\EntityTypeRepository;
use Webkul\MasterDataManagement\Repositories\MatchCandidateRepository;
use Webkul\MasterDataManagement\Repositories\RecordRepository;
use Webkul\MasterDataManagement\Repositories\SourceSystemRepository;
use Webkul\MasterDataManagement\Security\EntityTypePermissions;
use Webkul\MasterDataManagement\Security\RecordPolicy;
use Webkul\MasterDataManagement\Services\RecordFormBuilder;
use Webkul\User\Models\Admin;

/**
 * The match inbox pages: incoming source rows, their suggested records and the accept / create / reject decisions.
 *
 * Every row is gated by the role's grant on its entity type: viewing needs record view, accepting needs edit on the
 * chosen record, creating needs create on the type and rejecting needs edit on the type.
 */
class MatchingController extends Controller
{
    use ResolvesAdmin, RespondsWithRedirect;

    /**
     * The most suggestions the review page shows.
     */
    protected const int SUGGESTION_LIMIT = 5;

    public function __construct(
        protected MatchInbox $matchInbox,
        protected MatchCandidateRepository $matchCandidateRepository,
        protected RecordRepository $recordRepository,
        protected SourceSystemRepository $sourceSystemRepository,
        protected EntityTypeRepository $entityTypeRepository,
        protected EntityTypePermissions $entityTypePermissions,
        protected RecordFormBuilder $recordFormBuilder,
    ) {}

    /**
     * List the inbox rows of the entity types the admin may view.
     */
    public function index(Request $request): View|JsonResponse
    {
        if ($request->ajax()) {
            return resolve(MatchCandidateDataGrid::class)->toJson();
        }

        $admin = $this->admin();

        return view('masterdata::matching.index', [
            'canSubmit'     => bouncer()->hasPermission('masterdata.matching.create'),
            'typeOptions'   => $this->entityTypeRepository->options($admin instanceof Admin ? $this->entityTypePermissions->visibleEntityTypeIds($admin) : []),
        ]);
    }

    /**
     * Put an incoming source row into the inbox from the admin form.
     *
     * @throws ValidationException
     */
    public function store(MatchCandidateRequest $request): JsonResponse
    {
        $type = $this->entityTypeRepository->findOrFail((int) $request->validated('entity_type_id'));

        Gate::authorize('masterdata.records.viewAny', $type);

        $candidate = $this->matchInbox->submit(
            $this->sourceSystemRepository->findOrFail((int) $request->validated('source_system_id')),
            $type,
            (string) $request->validated('external_id'),
            ['code' => $request->validated('code'), 'label' => $request->validated('label')],
            $this->admin(),
        );

        return $this->redirectResponse(route('admin.masterdata.matching.show', $candidate->id), trans('masterdata::app.matching.submit-success'));
    }

    /**
     * Review one inbox row: its payload, the live suggestions and the decisions the admin may take.
     */
    public function show(int $id): View
    {
        $candidate = $this->candidate($id);

        $admin = $this->admin();

        $type = $candidate->entityType;

        $pending = $candidate->isPending() && bouncer()->hasPermission('masterdata.matching.edit');

        $suggestions = $candidate->isPending()
            ? $this->matchInbox->suggestionsFor($candidate, self::SUGGESTION_LIMIT, $admin)
            : [];

        return view('masterdata::matching.show', [
            'candidate'   => $candidate,
            'suggestions' => array_map(fn (MatchSuggestion $suggestion): array => [
                'suggestion' => $suggestion,
                'canAccept'  => $pending && Gate::allows('update', $suggestion->record),
            ], $suggestions),
            'canCreate'   => $pending && $type->status === EntityTypeStatus::Active && Gate::allows('masterdata.records.create', $type),
            'canReject'   => $pending && Gate::allows('update', RecordPolicy::subjectForEntityType((int) $type->id)),
            'codeMode'    => $this->recordFormBuilder->codeMode($type),
            'matched'     => $candidate->matched_record_id ? $this->recordRepository->find($candidate->matched_record_id) : null,
        ]);
    }

    /**
     * Link the row's external id to the chosen suggested record.
     *
     * @throws ValidationException
     */
    public function accept(MatchAcceptRequest $request, int $id): JsonResponse
    {
        $candidate = $this->candidate($id);

        $record = $this->recordRepository->findByUuid((string) $request->validated('record'))
            ?? throw ValidationException::withMessages(['record' => trans('masterdata::app.matching.record-not-found')]);

        $this->authorize('update', $record);

        if (! $this->matchInbox->accept($candidate, $record, $this->requireAdmin()) instanceof MatchCandidate) {
            return $this->pendingResponse(route('admin.masterdata.matching.show', $candidate->id));
        }

        return $this->decided($candidate, trans('masterdata::app.matching.accept-success'));
    }

    /**
     * Create a new record from the row and link the external id to it.
     *
     * @throws ValidationException
     */
    public function createRecord(MatchCreateRecordRequest $request, int $id): JsonResponse
    {
        $candidate = $this->candidate($id);

        abort_unless($candidate->entityType->status === EntityTypeStatus::Active, 404);

        Gate::authorize('masterdata.records.create', $candidate->entityType);

        $record = $this->matchInbox->createRecord($candidate, $request->validated(), $this->requireAdmin());

        if (! $record instanceof Record) {
            return $this->pendingResponse(route('admin.masterdata.matching.show', $candidate->id));
        }

        return $this->redirectResponse(route('admin.masterdata.records.edit', $record->uuid), trans('masterdata::app.matching.create-success'));
    }

    /**
     * Close the row without linking it.
     *
     * @throws ValidationException
     */
    public function reject(int $id): JsonResponse
    {
        $candidate = $this->candidate($id);

        Gate::authorize('update', RecordPolicy::subjectForEntityType((int) $candidate->entity_type_id));

        $this->matchInbox->reject($candidate, $this->requireAdmin());

        return $this->decided($candidate, trans('masterdata::app.matching.reject-success'));
    }

    /**
     * Find an inbox row of an entity type the admin may view, else 404 / 403.
     */
    protected function candidate(int $id): MatchCandidate
    {
        $candidate = $this->matchCandidateRepository->with(['sourceSystem', 'entityType.translations', 'resolvedBy'])->find($id) ?? abort(404);

        Gate::authorize('masterdata.records.viewAny', $candidate->entityType);

        abort_if($candidate->matched_record_id !== null && $this->recordRepository->find($candidate->matched_record_id) === null, 404);

        return $candidate;
    }

    /**
     * The signed-in admin every decision is recorded against.
     */
    protected function requireAdmin(): Admin
    {
        return $this->admin() ?? abort(403);
    }

    /**
     * Respond to a decision by returning to the reviewed row.
     */
    protected function decided(MatchCandidate $candidate, string $message): JsonResponse
    {
        return $this->redirectResponse(route('admin.masterdata.matching.show', $candidate->id), $message);
    }
}
