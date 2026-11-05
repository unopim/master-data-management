<?php

namespace Webkul\MasterDataManagement\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\MasterDataManagement\DataGrids\MatchRuleDataGrid;
use Webkul\MasterDataManagement\Http\Controllers\Concerns\RespondsWithRedirect;
use Webkul\MasterDataManagement\Http\Requests\MatchRuleRequest;
use Webkul\MasterDataManagement\Repositories\EntityTypeRepository;
use Webkul\MasterDataManagement\Repositories\MatchRuleRepository;

class MatchRuleController extends Controller
{
    use RespondsWithRedirect;

    public function __construct(
        protected EntityTypeRepository $entityTypeRepository,
        protected MatchRuleRepository $matchRuleRepository,
    ) {}

    /**
     * The grid of the entity type's match rules.
     */
    public function index(int $entityTypeId): JsonResponse
    {
        abort_unless(bouncer()->hasPermission('masterdata.entity_types.edit'), 403);

        $this->entityTypeRepository->findOrFail($entityTypeId);

        return resolve(MatchRuleDataGrid::class)->toJson();
    }

    /**
     * Store a match rule of the entity type.
     */
    public function store(MatchRuleRequest $request, int $entityTypeId): JsonResponse
    {
        $this->entityTypeRepository->findOrFail($entityTypeId);

        $this->matchRuleRepository->create($request->payload() + ['entity_type_id' => $entityTypeId]);

        return $this->respond($entityTypeId, trans('masterdata::app.match-rules.create-success'));
    }

    /**
     * Update a match rule of the entity type.
     */
    public function update(MatchRuleRequest $request, int $entityTypeId, int $id): JsonResponse
    {
        $this->matchRuleRepository->update($request->payload(), $request->matchRule()?->id ?? $id);

        return $this->respond($entityTypeId, trans('masterdata::app.match-rules.update-success'));
    }

    /**
     * Delete a match rule of the entity type.
     */
    public function destroy(int $entityTypeId, int $id): JsonResponse
    {
        abort_unless(bouncer()->hasPermission('masterdata.entity_types.edit'), 403);

        $rule = $this->matchRuleRepository->findFor($entityTypeId, $id) ?? abort(404);

        $this->matchRuleRepository->delete($rule->id);

        return $this->respond($entityTypeId, trans('masterdata::app.match-rules.delete-success'));
    }

    /**
     * Flash the message and answer with it and the matching tab to return to.
     */
    protected function respond(int $entityTypeId, string $message): JsonResponse
    {
        return $this->redirectResponse(route('admin.masterdata.entity_types.edit', ['id' => $entityTypeId, 'matching' => 1]), $message);
    }
}
