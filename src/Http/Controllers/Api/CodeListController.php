<?php

namespace Webkul\MasterDataManagement\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Webkul\AdminApi\Http\Controllers\API\ApiController;
use Webkul\MasterDataManagement\Enums\ApiErrorCode;
use Webkul\MasterDataManagement\Http\Requests\Api\ListCodeListsRequest;
use Webkul\MasterDataManagement\Http\Requests\Api\ShowCodeListRequest;
use Webkul\MasterDataManagement\Http\Resources\CodeListResource;
use Webkul\MasterDataManagement\Http\Responses\ApiError;
use Webkul\MasterDataManagement\Models\CodeList;
use Webkul\MasterDataManagement\Repositories\CodeListRepository;
use Webkul\MasterDataManagement\Repositories\CodeListVersionRepository;
use Webkul\MasterDataManagement\Repositories\CodeRepository;

class CodeListController extends ApiController
{
    public function __construct(
        protected CodeListRepository $codeListRepository,
        protected CodeListVersionRepository $codeListVersionRepository,
        protected CodeRepository $codeRepository,
    ) {}

    /**
     * List the code lists by cursor, ordered by id.
     */
    public function index(ListCodeListsRequest $request): AnonymousResourceCollection
    {
        $limit = $request->integer('limit', 50);

        $page = $this->codeListRepository->cursorWithNames($limit, $request->string('cursor')->toString() ?: null);

        return CodeListResource::collection($page->items())->additional(['meta' => [
            'next_cursor' => $page->nextCursor()?->encode(),
            'limit'       => $limit,
        ]]);
    }

    /**
     * Show a code list with the active codes of the version in effect today, and all versions on request.
     */
    public function get(ShowCodeListRequest $request, string $listCode): JsonResponse|CodeListResource
    {
        $codeList = $this->codeListRepository->findByCodeWithNames($listCode);

        if (! $codeList instanceof CodeList) {
            return ApiError::make(ApiErrorCode::CodeListNotFound, JsonResponse::HTTP_NOT_FOUND, trans('masterdata::app.api.code-list-not-found'));
        }

        $today = now();

        $effective = $this->codeListVersionRepository->effectiveOn($codeList->id, $today);

        $effective?->setRelation('codes', $this->codeRepository->inEffectForVersion($effective->id, $today));

        return new CodeListResource(
            $codeList,
            true,
            $effective,
            $request->string('include')->toString() === 'versions' ? $this->codeListVersionRepository->allFor($codeList->id) : null,
        );
    }
}
