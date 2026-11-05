<?php

namespace Webkul\MasterDataManagement\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\MasterDataManagement\DataGrids\FieldDataGrid;
use Webkul\MasterDataManagement\Http\Controllers\Concerns\RespondsWithRedirect;
use Webkul\MasterDataManagement\Http\Requests\FieldRequest;
use Webkul\MasterDataManagement\Models\EntityType;
use Webkul\MasterDataManagement\Models\Field;
use Webkul\MasterDataManagement\Repositories\EntityTypeRepository;
use Webkul\MasterDataManagement\Repositories\FieldRepository;
use Webkul\MasterDataManagement\Services\FieldService;

class FieldController extends Controller
{
    use RespondsWithRedirect;

    public function __construct(
        protected EntityTypeRepository $entityTypeRepository,
        protected FieldRepository $fieldRepository,
        protected FieldService $fieldService,
    ) {}

    /**
     * The fields grid of the entity type.
     */
    public function index(int $entityTypeId): JsonResponse
    {
        abort_unless(bouncer()->hasPermission('masterdata.entity_types.edit'), 403);

        $this->entityType($entityTypeId);

        return resolve(FieldDataGrid::class)->toJson();
    }

    /**
     * Store a new field on the entity type.
     */
    public function store(FieldRequest $request, int $entityTypeId): JsonResponse
    {
        $this->fieldService->create($this->entityType($entityTypeId), $request->payload());

        return $this->respond($entityTypeId, trans('masterdata::app.fields.create-success'));
    }

    /**
     * Update a field of the entity type.
     */
    public function update(FieldRequest $request, int $entityTypeId, int $id): JsonResponse
    {
        $this->entityType($entityTypeId);

        $this->fieldService->update($this->field($entityTypeId, $id), $request->payload());

        return $this->respond($entityTypeId, trans('masterdata::app.fields.update-success'));
    }

    /**
     * Remove a field of the entity type: archived while the type has records, deleted otherwise.
     */
    public function destroy(int $entityTypeId, int $id): JsonResponse
    {
        abort_unless(bouncer()->hasPermission('masterdata.entity_types.edit'), 403);

        $this->entityType($entityTypeId);

        $archived = $this->fieldService->delete($this->field($entityTypeId, $id));

        return $this->respond(
            $entityTypeId,
            trans($archived ? 'masterdata::app.fields.archive-success' : 'masterdata::app.fields.delete-success'),
        );
    }

    /**
     * Flash the message and answer with it and the fields tab to return to.
     */
    protected function respond(int $entityTypeId, string $message): JsonResponse
    {
        return $this->redirectResponse(route('admin.masterdata.entity_types.edit', ['id' => $entityTypeId, 'fields' => 1]), $message);
    }

    protected function entityType(int $id): EntityType
    {
        return $this->entityTypeRepository->findOrFail($id);
    }

    protected function field(int $entityTypeId, int $id): Field
    {
        return $this->fieldRepository->findForEntityType($entityTypeId, $id) ?? abort(404);
    }
}
