<?php

namespace Webkul\MasterDataManagement\Http\Controllers\Api;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Webkul\AdminApi\Http\Controllers\API\ApiController;
use Webkul\MasterDataManagement\Enums\ApiErrorCode;
use Webkul\MasterDataManagement\Exceptions\ChangeRequestPendingException;
use Webkul\MasterDataManagement\Exceptions\StaleRecordException;
use Webkul\MasterDataManagement\Http\Controllers\Api\Concerns\AuthorizesRecordViews;
use Webkul\MasterDataManagement\Http\Requests\Api\ListRecordsRequest;
use Webkul\MasterDataManagement\Http\Requests\Api\ShowRecordRequest;
use Webkul\MasterDataManagement\Http\Requests\Api\StoreRecordRequest;
use Webkul\MasterDataManagement\Http\Requests\Api\UpdateRecordRequest;
use Webkul\MasterDataManagement\Http\Resources\RecordResource;
use Webkul\MasterDataManagement\Http\Responses\ApiError;
use Webkul\MasterDataManagement\Models\EntityType;
use Webkul\MasterDataManagement\Models\Record;
use Webkul\MasterDataManagement\Repositories\EntityTypeRepository;
use Webkul\MasterDataManagement\Repositories\FieldRepository;
use Webkul\MasterDataManagement\Repositories\RecordRepository;
use Webkul\MasterDataManagement\Repositories\RelationRepository;
use Webkul\MasterDataManagement\Repositories\XrefRepository;
use Webkul\MasterDataManagement\Services\RecordWriter;
use Webkul\MasterDataManagement\Services\XrefService;
use Webkul\MasterDataManagement\ValueObjects\RecordWriteResult;
use Webkul\MasterDataManagement\ValueObjects\SourceContext;
use Webkul\User\Models\Admin;

/**
 * Writes go through RecordWriter with the API principal as author, so the change gate holds them for approval like
 * any other write; a held write answers 202 with `code: pending_approval` and the change request id.
 */
class RecordController extends ApiController
{
    use AuthorizesRecordViews;

    public function __construct(
        protected RecordRepository $recordRepository,
        protected EntityTypeRepository $entityTypeRepository,
        protected FieldRepository $fieldRepository,
        protected XrefService $xrefService,
        protected XrefRepository $xrefRepository,
        protected RelationRepository $relationRepository,
        protected RecordWriter $recordWriter,
    ) {}

    /**
     * List the records of an entity type by cursor, ordered by id, with optional filters and includes.
     *
     * A `filter[field]` key must name an active searchable field and matches its indexed value exactly.
     */
    public function index(ListRecordsRequest $request, string $type): JsonResponse|AnonymousResourceCollection
    {
        $entityType = $this->entityTypeRepository->findOneByField('code', $type);

        if (! $entityType instanceof EntityType) {
            return $this->entityTypeNotFound();
        }

        if (Gate::denies('masterdata.records.viewAny', $entityType)) {
            return $this->forbiddenEntityType();
        }

        $fieldFilters = $request->fieldFilters();

        $unsearchable = array_diff(array_keys($fieldFilters), $this->fieldRepository->searchableFor($entityType->id)->pluck('code')->all());

        if ($unsearchable !== []) {
            return ApiError::make(
                ApiErrorCode::FilterNotSearchable,
                JsonResponse::HTTP_UNPROCESSABLE_ENTITY,
                trans('masterdata::app.api.filter-not-searchable', ['field' => reset($unsearchable)]),
            );
        }

        $query = $this->recordRepository->scopedQuery($entityType->id);

        if (($code = $request->columnFilter('code')) !== null) {
            $query->where('code', $code);
        }

        if (($status = $request->columnFilter('status')) !== null) {
            $query->where('status', $status);
        }

        if (($since = $request->columnFilter('updated_since')) !== null) {
            $query->where('updated_at', '>=', Date::parse($since));
        }

        foreach ($fieldFilters as $fieldCode => $value) {
            $this->recordRepository->whereFieldValue($query, $entityType->id, (string) $fieldCode, '=', $value);
        }

        $page = $query->orderBy('id')->cursorPaginate($request->limit(), ['*'], 'cursor', $request->string('cursor')->toString() ?: null);

        $records = collect($page->items());

        $recordIds = $records->map(fn (Record $record): int => (int) $record->id)->all();

        $ids = $request->includes('xrefs') ? $this->xrefRepository->activeIdsByRecords($recordIds) : null;

        $relations = $request->includes('relations') ? $this->relationRepository->visibleReferenceUuids($recordIds) : null;

        return RecordResource::collection($records->map(fn (Record $record): RecordResource => new RecordResource(
            $record,
            $ids === null ? null : ($ids[$record->id] ?? []),
            null,
            $relations === null ? null : ($relations[$record->id] ?? []),
        )))->additional(['meta' => [
            'next_cursor' => $page->nextCursor()?->encode(),
            'limit'       => $request->limit(),
        ]]);
    }

