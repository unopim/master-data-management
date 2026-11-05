<?php

namespace Webkul\MasterDataManagement\Http\Controllers\Admin;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\MasterDataManagement\DataGrids\RecordDataGrid;
use Webkul\MasterDataManagement\Enums\EntityTypeStatus;
use Webkul\MasterDataManagement\Enums\RecordStatus;
use Webkul\MasterDataManagement\Exceptions\StaleRecordException;
use Webkul\MasterDataManagement\Http\Controllers\Concerns\ResolvesAdmin;
use Webkul\MasterDataManagement\Http\Controllers\Concerns\RespondsWithRedirect;
use Webkul\MasterDataManagement\Http\Requests\RecordOptionsRequest;
use Webkul\MasterDataManagement\Http\Requests\RecordParentRequest;
use Webkul\MasterDataManagement\Http\Requests\RecordRequest;
use Webkul\MasterDataManagement\Http\Requests\RecordRetireRequest;
use Webkul\MasterDataManagement\Models\EntityType;
use Webkul\MasterDataManagement\Models\Field;
use Webkul\MasterDataManagement\Models\Record;
use Webkul\MasterDataManagement\Quality\CompletenessCalculator;
use Webkul\MasterDataManagement\Quality\IssuePresenter;
use Webkul\MasterDataManagement\Repositories\ChangeRequestRepository;
use Webkul\MasterDataManagement\Repositories\EntityTypeRepository;
use Webkul\MasterDataManagement\Repositories\FieldRepository;
use Webkul\MasterDataManagement\Repositories\QualityIssueRepository;
use Webkul\MasterDataManagement\Repositories\RecordMergeRepository;
use Webkul\MasterDataManagement\Repositories\RecordRepository;
use Webkul\MasterDataManagement\Repositories\XrefRepository;
use Webkul\MasterDataManagement\Security\EntityTypePermissions;
use Webkul\MasterDataManagement\Services\HierarchyService;
use Webkul\MasterDataManagement\Services\LineageService;
use Webkul\MasterDataManagement\Services\RecordFormBuilder;
use Webkul\MasterDataManagement\Services\RecordPresenter;
use Webkul\MasterDataManagement\Services\RecordWriter;
use Webkul\MasterDataManagement\ValueObjects\SourceContext;
use Webkul\User\Models\Admin;

class RecordController extends Controller
{
    use ResolvesAdmin, RespondsWithRedirect;

    public function __construct(
        protected RecordRepository $recordRepository,
        protected XrefRepository $xrefRepository,
        protected RecordWriter $recordWriter,
        protected HierarchyService $hierarchyService,
        protected LineageService $lineageService,
        protected RecordFormBuilder $recordFormBuilder,
        protected CompletenessCalculator $completenessCalculator,
        protected QualityIssueRepository $qualityIssueRepository,
        protected IssuePresenter $issuePresenter,
        protected ChangeRequestRepository $changeRequestRepository,
        protected RecordPresenter $recordPresenter,
        protected FieldRepository $fieldRepository,
        protected EntityTypeRepository $entityTypeRepository,
        protected EntityTypePermissions $entityTypePermissions,
        protected RecordMergeRepository $recordMergeRepository,
    ) {}

    /**
     * The most change requests the record page lists.
     */
    protected const int CHANGE_REQUEST_LIMIT = 20;

    /**
     * The most records one async picker response holds.
     */
    protected const int OPTIONS_LIMIT = 20;

    /**
     * Display the records of an entity type.
     */
    public function index(Request $request, EntityType $entityType): View|JsonResponse
    {
        Gate::authorize('masterdata.records.viewAny', $entityType);

        if ($request->ajax()) {
            return resolve(RecordDataGrid::class)->toJson();
        }

        return view('masterdata::records.index', [
            'entityType'  => $entityType,
            'switchTypes' => $this->visibleEntityTypes(),
            'canCreate'   => $entityType->status === EntityTypeStatus::Active && Gate::allows('masterdata.records.create', $entityType),
            'topLevelUrl' => $entityType->is_hierarchical && in_array((int) $entityType->id, $this->hierarchyService->allowedParentTypeIds($entityType), true)
                ? $this->recordPresenter->childrenUrl($entityType, RecordParentRequest::ROOT)
                : null,
        ]);
    }

