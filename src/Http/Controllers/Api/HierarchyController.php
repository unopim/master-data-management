<?php

namespace Webkul\MasterDataManagement\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Webkul\AdminApi\Http\Controllers\API\ApiController;
use Webkul\MasterDataManagement\Enums\ApiErrorCode;
use Webkul\MasterDataManagement\Http\Controllers\Api\Concerns\AuthorizesRecordViews;
use Webkul\MasterDataManagement\Http\Requests\Api\PlaceRecordRequest;
use Webkul\MasterDataManagement\Http\Resources\HierarchyPlacementResource;
use Webkul\MasterDataManagement\Http\Resources\HierarchyResource;
use Webkul\MasterDataManagement\Http\Responses\ApiError;
use Webkul\MasterDataManagement\Models\EntityType;
use Webkul\MasterDataManagement\Models\Hierarchy;
use Webkul\MasterDataManagement\Models\HierarchyNode;
use Webkul\MasterDataManagement\Models\Record;
use Webkul\MasterDataManagement\Repositories\EntityTypeRepository;
use Webkul\MasterDataManagement\Repositories\HierarchyNodeRepository;
use Webkul\MasterDataManagement\Repositories\HierarchyRepository;
use Webkul\MasterDataManagement\Repositories\RecordRepository;
use Webkul\MasterDataManagement\Services\HierarchyService;

/**
 * The hierarchies of an entity type and a record's placement in them. Alternate placements change here; the primary
 * placement follows the record's `parent` on the record endpoints.
 */
class HierarchyController extends ApiController
{
    use AuthorizesRecordViews;

    public function __construct(
        protected EntityTypeRepository $entityTypeRepository,
        protected RecordRepository $recordRepository,
        protected HierarchyRepository $hierarchyRepository,
        protected HierarchyNodeRepository $hierarchyNodeRepository,
        protected HierarchyService $hierarchyService,
    ) {}

    /**
     * List the hierarchies of the entity type, the primary first.
     */
    public function index(string $type): JsonResponse|AnonymousResourceCollection
    {
        $entityType = $this->entityTypeRepository->findOneByField('code', $type);

        if (! $entityType instanceof EntityType) {
            return $this->entityTypeNotFound();
        }

        if ($this->deniesEntityType((int) $entityType->id)) {
            return $this->forbiddenEntityType();
        }

        return HierarchyResource::collection($this->hierarchyRepository->forEntityType((int) $entityType->id));
    }

    /**
     * List the record's placements in the hierarchies of its entity type.
     */
    public function placements(string $type, string $uuid): JsonResponse|AnonymousResourceCollection
    {
        $record = $this->resolveRecord($type, $uuid, 'view');

        if ($record instanceof JsonResponse) {
            return $record;
        }

        return $this->placementCollection($this->hierarchyNodeRepository->forRecords([(int) $record->id], false));
    }

    /**
     * Place the record under a parent in an alternate hierarchy, or at its root.
     */
    public function place(PlaceRecordRequest $request, string $type, string $uuid, string $hierarchyCode): JsonResponse|AnonymousResourceCollection
    {
        $record = $this->resolveRecord($type, $uuid, 'update');

        if ($record instanceof JsonResponse) {
            return $record;
        }

        $hierarchy = $this->hierarchyRepository->findByCode((int) $record->entity_type_id, $hierarchyCode);

        if (! $hierarchy instanceof Hierarchy) {
            return $this->hierarchyNotFound();
        }

        $parent = null;

        if ($request->parentUuid() !== null) {
            $parent = $this->recordRepository->findByUuid($request->parentUuid());

            if (! $parent instanceof Record || $this->deniesRecord($parent)) {
                return $this->validateErrorResponse(['parent' => [trans('masterdata::app.hierarchy.validation.parent-not-found')]]);
            }
        }

        try {
            $this->hierarchyService->place($hierarchy, $record, $parent);
        } catch (ValidationException $exception) {
            return $this->validateErrorResponse($exception->errors());
        }

        return $this->placementCollection($this->hierarchyNodeRepository->forRecords([(int) $record->id], false));
    }

    /**
     * Take the record out of an alternate hierarchy; its children move up a level.
     */
    public function remove(string $type, string $uuid, string $hierarchyCode): JsonResponse|AnonymousResourceCollection
    {
        $record = $this->resolveRecord($type, $uuid, 'update');

        if ($record instanceof JsonResponse) {
            return $record;
        }

        $hierarchy = $this->hierarchyRepository->findByCode((int) $record->entity_type_id, $hierarchyCode);

        if (! $hierarchy instanceof Hierarchy) {
            return $this->hierarchyNotFound();
        }

        try {
            $this->hierarchyService->remove($hierarchy, $record);
        } catch (ValidationException $exception) {
            return $this->validateErrorResponse($exception->errors());
        }

        return $this->placementCollection($this->hierarchyNodeRepository->forRecords([(int) $record->id], false));
    }

    /**
     * The record of the entity type the caller may act on, else the error response.
     */
    protected function resolveRecord(string $type, string $uuid, string $ability): Record|JsonResponse
    {
        $entityType = $this->entityTypeRepository->findOneByField('code', $type);

        if (! $entityType instanceof EntityType) {
            return $this->entityTypeNotFound();
        }

        if ($this->deniesEntityType((int) $entityType->id)) {
            return $this->forbiddenEntityType();
        }

        $record = $this->recordRepository->findByUuid($uuid);

        if (! $record instanceof Record || $record->entity_type_id !== $entityType->id || $record->uuid !== $uuid) {
            return ApiError::make(ApiErrorCode::RecordNotFound, JsonResponse::HTTP_NOT_FOUND, trans('masterdata::app.api.record-not-found'));
        }

        if (Gate::denies($ability, $record)) {
            return $this->forbiddenEntityType();
        }

        return $record;
    }

    /**
     * Wrap the placements in resources that name parents and ancestors by uuid, read in one query.
     *
     * @param  Collection<int, HierarchyNode>  $nodes
     */
    protected function placementCollection(Collection $nodes): AnonymousResourceCollection
    {
        $ids = $nodes->flatMap(fn (HierarchyNode $node): array => array_map(intval(...), array_filter(explode('/', (string) $node->path), is_numeric(...))))
            ->unique()
            ->values()
            ->all();

        $uuids = array_map(fn (array $summary): string => $summary['uuid'], $this->recordRepository->summaries($ids));

        return HierarchyPlacementResource::collection($nodes->map(fn (HierarchyNode $node): HierarchyPlacementResource => new HierarchyPlacementResource($node, $uuids)));
    }

    /**
     * Answer 404 for an unknown entity type.
     */
    protected function entityTypeNotFound(): JsonResponse
    {
        return ApiError::make(ApiErrorCode::EntityTypeNotFound, JsonResponse::HTTP_NOT_FOUND, trans('masterdata::app.api.entity-type-not-found'));
    }

    /**
     * Answer 404 for an unknown hierarchy of the entity type.
     */
    protected function hierarchyNotFound(): JsonResponse
    {
        return ApiError::make(ApiErrorCode::HierarchyNotFound, JsonResponse::HTTP_NOT_FOUND, trans('masterdata::app.hierarchies.api.not-found'));
    }
}
