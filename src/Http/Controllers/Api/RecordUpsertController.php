<?php

namespace Webkul\MasterDataManagement\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Webkul\AdminApi\Http\Controllers\API\ApiController;
use Webkul\MasterDataManagement\Enums\ApiErrorCode;
use Webkul\MasterDataManagement\Enums\EntityTypeStatus;
use Webkul\MasterDataManagement\Exceptions\ChangeRequestPendingException;
use Webkul\MasterDataManagement\Exceptions\StaleRecordException;
use Webkul\MasterDataManagement\Exceptions\XrefConflictException;
use Webkul\MasterDataManagement\Http\Controllers\Api\Concerns\AuthorizesRecordViews;
use Webkul\MasterDataManagement\Http\Requests\Api\StoreRecordRequest;
use Webkul\MasterDataManagement\Http\Resources\RecordResource;
use Webkul\MasterDataManagement\Http\Responses\ApiError;
use Webkul\MasterDataManagement\Importers\Record\Importer;
use Webkul\MasterDataManagement\Models\EntityType;
use Webkul\MasterDataManagement\Models\Record;
use Webkul\MasterDataManagement\Models\SourceSystem;
use Webkul\MasterDataManagement\Repositories\EntityTypeRepository;
use Webkul\MasterDataManagement\Repositories\RelationRepository;
use Webkul\MasterDataManagement\Repositories\SourceSystemRepository;
use Webkul\MasterDataManagement\Services\RecordWriter;
use Webkul\MasterDataManagement\Services\XrefService;
use Webkul\MasterDataManagement\Services\XrefWriter;
use Webkul\MasterDataManagement\ValueObjects\GateResult;
use Webkul\MasterDataManagement\ValueObjects\RecordWriteResult;
use Webkul\MasterDataManagement\ValueObjects\SourceContext;
use Webkul\User\Models\Admin;

/**
 * Upserts a record by the id a source system knows it under: the system-to-system write path. The crosswalk entry
 * decides between updating the golden record it resolves to (following merges) and creating one linked to the id.
 * Every write passes the change gate as the API principal, so on a type that requires approval it is held instead.
 */
class RecordUpsertController extends ApiController
{
    use AuthorizesRecordViews;

    public function __construct(
        protected EntityTypeRepository $entityTypeRepository,
        protected SourceSystemRepository $sourceSystemRepository,
        protected XrefService $xrefService,
        protected XrefWriter $xrefWriter,
        protected RecordWriter $recordWriter,
        protected RelationRepository $relationRepository,
    ) {}