    /**
     * Show the form that creates a record of the entity type.
     */
    public function create(EntityType $entityType): View
    {
        $this->authorizeCreate($entityType);

        return view('masterdata::records.create', [
            'entityType' => $entityType,
            'groups'     => $this->recordFormBuilder->groups($entityType),
            'codeMode'   => $this->recordFormBuilder->codeMode($entityType),
            'labelField' => $this->recordFormBuilder->labelField($entityType),
            'rootOption' => $this->recordPresenter->rootOption(),
        ]);
    }

    /**
     * Create a record of the entity type through the writer.
     *
     * @throws ValidationException
     */
    public function store(RecordRequest $request, EntityType $entityType): JsonResponse
    {
        $this->authorizeCreate($entityType);

        $result = $this->recordWriter->submitCreate(
            $entityType,
            $this->writerInput($request, $entityType),
            $this->admin(),
            new SourceContext('admin'),
        );

        if ($result->isPending()) {
            return $this->pendingResponse(bouncer()->hasPermission('masterdata.approvals') && $result->gate->reference !== null
                ? route('admin.masterdata.approvals.show', (int) $result->gate->reference)
                : route('admin.masterdata.records.index', $entityType->code));
        }

        $record = $result->record;

        $message = trans('masterdata::app.records.create-success');

        return $this->redirectResponse(route('admin.masterdata.records.edit', $record->uuid), $message);
    }

    /**
     * Display one record, as a form when the admin may edit it and it is still draft or active.
     *
     * A merged record opens its surviving record instead; an unknown or invisible uuid is a 404,
     * and a record of an entity type the role is not granted is a 403.
     */
    public function edit(Request $request, string $uuid): View
    {
        $record = $this->recordRepository->findByUuid($uuid) ?? abort(404);

        $this->authorize('view', $record);

        $record->load(['entityType.translations', 'entityType.dataOwner:id,name']);

        $xrefs = $this->xrefRepository->entriesForRecord($record->id);

        $hierarchy = $this->recordPresenter->hierarchy($record);

        $live = in_array($record->status, [RecordStatus::Draft, RecordStatus::Active], true);

        $editable = $live && Gate::allows('update', $record);

        $canMerge = Gate::allows('merge', $record);

        $locale = $this->recordFormBuilder->editLocale($request->string('locale')->toString() ?: null);

        $locales = $editable && $this->recordFormBuilder->hasLocalizedFields($record->entityType)
            ? $this->recordFormBuilder->editableLocales()
            : [];

        return view('masterdata::records.edit', [
            'record'           => $record,
            'editable'         => $editable,
            'canLinkXrefs'     => $editable,
            'retirable'        => $live && Gate::allows('delete', $record),
            'reactivatable'    => $record->status === RecordStatus::Retired && Gate::allows('delete', $record),
            'erasable'         => $record->status === RecordStatus::Retired && Gate::allows('erase', $record),
            'mergeable'        => $live && $canMerge,
            'canUnmerge'       => $canMerge,
            'merges'           => $this->recordMergeRepository->activeForSurvivor((int) $record->id),
            'groups'           => $editable ? $this->recordFormBuilder->groups($record->entityType, $record, $locale) : [],
            'locale'           => $locale,
            'locales'          => count($locales) > 1 ? $locales : [],
            'labelField'       => $this->recordFormBuilder->labelField($record->entityType),
            'otherValues'      => $this->recordFormBuilder->otherValues($record->entityType, $record),
            'valueRows'        => $this->recordPresenter->valueRows($record->values ?? []),
            'xrefs'            => $xrefs,
            'hierarchy'        => $hierarchy,
            'childTypes'       => $hierarchy === null ? [] : $this->recordPresenter->childTypeLinks($record, $this->visibleEntityTypes()),
            'relations'        => $this->recordPresenter->relations($record),
            'lineage'          => $this->lineageService->forRecord($record, $xrefs, $hierarchy['ancestors'] ?? null),
            'completeness'     => $this->completenessCalculator->evaluate($record->entityType, $record->values ?? []),
            'issues'           => $this->issuePresenter->forRecord($record, $this->qualityIssueRepository->involvingRecord((int) $record->id)),
            'canDecide'        => bouncer()->hasPermission('masterdata.quality.resolve') && Gate::allows('update', $record),
            'dataOwner'        => $record->entityType->dataOwner?->name,
            'pendingChange'    => $this->changeRequestRepository->firstPendingForRecord((int) $record->id),
            'pendingCount'     => $this->changeRequestRepository->pendingCountForRecord((int) $record->id),
            'changeRequests'   => $this->changeRequestRepository->latestForRecord((int) $record->id, self::CHANGE_REQUEST_LIMIT),
            'canViewApprovals' => bouncer()->hasPermission('masterdata.approvals'),
        ]);
    }

