<?php

namespace Webkul\MasterDataManagement\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\MasterDataManagement\Enums\RecordStatus;
use Webkul\MasterDataManagement\Http\Controllers\Concerns\ResolvesAdmin;
use Webkul\MasterDataManagement\Http\Controllers\Concerns\RespondsWithRedirect;
use Webkul\MasterDataManagement\Http\Requests\RecordOptionsRequest;
use Webkul\MasterDataManagement\Http\Requests\RecordParentRequest;
use Webkul\MasterDataManagement\Http\Requests\RecordPlacementRequest;
use Webkul\MasterDataManagement\Http\Requests\RecordTreeRequest;
use Webkul\MasterDataManagement\Models\EntityType;
use Webkul\MasterDataManagement\Models\Hierarchy;
use Webkul\MasterDataManagement\Models\Record;
use Webkul\MasterDataManagement\Repositories\HierarchyNodeRepository;
use Webkul\MasterDataManagement\Repositories\HierarchyRepository;
use Webkul\MasterDataManagement\Repositories\RecordRepository;
use Webkul\MasterDataManagement\Services\HierarchyPresenter;
use Webkul\MasterDataManagement\Services\HierarchyService;
use Webkul\MasterDataManagement\Services\RecordPresenter;

/**
 * The tree page of an entity type's hierarchies and a record's placement in its alternate hierarchies.
 */
class RecordHierarchyController extends Controller
{
    use ResolvesAdmin, RespondsWithRedirect;

    /**
     * The most records one async parent picker response holds.
     */
    protected const int OPTIONS_LIMIT = 20;

    public function __construct(
        protected RecordRepository $recordRepository,
        protected HierarchyRepository $hierarchyRepository,
        protected HierarchyNodeRepository $hierarchyNodeRepository,
        protected HierarchyService $hierarchyService,
        protected HierarchyPresenter $hierarchyPresenter,
        protected RecordPresenter $recordPresenter,
    ) {}

    /**
     * Show the entity type's records as a tree of the chosen hierarchy, the primary one by default.
     */
    public function tree(RecordTreeRequest $request, EntityType $entityType): View
    {
        Gate::authorize('masterdata.records.viewAny', $entityType);

        if ($entityType->is_hierarchical) {
            $this->hierarchyService->ensurePrimary($entityType);
        }

        $hierarchies = $this->hierarchyRepository->forEntityType((int) $entityType->id);

        $current = $hierarchies->firstWhere('id', $request->hierarchyId()) ?? $hierarchies->first();

        return view('masterdata::records.tree', [
            'entityType'  => $entityType,
            'hierarchies' => $this->hierarchyPresenter->options($hierarchies),
            'current'     => $current,
        ]);
    }

    /**
     * One level of the tree: the roots of the hierarchy, or the children of the given record.
     */
    public function nodes(RecordTreeRequest $request, EntityType $entityType): JsonResponse
    {
        Gate::authorize('masterdata.records.viewAny', $entityType);

        $hierarchy = $this->hierarchyRepository->findFor((int) $entityType->id, (int) $request->hierarchyId()) ?? abort(404);

        $parent = null;

        if ($request->parentUuid() !== null) {
            $parent = $this->recordRepository->findByUuid($request->parentUuid()) ?? abort(404);

            abort_if(Gate::denies('view', $parent), 404);
        }

        return new JsonResponse($this->hierarchyPresenter->level($hierarchy, $parent, $this->admin()));
    }

    /**
     * Search the records of the type that may parent the record in the alternate hierarchy: draft and active
     * records the admin may see, outside the record's own subtree there.
     */
    public function options(RecordOptionsRequest $request, string $uuid, int $hierarchyId): JsonResponse
    {
        [$record, $hierarchy] = $this->resolve($uuid, $hierarchyId);

        $node = $this->hierarchyNodeRepository->nodeFor((int) $hierarchy->id, (int) $record->id);

        $uuids = array_values(array_map(strval(...), (array) $request->validated('identifiers.values', [])));

        $term = (string) $request->validated('query', '');

        $options = $this->recordRepository
            ->parentCandidates(
                null,
                [(int) $record->entity_type_id],
                self::OPTIONS_LIMIT,
                $this->admin(),
                $term,
                array_values(array_diff($uuids, [RecordParentRequest::ROOT])),
                $this->hierarchyNodeRepository->outsideSubtree((int) $hierarchy->id, $node?->path ?? '/'.$record->id.'/'),
            )
            ->reject(fn (Record $candidate): bool => (int) $candidate->id === (int) $record->id)
            ->map($this->recordPresenter->option(...))
            ->values()
            ->all();

        $showRoot = $uuids === [] ? $term === '' : in_array(RecordParentRequest::ROOT, $uuids, true);

        return new JsonResponse([
            'options'  => $showRoot ? [$this->recordPresenter->rootOption(), ...$options] : $options,
            'page'     => 1,
            'lastPage' => 1,
        ]);
    }

    /**
     * Place the record under the chosen parent in the alternate hierarchy, or at its root.
     *
     * @throws ValidationException
     */
    public function update(RecordPlacementRequest $request, string $uuid, int $hierarchyId): JsonResponse
    {
        [$record, $hierarchy] = $this->resolve($uuid, $hierarchyId);

        $parent = null;

        if ($request->parentUuid() !== null) {
            $parent = $this->recordRepository->findByUuid($request->parentUuid());

            if (! $parent instanceof Record || Gate::denies('view', $parent)) {
                throw ValidationException::withMessages(['parent' => trans('masterdata::app.hierarchy.validation.parent-not-found')]);
            }
        }

        $this->hierarchyService->place($hierarchy, $record, $parent);

        return $this->redirectResponse(route('admin.masterdata.records.edit', $record->uuid), trans('masterdata::app.hierarchies.record.placed', ['hierarchy' => $hierarchy->displayLabel()]));
    }

    /**
     * Take the record out of the alternate hierarchy; its children move up a level.
     *
     * @throws ValidationException
     */
    public function destroy(string $uuid, int $hierarchyId): JsonResponse
    {
        abort_unless(bouncer()->hasPermission('masterdata.records.edit'), 403);

        [$record, $hierarchy] = $this->resolve($uuid, $hierarchyId);

        $this->hierarchyService->remove($hierarchy, $record);

        return $this->redirectResponse(route('admin.masterdata.records.edit', $record->uuid), trans('masterdata::app.hierarchies.record.removed', ['hierarchy' => $hierarchy->displayLabel()]));
    }

    /**
     * The live record the admin may update and the alternate hierarchy of its type, else 404 or 403.
     *
     * @return array{0: Record, 1: Hierarchy}
     */
    protected function resolve(string $uuid, int $hierarchyId): array
    {
        $record = $this->recordRepository->findByUuid($uuid) ?? abort(404);

        $this->authorize('update', $record);

        abort_unless(in_array($record->status, [RecordStatus::Draft, RecordStatus::Active], true), 404);

        $hierarchy = $this->hierarchyRepository->findFor((int) $record->entity_type_id, $hierarchyId) ?? abort(404);

        abort_if($hierarchy->is_primary, 404);

        return [$record, $hierarchy];
    }
}
