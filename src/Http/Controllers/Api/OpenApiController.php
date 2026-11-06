<?php

namespace Webkul\MasterDataManagement\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Webkul\AdminApi\Http\Controllers\API\ApiController;
use Webkul\MasterDataManagement\Services\OpenApiDocument;

class OpenApiController extends ApiController
{
    /**
     * Serve the OpenAPI document of the master data API.
     */
    public function show(OpenApiDocument $document): JsonResponse
    {
        return new JsonResponse($document->toArray(), JsonResponse::HTTP_OK, [], JSON_UNESCAPED_SLASHES);
    }
}