    /**
     * Update a record's values, label and status at the revision the editor read.
     *
     * @throws ValidationException
     */
    public function update(RecordRequest $request, string $uuid): JsonResponse
    {
        $record = $this->recordRepository->findByUuid($uuid) ?? abort(404);

        $this->authorize('update', $record);

        try {
            $result = $this->recordWriter->submitUpdate(
                $record,
                $this->writerInput($request, $record->entityType),
                (int) $request->validated('revision'),
                $this->admin(),
                new SourceContext('admin'),
            );
        } catch (StaleRecordException $exception) {
            return new JsonResponse(['message' => $exception->getMessage()], JsonResponse::HTTP_CONFLICT);
        }

        $editUrl = $this->editUrl($record, $request->query('locale'));

        if ($result->isPending()) {
            return $this->pendingResponse($editUrl);
        }

        return $this->redirectResponse($editUrl, trans('masterdata::app.records.update-success'));
    }

    /**
     * Retire a record at the revision the editor read; a retired record stays readable but can no longer change.
     *
     * @throws ValidationException
     */
    public function retire(RecordRetireRequest $request, string $uuid): JsonResponse
    {
        $record = $this->recordRepository->findByUuid($uuid) ?? abort(404);

        $this->authorize('delete', $record);

        try {
            $result = $this->recordWriter->submitRetire($record, (int) $request->validated('revision'), $this->admin(), new SourceContext('admin'));
        } catch (StaleRecordException $exception) {
            return new JsonResponse(['message' => $exception->getMessage()], JsonResponse::HTTP_CONFLICT);
        }

        if ($result->isPending()) {
            return $this->pendingResponse(route('admin.masterdata.records.edit', $record->uuid));
        }

        return $this->redirectResponse(route('admin.masterdata.records.edit', $record->uuid), trans('masterdata::app.records.retire-success'));
    }

    /**
     * Bring a retired record back to active at the revision the editor read; refused below a retired parent.
     *
     * @throws ValidationException
     */
    public function reactivate(RecordRetireRequest $request, string $uuid): JsonResponse
    {
        $record = $this->recordRepository->findByUuid($uuid) ?? abort(404);

        $this->authorize('delete', $record);

        try {
            $result = $this->recordWriter->submitReactivate($record, (int) $request->validated('revision'), $this->admin(), new SourceContext('admin'));
        } catch (StaleRecordException $exception) {
            return new JsonResponse(['message' => $exception->getMessage()], JsonResponse::HTTP_CONFLICT);
        }

        if ($result->isPending()) {
            return $this->pendingResponse(route('admin.masterdata.records.edit', $record->uuid));
        }

        return $this->redirectResponse(route('admin.masterdata.records.edit', $record->uuid), trans('masterdata::app.records.reactivate.success'));
    }

    /**
     * Search the records that may parent a record of the entity type, for the async parent picker, or, with `field`,
     * the records a reference field of the type may point to.
     *
     * Rows are limited to the types the entity type allows as parents (narrowed by `types[]`), to draft and active
     * records the admin may see, and, for an existing record, to records outside its own subtree.
     */
    public function options(RecordOptionsRequest $request, EntityType $entityType): JsonResponse
    {
        Gate::authorize('masterdata.records.viewAny', $entityType);

        if ($request->filled('field')) {
            return $this->referenceOptions($request, $entityType);
        }

        $record = null;

        if ($request->filled('record')) {
            $record = $this->recordRepository->findByUuid((string) $request->validated('record')) ?? abort(404);

            abort_unless((int) $record->entity_type_id === (int) $entityType->id, 404);
        }

        $typeIds = $entityType->is_hierarchical ? $this->hierarchyService->allowedParentTypeIds($entityType) : [];

        $requested = array_map(intval(...), (array) $request->validated('types', []));

        if ($requested !== []) {
            $typeIds = array_values(array_intersect($typeIds, $requested));
        }

        $uuids = array_values(array_map(strval(...), (array) $request->validated('identifiers.values', [])));

        $term = (string) $request->validated('query', '');

        $options = $this->recordRepository
            ->parentCandidates($record, $typeIds, self::OPTIONS_LIMIT, $this->admin(), $term, array_values(array_diff($uuids, [RecordParentRequest::ROOT])))
            ->map($this->recordPresenter->option(...))
            ->values()
            ->all();

        $showRoot = $entityType->is_hierarchical && ($uuids === [] ? $term === '' : in_array(RecordParentRequest::ROOT, $uuids, true));

        return new JsonResponse([
            'options'  => $showRoot ? [$this->recordPresenter->rootOption(), ...$options] : $options,
            'page'     => 1,
            'lastPage' => 1,
        ]);
    }