    /**
     * Show a record with its external ids and references; a merged uuid redirects permanently to the survivor.
     *
     * The `ETag` is the record revision, the value an update or delete sends back in `If-Match`.
     */
    public function show(ShowRecordRequest $request, string $type, string $uuid): JsonResponse|RedirectResponse
    {
        $entityType = $this->entityTypeRepository->findOneByField('code', $type);

        if (! $entityType instanceof EntityType) {
            return $this->entityTypeNotFound();
        }

        if ($this->deniesEntityType($entityType->id)) {
            return $this->forbiddenEntityType();
        }

        $record = $this->recordRepository->findByUuid($uuid);

        if (! $record instanceof Record || $record->entity_type_id !== $entityType->id) {
            return $this->recordNotFound();
        }

        if ($this->deniesRecord($record)) {
            return $this->forbiddenEntityType();
        }

        if ($record->uuid !== $uuid) {
            return to_route('admin.api.masterdata.records.show', [$type, $record->uuid], 301);
        }

        $relations = $request->includes('relations')
            ? ($this->relationRepository->visibleReferenceUuids([(int) $record->id])[$record->id] ?? [])
            : null;

        return $this->recordResponse(new RecordResource(
            $record,
            $this->xrefService->idsFor($record),
            $this->relationRepository->referenceValues($record),
            $relations,
        ));
    }

    /**
     * Create a record of the entity type.
     *
     * Answers 201 with the record, its `Location` and its revision as `ETag`, or 202 when the write is held for
     * approval.
     */
    public function store(StoreRecordRequest $request, string $type): JsonResponse
    {
        $entityType = $this->entityTypeRepository->findOneByField('code', $type);

        if (! $entityType instanceof EntityType) {
            return $this->entityTypeNotFound();
        }

        $author = $request->user();

        if (! $author instanceof Admin || Gate::forUser($author)->denies('masterdata.records.create', $entityType)) {
            return $this->forbiddenEntityType(trans('masterdata::app.api.forbidden-record-write'));
        }

        try {
            $result = $this->recordWriter->submitCreate($entityType, $request->validated(), $author, new SourceContext('api'));
        } catch (ChangeRequestPendingException $exception) {
            return $this->alreadyPending($exception);
        } catch (ValidationException $exception) {
            return $this->validateErrorResponse($exception->errors());
        }

        if ($result->isPending()) {
            return $this->pendingResponse($result);
        }

        return $this->recordResponse(new RecordResource($result->record->refresh(), [], []), JsonResponse::HTTP_CREATED)
            ->header('Location', route('admin.api.masterdata.records.show', [$type, $result->record->uuid]));
    }

    /**
     * Apply a partial update at the revision named by the `If-Match` header.
     *
     * Answers 428 without `If-Match`, 409 for a merged uuid, 412 when the revision is stale and 202 when the write
     * is held for approval.
     */
    public function update(UpdateRecordRequest $request, string $type, string $uuid): JsonResponse
    {
        return $this->write($request, $type, $uuid, 'update', fn (Record $record, int $revision, Admin $author, SourceContext $source): RecordWriteResult => $this->recordWriter->submitUpdate($record, $request->validated(), $revision, $author, $source));
    }

    /**
     * Retire the record at the revision named by the `If-Match` header; answers like an update.
     */
    public function delete(Request $request, string $type, string $uuid): JsonResponse
    {
        return $this->write($request, $type, $uuid, 'delete', fn (Record $record, int $revision, Admin $author, SourceContext $source): RecordWriteResult => $this->recordWriter->submitRetire($record, $revision, $author, $source));
    }

