<?php

namespace Webkul\MasterDataManagement\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\MasterDataManagement\DataGrids\SurvivorshipRuleDataGrid;
use Webkul\MasterDataManagement\Http\Controllers\Concerns\RespondsWithRedirect;
use Webkul\MasterDataManagement\Http\Requests\SurvivorshipRuleRequest;
use Webkul\MasterDataManagement\Repositories\EntityTypeRepository;
use Webkul\MasterDataManagement\Repositories\SurvivorshipRuleRepository;

class SurvivorshipRuleController extends Controller
{
    use RespondsWithRedirect;

    public function __construct(
        protected EntityTypeRepository $entityTypeRepository,
        protected SurvivorshipRuleRepository $survivorshipRuleRepository,
    ) {}

    /**
     * The grid of the entity type's survivorship rules.
     */
    public function index(int $entityTypeId): JsonResponse
    {
        abort_unless(bouncer()->hasPermission('masterdata.entity_types.edit'), 403);

        $this->entityTypeRepository->findOrFail($entityTypeId);

        return resolve(SurvivorshipRuleDataGrid::class)->toJson();
    }

    /**
     * Store a survivorship rule of the entity type.
     */
    public function store(SurvivorshipRuleRequest $request, int $entityTypeId): JsonResponse
    {
        $this->entityTypeRepository->findOrFail($entityTypeId);

        $this->survivorshipRuleRepository->create($request->payload() + ['entity_type_id' => $entityTypeId]);

        return $this->respond($entityTypeId, trans('masterdata::app.survivorship-rules.create-success'));
    }

    /**
     * Update a survivorship rule of the entity type.
     */
    public function update(SurvivorshipRuleRequest $request, int $entityTypeId, int $id): JsonResponse
    {
        $this->survivorshipRuleRepository->update($request->payload(), $request->survivorshipRule()?->id ?? $id);

        return $this->respond($entityTypeId, trans('masterdata::app.survivorship-rules.update-success'));
    }

    /**
     * Delete a survivorship rule of the entity type.
     */
    public function destroy(int $entityTypeId, int $id): JsonResponse
    {
        abort_unless(bouncer()->hasPermission('masterdata.entity_types.edit'), 403);

        $rule = $this->survivorshipRuleRepository->findFor($entityTypeId, $id) ?? abort(404);

        $this->survivorshipRuleRepository->delete($rule->id);

        return $this->respond($entityTypeId, trans('masterdata::app.survivorship-rules.delete-success'));
    }

    /**
     * Flash the message and answer with it and the survivorship tab to return to.
     */
    protected function respond(int $entityTypeId, string $message): JsonResponse
    {
        return $this->redirectResponse(route('admin.masterdata.entity_types.edit', ['id' => $entityTypeId, 'survivorship' => 1]), $message);
    }
}
