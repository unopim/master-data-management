<?php

namespace Webkul\MasterDataManagement\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Admin\Http\Requests\MassDestroyRequest;
use Webkul\MasterDataManagement\DataGrids\EntityTypeDataGrid;
use Webkul\MasterDataManagement\Enums\RecordAction;
use Webkul\MasterDataManagement\Http\Controllers\Concerns\RespondsWithRedirect;
use Webkul\MasterDataManagement\Http\Requests\EntityTypePermissionRequest;
use Webkul\MasterDataManagement\Http\Requests\EntityTypeRequest;
use Webkul\MasterDataManagement\Jobs\RunQualityScan;
use Webkul\MasterDataManagement\Models\EntityType as EntityTypeModel;
use Webkul\MasterDataManagement\Quality\QualityScanner;
use Webkul\MasterDataManagement\Repositories\EntityTypeRepository;
use Webkul\MasterDataManagement\Repositories\RecordRepository;
use Webkul\MasterDataManagement\Repositories\RoleEntityPermissionRepository;
use Webkul\MasterDataManagement\Security\EntityTypePermissions;
use Webkul\MasterDataManagement\Services\EntityTypeFormOptions;
use Webkul\MasterDataManagement\Services\MatchRuleFormOptions;
use Webkul\MasterDataManagement\Services\SchemaPublisher;
use Webkul\MasterDataManagement\Services\SurvivorshipRuleFormOptions;
use Webkul\MasterDataManagement\Services\TemplateInstaller;

class EntityTypeController extends Controller
{
    use RespondsWithRedirect;

    public function __construct(
        protected EntityTypeRepository $entityTypeRepository,
        protected RecordRepository $recordRepository,
        protected RoleEntityPermissionRepository $roleEntityPermissionRepository,
        protected EntityTypeFormOptions $formOptions,
        protected QualityScanner $qualityScanner,
        protected MatchRuleFormOptions $matchRuleFormOptions,
        protected SurvivorshipRuleFormOptions $survivorshipRuleFormOptions,
        protected TemplateInstaller $templateInstaller,
    ) {}

    /**
     * Display the entity type listing.
     */
    public function index(Request $request): View|JsonResponse
    {
        abort_unless(bouncer()->hasPermission('masterdata.entity_types'), 403);

        if ($request->ajax()) {
            return resolve(EntityTypeDataGrid::class)->toJson();
        }

        return view('masterdata::entity-types.index', $this->formOptions->quickCreateData(new EntityTypeModel) + [
            'templateOptions' => bouncer()->hasPermission('masterdata.entity_types.templates')
                ? $this->templateInstaller->options(app()->getLocale())
                : [],
        ]);
    }

    /**
     * Show the entity type creation form.
     */
    public function create(): View
    {
        abort_unless(bouncer()->hasPermission('masterdata.entity_types.create'), 403);

        return view('masterdata::entity-types.create', $this->formOptions->formData(new EntityTypeModel));
    }

    /**
     * Store a newly created entity type.
     */
    public function store(EntityTypeRequest $request): JsonResponse
    {
        $entityType = $this->entityTypeRepository->create($request->payload());

        return $this->redirectResponse(route('admin.masterdata.entity_types.edit', $entityType->id), trans('masterdata::app.entity-types.create-success'));
    }

    /**
     * Show the entity type edit form.
     */
    public function edit(int $id): View
    {
        abort_unless(bouncer()->hasPermission('masterdata.entity_types.edit'), 403);

        $entityType = $this->entityTypeRepository->with('translations')->findOrFail($id);

        $data = $this->formOptions->formData($entityType);

        if (request()->has('permissions')) {
            $data += [
                'roles'         => $this->formOptions->customRoles(),
                'grants'        => $this->roleEntityPermissionRepository->grantsForEntityType($id),
                'recordActions' => RecordAction::cases(),
            ];
        }

        if (request()->has('fields')) {
            $data += $this->formOptions->fieldFormData($entityType);
        }

        if (request()->has('relations')) {
            $data += $this->formOptions->relationTypeFormData($entityType);
        }

        if (request()->has('matching')) {
            $data += $this->matchRuleFormOptions->formData($entityType);
        }

        if (request()->has('survivorship')) {
            $data += $this->survivorshipRuleFormOptions->formData($entityType);
        }

        return view('masterdata::entity-types.edit', $data);
    }