    /**
     * Update the record the external id resolves to, else create one and link the id.
     *
     * Returns 200 for an update, 201 for a create, 202 with the change request id when the write is held for
     * approval. An optional `If-Match` revision guards the update; without it the update applies at the revision
     * read here.
     */
    public function upsert(StoreRecordRequest $request, string $type, string $system, string $externalId): JsonResponse
    {
        $entityType = $this->entityTypeRepository->findOneByField('code', $type);

        $sourceSystem = $this->sourceSystemRepository->findOneByField('code', $system);

        if (! $entityType instanceof EntityType || ! $sourceSystem instanceof SourceSystem) {
            return ApiError::withCode('not_found', JsonResponse::HTTP_NOT_FOUND, trans('masterdata::app.api.system-or-type-not-found'));
        }

        if ($this->deniesEntityType((int) $entityType->id)) {
            return $this->forbiddenEntityType();
        }

        if (! $sourceSystem->is_active || $entityType->status !== EntityTypeStatus::Active) {
            return ApiError::withCode(
                'inactive',
                JsonResponse::HTTP_UNPROCESSABLE_ENTITY,
                trans($sourceSystem->is_active ? 'masterdata::app.api.type-inactive' : 'masterdata::app.api.system-inactive'),
            );
        }

        $externalId = trim($externalId);

        $input = $this->writerInput($request);

        $by = $request->user() instanceof Admin ? $request->user() : null;

        $record = $this->xrefService->resolve($sourceSystem, (int) $entityType->id, $externalId);

        try {
            return $record instanceof Record
                ? $this->update($request, $record, $input, $by, $sourceSystem, $externalId)
                : $this->create($entityType, $input, $by, $sourceSystem, $externalId);
        } catch (ChangeRequestPendingException $exception) {
            return ApiError::withCode('already_pending', JsonResponse::HTTP_CONFLICT, (string) Arr::first(Arr::flatten($exception->errors())));
        } catch (ValidationException $exception) {
            return response()->json([
                'success' => false,
                'message' => trans('masterdata::app.api.validation-failed'),
                'errors'  => $exception->errors(),
            ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        } catch (XrefConflictException $exception) {
            return ApiError::withCode('xref_conflict', JsonResponse::HTTP_CONFLICT, $exception->getMessage());
        } catch (StaleRecordException $exception) {
            return $this->staleResponse($exception->getMessage(), (int) $exception->record->revision);
        }
    }

    /**
     * Update the resolved record at the `If-Match` revision, or at its current one when none is sent.
     *
     * @param  array<string, mixed>  $input
     *
     * @throws ValidationException
     * @throws StaleRecordException
     */
    protected function update(StoreRecordRequest $request, Record $record, array $input, ?Admin $by, SourceSystem $system, string $externalId): JsonResponse
    {
        if (Gate::denies('update', $record)) {
            return $this->forbiddenEntityType();
        }

        $revision = $this->ifMatchRevision($request) ?? (int) $record->revision;

        if ($revision !== (int) $record->revision) {
            return $this->staleResponse(trans('masterdata::app.records.stale-revision'), (int) $record->revision);
        }

        unset($input['code']);

        $result = $this->recordWriter->submitUpdate($record, $input, $revision, $by, new SourceContext('api', (int) $system->id, $externalId));

        return $result->isPending() ? $this->pendingResponse($result->gate) : $this->recordResponse($result, JsonResponse::HTTP_OK);
    }

    /**
     * Create a record linked to the external id. A held create carries the id, linked once the create is approved;
     * while it waits, the same id cannot queue a second create.
     *
     * @param  array<string, mixed>  $input
     *
     * @throws ValidationException
     * @throws XrefConflictException
     */
    protected function create(EntityType $type, array $input, ?Admin $by, SourceSystem $system, string $externalId): JsonResponse
    {
        if (Gate::denies('masterdata.records.create', $type)) {
            return $this->forbiddenEntityType();
        }

        $source = new SourceContext('api', (int) $system->id, $externalId, [
            Importer::CONTEXT_XREF => [
                XrefWriter::INPUT_SYSTEM      => (int) $system->id,
                XrefWriter::INPUT_EXTERNAL_ID => $externalId,
            ],
        ]);

        $result = DB::transaction(function () use ($type, $input, $by, $system, $externalId, $source): RecordWriteResult {
            $result = $this->recordWriter->submitCreate($type, $input, $by, $source);

            if (! $result->isPending()) {
                $this->xrefWriter->link($result->record, $system, $externalId, $by, $source);
            }

            return $result;
        });

        if ($result->isPending()) {
            return $this->pendingResponse($result->gate);
        }

        return $this->recordResponse($result, JsonResponse::HTTP_CREATED)
            ->header('Location', route('admin.api.masterdata.records.show', [$type->code, $result->record->uuid]));
    }

    /**
     * The record writer input the request carries; an empty code lets the type build one.
     *
     * @return array<string, mixed>
     */
    protected function writerInput(StoreRecordRequest $request): array
    {
        $input = Arr::only($request->validated(), ['code', 'label', 'status', 'parent', 'values']);

        if (array_key_exists('code', $input) && ($input['code'] === null || $input['code'] === '')) {
            unset($input['code']);
        }

        return $input;
    }

    /**
     * The revision an `If-Match` header names, quoted or bare; null when the header is absent or not a revision.
     */
    protected function ifMatchRevision(StoreRecordRequest $request): ?int
    {
        $value = trim((string) $request->header('If-Match'), " \t\"");

        return ctype_digit($value) ? (int) $value : null;
    }

    /**
     * The written record with its external ids and references, its revision as the ETag.
     */
    protected function recordResponse(RecordWriteResult $result, int $status): JsonResponse
    {
        $record = $result->record->refresh();

        return new RecordResource($record, $this->xrefService->idsFor($record), $this->relationRepository->referenceValues($record))
            ->response()
            ->setStatusCode($status)
            ->header('ETag', '"'.$record->revision.'"');
    }

    /**
     * The 202 body of a write held for approval, naming the change request that holds it.
     */
    protected function pendingResponse(GateResult $gate): JsonResponse
    {
        return ApiError::make(ApiErrorCode::PendingApproval, JsonResponse::HTTP_ACCEPTED, trans('masterdata::app.api.change-held'), [
            'change_request_id' => (int) $gate->reference,
        ]);
    }

    /**
     * The 412 body of a write made at an outdated revision, with the record's current one.
     */
    protected function staleResponse(string $message, int $currentRevision): JsonResponse
    {
        return ApiError::make(ApiErrorCode::StaleRevision, JsonResponse::HTTP_PRECONDITION_FAILED, $message, [
            'current_revision' => $currentRevision,
        ]);
    }
}
