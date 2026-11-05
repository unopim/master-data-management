<?php

namespace Webkul\MasterDataManagement\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\MasterDataManagement\DataGrids\RelationTypeDataGrid;
use Webkul\MasterDataManagement\Http\Controllers\Concerns\RespondsWithRedirect;
use Webkul\MasterDataManagement\Http\Requests\RelationTypeRequest;
use Webkul\MasterDataManagement\Repositories\EntityTypeRepository;
use Webkul\MasterDataManagement\Repositories\RelationRepository;
use Webkul\MasterDataManagement\Repositories\RelationTypeRepository;

class RelationTypeController extends Controller
{
    use RespondsWithRedirect;

    public function __construct(
        protected EntityTypeRepository $entityTypeRepository,
        protected RelationTypeRepository $relationTypeRepository,
        protected RelationRepository $relationRepository,
    ) {}

    /**
     * The grid of the relation types that start from the entity type, system ones included.
     */
    public function index(int $entityTypeId): JsonResponse
    {
        abort_unless(bouncer()->hasPermission('masterdata.entity_types.edit'), 403);

        $this->entityTypeRepository->findOrFail($entityTypeId);

        return resolve(RelationTypeDataGrid::class)->toJson();
    }

    /**
     * Store a relation type from the entity type.
     */
    public function store(RelationTypeRequest $request, int $entityTypeId): JsonResponse
    {
        $this->entityTypeRepository->findOrFail($entityTypeId);

        $this->relationTypeRepository->create($request->payload() + [
            'from_entity_type_id' => $entityTypeId,
            'is_system'           => false,
        ]);

        return $this->respond($entityTypeId, trans('masterdata::app.relation-types.create-success'));
    }

    /**
     * Update a relation type of the entity type; system types follow their reference field instead.
     */
    public function update(RelationTypeRequest $request, int $entityTypeId, int $id): JsonResponse
    {
        $this->relationTypeRepository->update($request->payload(), $request->relationType()?->id ?? $id);

        return $this->respond($entityTypeId, trans('masterdata::app.relation-types.update-success'));
    }

    /**
     * Delete a relation type of the entity type; refused for system types and while relations of the type exist.
     */
    public function destroy(int $entityTypeId, int $id): JsonResponse
    {
        abort_unless(bouncer()->hasPermission('masterdata.entity_types.edit'), 403);

        $type = $this->relationTypeRepository->findFrom($entityTypeId, $id) ?? abort(404);

        if ($type->is_system) {
            return $this->refuse(trans('masterdata::app.relation-types.validation.system-type'));
        }

        if ($this->relationRepository->existsForType((int) $type->id)) {
            return $this->refuse(trans('masterdata::app.relation-types.validation.in-use'));
        }

        $this->relationTypeRepository->delete($type->id);

        return $this->respond($entityTypeId, trans('masterdata::app.relation-types.delete-success'));
    }

    /**
     * Flash the message and answer with it and the relations tab to return to.
     */
    protected function respond(int $entityTypeId, string $message): JsonResponse
    {
        return $this->redirectResponse(route('admin.masterdata.entity_types.edit', ['id' => $entityTypeId, 'relations' => 1]), $message);
    }

    /**
     * Refuse the request with the message.
     */
    protected function refuse(string $message): JsonResponse
    {
        return new JsonResponse(['message' => $message], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
    }
}