    /**
     * Publish the entity type's current fields as its next schema version.
     */
    public function publish(int $id, SchemaPublisher $schemaPublisher): JsonResponse
    {
        abort_unless(bouncer()->hasPermission('masterdata.entity_types.edit'), 403);

        $version = $schemaPublisher->publish($this->entityTypeRepository->findOrFail($id), auth()->guard('admin')->user());

        $message = trans('masterdata::app.entity-types.publish.success', ['version' => $version->version]);

        return $this->redirectResponse(route('admin.masterdata.entity_types.edit', $id), $message);
    }

    /**
     * Update the entity type.
     *
     * Submitted settings are merged over the stored ones, so keys another screen owns survive; a change to the data
     * quality or hierarchy settings queues a quality scan of the type.
     */
    public function update(EntityTypeRequest $request, int $id): JsonResponse
    {
        $entityType = $this->entityTypeRepository->findOrFail($id);

        $payload = $request->payload();

        if (is_array($payload['settings'] ?? null)) {
            $payload['settings'] = array_replace($entityType->settings ?? [], $payload['settings']);
        }

        $before = $this->qualityScanner->settingsSnapshot($entityType);

        $updated = $this->entityTypeRepository->update($payload, $id);

        if ($this->qualityScanner->settingsSnapshot($updated) !== $before) {
            dispatch(new RunQualityScan($id));
        }

        return $this->redirectResponse(route('admin.masterdata.entity_types.edit', $id), trans('masterdata::app.entity-types.update-success'));
    }

    /**
     * Replace the record grants of every custom role on the entity type.
     *
     * A custom role absent from the submission loses its grant, because unticked checkboxes are not submitted.
     */
    public function updatePermissions(EntityTypePermissionRequest $request, int $id, EntityTypePermissions $entityTypePermissions): JsonResponse
    {
        $this->entityTypeRepository->findOrFail($id);

        $actionsByRoleId = [];

        foreach ($this->formOptions->customRoles() as $role) {
            $actionsByRoleId[$role->id] = $request->actionsFor($role->id);
        }

        $this->roleEntityPermissionRepository->syncForEntityType($id, $actionsByRoleId);

        $entityTypePermissions->flush();

        foreach (array_keys($actionsByRoleId) as $roleId) {
            Cache::forget(DashboardController::countsCacheKey($roleId));
        }

        return $this->redirectResponse(route('admin.masterdata.entity_types.edit', ['id' => $id, 'permissions' => 1]), trans('masterdata::app.entity-types.permissions.update-success'));
    }

    /**
     * Delete the entity type.
     */
    public function destroy(int $id): JsonResponse
    {
        abort_unless(bouncer()->hasPermission('masterdata.entity_types.delete'), 403);

        $this->entityTypeRepository->findOrFail($id);

        if ($this->recordRepository->existsForEntityType($id)) {
            return new JsonResponse([
                'message' => trans('masterdata::app.entity-types.delete-has-records'),
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        $this->entityTypeRepository->delete($id);

        return new JsonResponse([
            'message' => trans('masterdata::app.entity-types.delete-success'),
        ]);
    }

    /**
     * Delete the selected entity types, skipping and naming every type that still has records.
     */
    public function massDestroy(MassDestroyRequest $request): JsonResponse
    {
        abort_unless(bouncer()->hasPermission('masterdata.entity_types.delete'), 403);

        $types = $this->entityTypeRepository->findWhereIn('id', array_map(intval(...), $request->input('indices')));
        $recordCounts = $this->recordRepository->countsByEntityType($types->modelKeys());

        [$skipped, $deletable] = $types->partition(fn (EntityTypeModel $type): bool => ($recordCounts[$type->id] ?? 0) > 0);

        foreach ($deletable as $type) {
            $this->entityTypeRepository->delete($type->id);
        }

        $messages = [];

        if ($deletable->isNotEmpty()) {
            $messages[] = trans('masterdata::app.entity-types.mass-delete-success', ['count' => $deletable->count()]);
        }

        if ($skipped->isNotEmpty()) {
            $messages[] = trans('masterdata::app.entity-types.mass-delete-skipped', ['codes' => $skipped->pluck('code')->sort()->implode(', ')]);
        }

        return new JsonResponse(
            ['message' => implode(' ', $messages), 'skipped' => $skipped->pluck('code')->values()->all()],
            $deletable->isEmpty() && $skipped->isNotEmpty() ? JsonResponse::HTTP_BAD_REQUEST : JsonResponse::HTTP_OK,
        );
    }
}
