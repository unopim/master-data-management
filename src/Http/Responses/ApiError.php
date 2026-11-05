<?php

namespace Webkul\MasterDataManagement\Http\Responses;

use Illuminate\Http\JsonResponse;
use Webkul\MasterDataManagement\Enums\ApiErrorCode;

/**
 * Master data REST errors keep the core shape (`success`, `message`) and add the stable `code`.
 */
class ApiError
{
    /**
     * Build an error response for the code.
     *
     * @param  array<string, mixed>  $extra
     */
    public static function make(ApiErrorCode $code, int $status, string $message, array $extra = []): JsonResponse
    {
        return self::withCode($code->value, $status, $message, $extra);
    }

    /**
     * Build an error response for a code outside the enum, kept for the codes older endpoints already send.
     *
     * @param  array<string, mixed>  $extra
     */
    public static function withCode(string $code, int $status, string $message, array $extra = []): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'code'    => $code,
            ...$extra,
        ], $status);
    }
}
