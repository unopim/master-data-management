<?php

namespace Webkul\MasterDataManagement\Http\Controllers\Api\Concerns;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Webkul\MasterDataManagement\Enums\ApiErrorCode;
use Webkul\MasterDataManagement\Http\Responses\ApiError;
use Webkul\MasterDataManagement\Models\Record;
use Webkul\MasterDataManagement\Security\RecordPolicy;

/**
 * A role without a grant on the entity type gets a 403, not a 404: the api key already names the type.
 * The type is checked before any lookup so a denied caller cannot probe which records exist.
 */
trait AuthorizesRecordViews
{
    use RespondsWithErrorCode;

    /**
     * Whether the caller may not view records of the entity type at all.
     */
    protected function deniesEntityType(int $entityTypeId): bool
    {
        return Gate::denies('view', RecordPolicy::subjectForEntityType($entityTypeId));
    }

    /**
     * Whether the caller may not view the record.
     */
    protected function deniesRecord(Record $record): bool
    {
        return Gate::denies('view', $record);
    }

    /**
     * Build the 403 body for a missing entity type grant.
     */
    protected function forbiddenEntityType(?string $message = null): JsonResponse
    {
        return ApiError::make(ApiErrorCode::ForbiddenEntityType, JsonResponse::HTTP_FORBIDDEN, $message ?? trans('masterdata::app.api.forbidden-entity-type'));
    }
}
