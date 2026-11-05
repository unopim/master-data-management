<?php

namespace Webkul\MasterDataManagement\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Webkul\AdminApi\Http\Controllers\API\ApiController;
use Webkul\MasterDataManagement\Enums\ApiErrorCode;
use Webkul\MasterDataManagement\Http\Controllers\Api\Concerns\AuthorizesRecordViews;
use Webkul\MasterDataManagement\Http\Requests\Api\ResolveXrefRequest;
use Webkul\MasterDataManagement\Http\Resources\XrefResource;
use Webkul\MasterDataManagement\Http\Responses\ApiError;
use Webkul\MasterDataManagement\Models\Record;
use Webkul\MasterDataManagement\Repositories\EntityTypeRepository;
use Webkul\MasterDataManagement\Repositories\SourceSystemRepository;
use Webkul\MasterDataManagement\Services\XrefService;

class XrefController extends ApiController
{
    use AuthorizesRecordViews;

    public function __construct(
        protected XrefService $xrefService,
        protected SourceSystemRepository $sourceSystemRepository,
        protected EntityTypeRepository $entityTypeRepository,
    ) {}

    /**
     * Resolve a source system's external id to the golden record and all of its system ids.
     */
    public function resolve(ResolveXrefRequest $request): JsonResponse|XrefResource
    {
        $system = $this->sourceSystemRepository->findOneByField('code', $request->string('system')->toString());
        $type = $this->entityTypeRepository->findOneByField('code', $request->string('type')->toString());

        if ($type && $this->deniesEntityType($type->id)) {
            return $this->forbiddenEntityType();
        }

        $record = $system && $type
            ? $this->xrefService->resolve($system, $type->id, $request->string('id')->toString())
            : null;

        if (! $record instanceof Record) {
            return ApiError::make(ApiErrorCode::XrefNotFound, JsonResponse::HTTP_NOT_FOUND, trans('masterdata::app.api.xref-not-found'));
        }

        if ($this->deniesRecord($record)) {
            return $this->forbiddenEntityType();
        }

        return new XrefResource($record, $this->xrefService->idsFor($record));
    }
}
