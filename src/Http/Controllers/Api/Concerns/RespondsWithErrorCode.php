<?php

namespace Webkul\MasterDataManagement\Http\Controllers\Api\Concerns;

use Illuminate\Http\JsonResponse;
use Webkul\MasterDataManagement\Http\Responses\ApiError;

/**
 * Errors keep the core REST shape (`success`, `message`) and add a stable `code` clients can branch on.
 */
trait RespondsWithErrorCode
{
    /**
     * Build an error body in the core shape with the stable code.
     */
    protected function errorResponse(string $code, string $message, int $status): JsonResponse
    {
        return ApiError::withCode($code, $status, $message);
    }
}
