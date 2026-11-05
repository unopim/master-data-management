<?php

namespace Webkul\MasterDataManagement\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\MasterDataManagement\Http\Controllers\Concerns\ResolvesAdmin;
use Webkul\MasterDataManagement\Models\EntityType;
use Webkul\MasterDataManagement\Repositories\EntityTypeRepository;
use Webkul\MasterDataManagement\Security\EntityTypePermissions;
use Webkul\User\Models\Admin;

/**
 * Option sources for the filter fields of the master data exporters on the DataTransfer export form.
 */
class ExportFilterController extends Controller
{
    use ResolvesAdmin;

    public function __construct(
        protected EntityTypeRepository $entityTypeRepository,
        protected EntityTypePermissions $entityTypePermissions,
    ) {}

    /**
     * The active entity types the admin may view, as `code`/`label` options in the async select contract.
     */
    public function entityTypes(Request $request): JsonResponse
    {
        abort_unless(bouncer()->hasPermission('data_transfer.export'), 403, trans('admin::app.common.unauthorized'));

        $admin = $this->admin();

        $options = $this->entityTypeRepository
            ->listActive($admin instanceof Admin ? $this->entityTypePermissions->visibleEntityTypeIds($admin) : [])
            ->map(fn (EntityType $type): array => ['code' => $type->code, 'label' => ($type->name ?: $type->code).' ('.$type->code.')']);

        $identifiers = $request->input('identifiers.values');

        if (! empty($identifiers)) {
            $codes = is_array($identifiers) ? $identifiers : explode(',', (string) $identifiers);

            $options = $options->whereIn('code', $codes);
        } elseif (($term = mb_strtolower(trim((string) $request->query('query', '')))) !== '') {
            $options = $options->filter(fn (array $option): bool => str_contains(mb_strtolower($option['label']), $term));
        }

        return new JsonResponse([
            'options'  => $options->values(),
            'page'     => 1,
            'lastPage' => 1,
        ]);
    }
}
