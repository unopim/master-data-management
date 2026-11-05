<?php

namespace Webkul\MasterDataManagement\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Webkul\AdminApi\Http\Controllers\API\ApiController;
use Webkul\MasterDataManagement\Enums\EntityTypeStatus;
use Webkul\MasterDataManagement\Http\Controllers\Api\Concerns\AuthorizesRecordViews;
use Webkul\MasterDataManagement\Http\Requests\Api\SubmitMatchCandidateRequest;
use Webkul\MasterDataManagement\Http\Resources\MatchCandidateResource;
use Webkul\MasterDataManagement\Matching\MatchInbox;
use Webkul\MasterDataManagement\Repositories\EntityTypeRepository;
use Webkul\MasterDataManagement\Repositories\SourceSystemRepository;
use Webkul\User\Models\Admin;

class MatchCandidateController extends ApiController
{
    use AuthorizesRecordViews;

    public function __construct(
        protected MatchInbox $matchInbox,
        protected SourceSystemRepository $sourceSystemRepository,
        protected EntityTypeRepository $entityTypeRepository,
    ) {}

    /**
     * Submit a source row no crosswalk entry resolves into the match inbox, with its best suggestions.
     *
     * Returns 201 for a new inbox row and 200 when the row held for the same external id is reopened.
     */
    public function store(SubmitMatchCandidateRequest $request): JsonResponse
    {
        $system = $this->sourceSystemRepository->findOneByField('code', $request->string('system')->toString());

        $type = $this->entityTypeRepository->findOneByField('code', $request->string('type')->toString());

        if (! $system || ! $type) {
            return $this->errorResponse('not_found', trans('masterdata::app.api.system-or-type-not-found'), JsonResponse::HTTP_NOT_FOUND);
        }

        if ($this->deniesEntityType($type->id)) {
            return $this->forbiddenEntityType();
        }

        if (! $system->is_active || $type->status !== EntityTypeStatus::Active) {
            return $this->errorResponse(
                'inactive',
                trans($system->is_active ? 'masterdata::app.api.type-inactive' : 'masterdata::app.api.system-inactive'),
                JsonResponse::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        $by = auth()->user();

        try {
            $candidate = $this->matchInbox->submit(
                $system,
                $type,
                $request->string('id')->toString(),
                $request->safe()->only(['code', 'label', 'values']),
                $by instanceof Admin ? $by : null,
            );
        } catch (ValidationException $exception) {
            return $this->errorResponse('already_linked', (string) collect($exception->errors())->flatten()->first(), JsonResponse::HTTP_CONFLICT);
        }

        $candidate->load(['sourceSystem', 'entityType']);

        return new MatchCandidateResource($candidate, $this->matchInbox->suggestionsFor($candidate, 5, $by instanceof Admin ? $by : null))
            ->response()
            ->setStatusCode($candidate->wasRecentlyCreated ? 201 : 200);
    }
}
