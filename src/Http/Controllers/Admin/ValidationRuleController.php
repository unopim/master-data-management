<?php

namespace Webkul\MasterDataManagement\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\MasterDataManagement\DataGrids\ValidationRuleDataGrid;
use Webkul\MasterDataManagement\Http\Controllers\Concerns\RespondsWithRedirect;
use Webkul\MasterDataManagement\Http\Requests\ValidationRuleRequest;
use Webkul\MasterDataManagement\Repositories\EntityTypeRepository;
use Webkul\MasterDataManagement\Repositories\FieldValidationRuleRepository;

class ValidationRuleController extends Controller
{
    use RespondsWithRedirect;

    public function __construct(
        protected EntityTypeRepository $entityTypeRepository,
        protected FieldValidationRuleRepository $fieldValidationRuleRepository,
    ) {}

    /**
     * The grid of the entity type's validation rules.
     */
    public function index(int $entityTypeId): JsonResponse
    {
        abort_unless(bouncer()->hasPermission('masterdata.entity_types.edit'), 403);

        $this->entityTypeRepository->findOrFail($entityTypeId);

        return resolve(ValidationRuleDataGrid::class)->toJson();
    }

    /**
     * Store a validation rule of the entity type.
     */
    public function store(ValidationRuleRequest $request, int $entityTypeId): JsonResponse
    {
        $this->entityTypeRepository->findOrFail($entityTypeId);

        $this->fieldValidationRuleRepository->create($request->payload());

        return $this->respond($entityTypeId, trans('masterdata::app.validation-rules.create-success'));
    }

    /**
     * Update a validation rule of the entity type.
     */
    public function update(ValidationRuleRequest $request, int $entityTypeId, int $id): JsonResponse
    {
        $this->fieldValidationRuleRepository->update($request->payload(), $request->validationRule()?->id ?? $id);

        return $this->respond($entityTypeId, trans('masterdata::app.validation-rules.update-success'));
    }

    /**
     * Delete a validation rule of the entity type.
     */
    public function destroy(int $entityTypeId, int $id): JsonResponse
    {
        abort_unless(bouncer()->hasPermission('masterdata.entity_types.edit'), 403);

        $row = $this->fieldValidationRuleRepository->findFor($entityTypeId, $id) ?? abort(404);

        $this->fieldValidationRuleRepository->delete($row->id);

        return $this->respond($entityTypeId, trans('masterdata::app.validation-rules.delete-success'));
    }

    /**
     * Flash the message and answer with it and the validation tab to return to.
     */
    protected function respond(int $entityTypeId, string $message): JsonResponse
    {
        return $this->redirectResponse(route('admin.masterdata.entity_types.edit', ['id' => $entityTypeId, 'validation' => 1]), $message);
    }
}
