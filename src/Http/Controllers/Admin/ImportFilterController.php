<?php

namespace Webkul\MasterDataManagement\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\MasterDataManagement\Http\Controllers\Concerns\ResolvesAdmin;
use Webkul\MasterDataManagement\Http\Requests\ImportFilterOptionsRequest;
use Webkul\MasterDataManagement\Models\EntityType;
use Webkul\MasterDataManagement\Repositories\CodeListRepository;
use Webkul\MasterDataManagement\Repositories\EntityTypeRepository;
use Webkul\MasterDataManagement\Repositories\SourceSystemRepository;
use Webkul\MasterDataManagement\Security\RecordPolicy;
use Webkul\User\Models\Admin;

/**
 * Option sources for the filter fields of the master data importers on the DataTransfer import form, keyed by code
 * like the filters they fill. The job validators still check the saved values.
 */
class ImportFilterController extends Controller
{
    use ResolvesAdmin;

    public const string ACCESS_EDIT = 'edit';

    public function __construct(
        protected EntityTypeRepository $entityTypeRepository,
        protected SourceSystemRepository $sourceSystemRepository,
        protected CodeListRepository $codeListRepository,
    ) {}

    /**
     * The active entity types whose records the admin may create or edit, or only edit with `access=edit`.
     */
    public function entityTypes(ImportFilterOptionsRequest $request): JsonResponse
    {
        $admin = $this->admin();

        $codes = $request->codes();

        $term = mb_strtolower((string) $request->term());

        $options = $this->entityTypeRepository->listActive()
            ->filter(fn (EntityType $type): bool => ! $admin instanceof Admin || $this->writable($admin, $type, $request->editOnly()))
            ->map(fn (EntityType $type): array => ['code' => (string) $type->code, 'label' => ($type->name ?: $type->code).' ('.$type->code.')'])
            ->filter(fn (array $option): bool => $codes !== []
                ? in_array($option['code'], $codes, true)
                : $term === '' || str_contains(mb_strtolower($option['label']), $term));

        return new JsonResponse(['options' => $options->values(), 'page' => 1, 'lastPage' => 1]);
    }

    /**
     * The active source systems, in priority order.
     */
    public function sourceSystems(ImportFilterOptionsRequest $request): JsonResponse
    {
        return new JsonResponse($this->sourceSystemRepository->searchCodeOptions(
            $request->term(), $request->codes(), $request->pageNumber(), ImportFilterOptionsRequest::PER_PAGE,
        ));
    }

    /**
     * The code lists, for admins who may edit code lists.
     */
    public function codeLists(ImportFilterOptionsRequest $request): JsonResponse
    {
        $admin = $this->admin();

        if ($admin instanceof Admin && $admin->role?->permission_type !== 'all' && ! $admin->hasPermission('masterdata.code_lists.edit')) {
            return new JsonResponse(['options' => [], 'page' => 1, 'lastPage' => 1]);
        }

        return new JsonResponse($this->codeListRepository->searchCodeOptions(
            $request->term(), $request->codes(), $request->pageNumber(), ImportFilterOptionsRequest::PER_PAGE,
        ));
    }

    protected function writable(Admin $admin, EntityType $type, bool $editOnly): bool
    {
        $gate = Gate::forUser($admin);

        return $gate->allows('update', RecordPolicy::subjectForEntityType((int) $type->id))
            || (! $editOnly && $gate->allows('masterdata.records.create', $type));
    }
}