    /**
     * Locate the record, check the policy ability and the `If-Match` revision, then run the write.
     *
     * @param  Closure(Record, int, Admin, SourceContext): RecordWriteResult  $submit
     */
    protected function write(Request $request, string $type, string $uuid, string $ability, Closure $submit): JsonResponse
    {
        $entityType = $this->entityTypeRepository->findOneByField('code', $type);

        if (! $entityType instanceof EntityType) {
            return $this->entityTypeNotFound();
        }

        $expectedRevision = $this->expectedRevision($request);

        if ($expectedRevision === null) {
            return ApiError::make(ApiErrorCode::PreconditionRequired, JsonResponse::HTTP_PRECONDITION_REQUIRED, trans('masterdata::app.api.precondition-required'));
        }

        $record = $this->recordRepository->findByUuid($uuid);

        if (! $record instanceof Record || $record->entity_type_id !== $entityType->id) {
            return $this->recordNotFound();
        }

        $author = $request->user();

        if (! $author instanceof Admin || Gate::forUser($author)->denies($ability, $record)) {
            return $this->forbiddenEntityType(trans('masterdata::app.api.forbidden-record-write'));
        }

        if ($record->uuid !== $uuid) {
            return ApiError::make(ApiErrorCode::MergedRecord, JsonResponse::HTTP_CONFLICT, trans('masterdata::app.api.merged-record'), [
                'merged_into' => $record->uuid,
            ]);
        }

        if ($record->revision !== $expectedRevision) {
            return $this->staleRevision((int) $record->revision);
        }

        try {
            $result = $submit($record, $expectedRevision, $author, new SourceContext('api'));
        } catch (ChangeRequestPendingException $exception) {
            return $this->alreadyPending($exception);
        } catch (ValidationException $exception) {
            return $this->validateErrorResponse($exception->errors());
        } catch (StaleRecordException) {
            return $this->staleRevision((int) $this->recordRepository->find($record->id)?->revision);
        }

        if ($result->isPending()) {
            return $this->pendingResponse($result);
        }

        $record = $result->record->refresh();

        return $this->recordResponse(new RecordResource($record, $this->xrefService->idsFor($record), $this->relationRepository->referenceValues($record)));
    }

    /**
     * Read the revision from an `If-Match` header of a bare or quoted integer; null when missing or malformed.
     */
    protected function expectedRevision(Request $request): ?int
    {
        $header = trim((string) $request->header('If-Match'));

        return preg_match('/^"?(\d{1,18})"?$/', $header, $match) === 1 ? (int) $match[1] : null;
    }

    /**
     * Send the record with its revision as `ETag`.
     */
    protected function recordResponse(RecordResource $resource, int $status = JsonResponse::HTTP_OK): JsonResponse
    {
        $response = $resource->response()->setStatusCode($status);

        $response->setEtag((string) $resource->resource->revision);

        return $response;
    }

    /**
     * Answer 202 for a write the change gate holds for approval.
     */
    protected function pendingResponse(RecordWriteResult $result): JsonResponse
    {
        return ApiError::make(ApiErrorCode::PendingApproval, JsonResponse::HTTP_ACCEPTED, trans('masterdata::app.api.change-held'), [
            'change_request_id' => (int) $result->gate->reference,
        ]);
    }

    /**
     * Answer 409 when the record already holds a change waiting for approval.
     */
    protected function alreadyPending(ChangeRequestPendingException $exception): JsonResponse
    {
        return ApiError::make(ApiErrorCode::AlreadyPending, JsonResponse::HTTP_CONFLICT, (string) Arr::first(Arr::flatten($exception->errors())));
    }

    /**
     * Answer 412 for an `If-Match` revision that is no longer current.
     */
    protected function staleRevision(int $currentRevision): JsonResponse
    {
        return ApiError::make(ApiErrorCode::StaleRevision, JsonResponse::HTTP_PRECONDITION_FAILED, trans('masterdata::app.records.stale-revision'), [
            'current_revision' => $currentRevision,
        ]);
    }

    /**
     * Answer 404 for an unknown entity type.
     */
    protected function entityTypeNotFound(): JsonResponse
    {
        return ApiError::make(ApiErrorCode::EntityTypeNotFound, JsonResponse::HTTP_NOT_FOUND, trans('masterdata::app.api.entity-type-not-found'));
    }

    /**
     * Answer 404 for an unknown record.
     */
    protected function recordNotFound(): JsonResponse
    {
        return ApiError::make(ApiErrorCode::RecordNotFound, JsonResponse::HTTP_NOT_FOUND, trans('masterdata::app.api.record-not-found'));
    }
}
