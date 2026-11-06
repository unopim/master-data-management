<?php

namespace Webkul\MasterDataManagement\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\MasterDataManagement\DataGrids\HierarchyDataGrid;
use Webkul\MasterDataManagement\Http\Controllers\Concerns\RespondsWithRedirect;
use Webkul\MasterDataManagement\Http\Requests\HierarchyRequest;
use Webkul\MasterDataManagement\Repositories\EntityTypeRepository;
use Webkul\MasterDataManagement\Repositories\HierarchyRepository;

class HierarchyController extends Controller
{
    use RespondsWithRedirect;

    public function __construct(
        protected EntityTypeRepository $entityTypeRepository,
        protected HierarchyRepository $hierarchyRepository,
    ) {}

    /**
     * The grid of the entity type's hierarchies.
     */
    public function index(int $entityTypeId): JsonResponse
    {
        abort_unless(bouncer()->hasPermission('masterdata.entity_types.edit'), 403);

        $this->entityTypeRepository->findOrFail($entityTypeId);

        return resolve(HierarchyDataGrid::class)->toJson();
    }

    /**
     * Store an alternate hierarchy of the entity type.
     */
    public function store(HierarchyRequest $request, int $entityTypeId): JsonResponse
    {
        $this->entityTypeRepository->findOrFail($entityTypeId);

        $this->hierarchyRepository->create($request->payload() + ['entity_type_id' => $entityTypeId]);

        return $this->respond($entityTypeId, trans('masterdata::app.hierarchies.create-success'));
    }

    /**
     * Update the labels of a hierarchy of the entity type.
     */
    public function update(HierarchyRequest $request, int $entityTypeId, int $id): JsonResponse
    {
        $this->hierarchyRepository->update($request->payload(), $request->hierarchy()?->id ?? $id);

        return $this->respond($entityTypeId, trans('masterdata::app.hierarchies.update-success'));
    }

    /**
     * Delete an alternate hierarchy of the entity type with its placements; the primary hierarchy stays.
     */
    public function destroy(int $entityTypeId, int $id): JsonResponse
    {
        abort_unless(bouncer()->hasPermission('masterdata.entity_types.edit'), 403);

        $hierarchy = $this->hierarchyRepository->findFor($entityTypeId, $id) ?? abort(404);

        if ($hierarchy->is_primary) {
            return new JsonResponse(['message' => trans('masterdata::app.hierarchies.validation.primary-delete')], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        $this->hierarchyRepository->delete($hierarchy->id);

        return $this->respond($entityTypeId, trans('masterdata::app.hierarchies.delete-success'));
    }

    /**
     * Flash the message and answer with it and the hierarchies tab to return to.
     */
    protected function respond(int $entityTypeId, string $message): JsonResponse
    {
        return $this->redirectResponse(route('admin.masterdata.entity_types.edit', ['id' => $entityTypeId, 'hierarchies' => 1]), $message);
    }
}
