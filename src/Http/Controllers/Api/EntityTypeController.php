<?php

namespace Webkul\MasterDataManagement\Http\Controllers\Api;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Webkul\AdminApi\Http\Controllers\API\ApiController;
use Webkul\MasterDataManagement\Enums\ApiErrorCode;
use Webkul\MasterDataManagement\Http\Controllers\Api\Concerns\AuthorizesRecordViews;
use Webkul\MasterDataManagement\Http\Resources\EntityTypeResource;
use Webkul\MasterDataManagement\Http\Responses\ApiError;
use Webkul\MasterDataManagement\Models\EntityType;
use Webkul\MasterDataManagement\Repositories\EntityTypeRepository;
use Webkul\MasterDataManagement\Security\EntityTypePermissions;
use Webkul\User\Models\Admin;

class EntityTypeController extends ApiController
{
    use AuthorizesRecordViews;

    public function __construct(
        protected EntityTypeRepository $entityTypeRepository,
        protected EntityTypePermissions $entityTypePermissions,
    ) {}

    /**
     * List the entity types whose records the caller may view, with their active fields.
     */
    public function index(): AnonymousResourceCollection
    {
        $admin = auth()->user();

        $ids = $admin instanceof Admin ? $this->entityTypePermissions->visibleEntityTypeIds($admin) : [];

        return $this->collection($this->entityTypeRepository->listWithSchema($ids));
    }

    /**
     * Show an entity type with its active fields.
     */
    public function get(string $typeCode): JsonResponse|EntityTypeResource
    {
        $type = $this->entityTypeRepository->listWithSchema(null, $typeCode)->first();

        if (! $type instanceof EntityType) {
            return ApiError::make(ApiErrorCode::EntityTypeNotFound, JsonResponse::HTTP_NOT_FOUND, trans('masterdata::app.api.entity-type-not-found'));
        }

        if ($this->deniesEntityType($type->id)) {
            return $this->forbiddenEntityType();
        }

        return new EntityTypeResource($type, $this->entityTypeRepository->codesByIds($this->parentTypeIds($type)));
    }

    /**
     * Wrap the types in resources that name their allowed parent types by code, read in one query.
     *
     * @param  Collection<int, EntityType>  $types
     */
    protected function collection(Collection $types): AnonymousResourceCollection
    {
        $codes = $this->entityTypeRepository->codesByIds($types->flatMap($this->parentTypeIds(...))->unique()->values()->all());

        return EntityTypeResource::collection($types->map(fn (EntityType $type): EntityTypeResource => new EntityTypeResource($type, $codes)));
    }

    /**
     * The ids of the types the entity type's records may sit below.
     *
     * @return list<int>
     */
    protected function parentTypeIds(EntityType $type): array
    {
        return array_values(array_map(intval(...), $type->allowed_parent_type_ids ?? []));
    }
}