    /**
     * The active entity types the admin may view, in display order.
     *
     * @return Collection<int, EntityType>
     */
    protected function visibleEntityTypes(): Collection
    {
        $admin = $this->admin();

        return $this->entityTypeRepository->listActive($admin instanceof Admin ? $this->entityTypePermissions->visibleEntityTypeIds($admin) : []);
    }

    /**
     * Search the draft and active records of the type a reference field points to, for the async reference picker.
     */
    protected function referenceOptions(RecordOptionsRequest $request, EntityType $entityType): JsonResponse
    {
        $field = $this->fieldRepository->activeFor((int) $entityType->id)
            ->first(fn (Field $field): bool => $field->code === $request->validated('field') && $field->type === 'reference');

        abort_unless($field instanceof Field && $field->reference_entity_type_id !== null, 404);

        $options = $this->recordRepository
            ->parentCandidates(
                null,
                [(int) $field->reference_entity_type_id],
                self::OPTIONS_LIMIT,
                $this->admin(),
                (string) $request->validated('query', ''),
                array_values(array_map(strval(...), (array) $request->validated('identifiers.values', []))),
            )
            ->map($this->recordPresenter->option(...))
            ->values()
            ->all();

        return new JsonResponse(['options' => $options, 'page' => 1, 'lastPage' => 1]);
    }

    /**
     * Move a record under another parent, or to the root, at the revision the editor read.
     *
     * Every refusal is reported on `parent`, the only field the change parent form shows.
     *
     * @throws ValidationException
     */
    public function updateParent(RecordParentRequest $request, string $uuid): JsonResponse
    {
        $record = $this->recordRepository->findByUuid($uuid) ?? abort(404);

        $this->authorize('update', $record);

        try {
            $result = $this->recordWriter->submitUpdate(
                $record,
                ['parent' => $request->validated('parent')],
                (int) $request->validated('revision'),
                $this->admin(),
                new SourceContext('admin'),
            );
        } catch (StaleRecordException $exception) {
            return new JsonResponse(['message' => $exception->getMessage()], JsonResponse::HTTP_CONFLICT);
        } catch (ValidationException $exception) {
            throw ValidationException::withMessages(['parent' => Arr::flatten($exception->errors())]);
        }

        if ($result->isPending()) {
            return $this->pendingResponse(route('admin.masterdata.records.edit', $record->uuid));
        }

        return new JsonResponse([
            'redirect_url' => route('admin.masterdata.records.edit', $record->uuid),
            'message'      => trans('masterdata::app.hierarchy.move-success'),
        ]);
    }

    /**
     * The record page, kept in the edited locale when that is an active locale other than the default one.
     */
    protected function editUrl(Record $record, mixed $locale): string
    {
        $locale = $this->recordFormBuilder->editLocale(is_string($locale) ? $locale : null);

        return route('admin.masterdata.records.edit', array_filter([
            'uuid'   => $record->uuid,
            'locale' => $locale === $this->recordFormBuilder->editLocale(null) ? null : $locale,
        ]));
    }

    /**
     * Collect the writer input from the form: values (normalised to the writer's shape), label, status, code, parent.
     *
     * `parent` is passed only when the form sent it, since an absent parent on update means "keep the current one".
     *
     * @return array<string, mixed>
     */
    protected function writerInput(RecordRequest $request, EntityType $type): array
    {
        $validated = $request->validated();

        $input = Arr::only($validated, ['code', 'label', 'status', 'parent']);

        if (isset($validated['values']) && is_array($validated['values'])) {
            $input['values'] = $this->recordFormBuilder->normalizeValues($type, $validated['values']);
        }

        if (array_key_exists('code', $input) && ($input['code'] === null || $input['code'] === '')) {
            unset($input['code']);
        }

        return $input;
    }

    /**
     * Refuse the create form and store for an entity type that is not active or that the admin may not create in.
     */
    protected function authorizeCreate(EntityType $entityType): void
    {
        abort_unless($entityType->status === EntityTypeStatus::Active, 404);

        Gate::authorize('masterdata.records.create', $entityType);
    }
}
