<?php

namespace Webkul\MasterDataManagement\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\MasterDataManagement\DataGrids\FieldTransformDataGrid;
use Webkul\MasterDataManagement\Http\Controllers\Concerns\RespondsWithRedirect;
use Webkul\MasterDataManagement\Http\Requests\FieldTransformRequest;
use Webkul\MasterDataManagement\Repositories\EntityTypeRepository;
use Webkul\MasterDataManagement\Repositories\FieldTransformRepository;

class FieldTransformController extends Controller
{
    use RespondsWithRedirect;

    public function __construct(
        protected EntityTypeRepository $entityTypeRepository,
        protected FieldTransformRepository $fieldTransformRepository,
    ) {}

    /**
     * The grid of the entity type's field transforms.
     */
    public function index(int $entityTypeId): JsonResponse
    {
        abort_unless(bouncer()->hasPermission('masterdata.entity_types.edit'), 403);

        $this->entityTypeRepository->findOrFail($entityTypeId);

        return resolve(FieldTransformDataGrid::class)->toJson();
    }

    /**
     * Store a field transform of the entity type.
     */
    public function store(FieldTransformRequest $request, int $entityTypeId): JsonResponse
    {
        $this->entityTypeRepository->findOrFail($entityTypeId);

        $this->fieldTransformRepository->create($request->payload());

        return $this->respond($entityTypeId, trans('masterdata::app.field-transforms.create-success'));
    }

    /**
     * Update a field transform of the entity type.
     */
    public function update(FieldTransformRequest $request, int $entityTypeId, int $id): JsonResponse
    {
        $this->fieldTransformRepository->update($request->payload(), $request->fieldTransform()?->id ?? $id);

        return $this->respond($entityTypeId, trans('masterdata::app.field-transforms.update-success'));
    }

    /**
     * Delete a field transform of the entity type.
     */
    public function destroy(int $entityTypeId, int $id): JsonResponse
    {
        abort_unless(bouncer()->hasPermission('masterdata.entity_types.edit'), 403);

        $row = $this->fieldTransformRepository->findFor($entityTypeId, $id) ?? abort(404);

        $this->fieldTransformRepository->delete($row->id);

        return $this->respond($entityTypeId, trans('masterdata::app.field-transforms.delete-success'));
    }

    /**
     * Flash the message and answer with it and the validation tab to return to.
     */
    protected function respond(int $entityTypeId, string $message): JsonResponse
    {
        return $this->redirectResponse(route('admin.masterdata.entity_types.edit', ['id' => $entityTypeId, 'validation' => 1]), $message);
    }
}
