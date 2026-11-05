<?php

namespace Webkul\MasterDataManagement\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\MasterDataManagement\Repositories\EntityTypeRepository;
use Webkul\MasterDataManagement\Repositories\RecordRepository;
use Webkul\MasterDataManagement\Security\EntityTypePermissions;
use Webkul\MasterDataManagement\Services\EntityTypePlacement;
use Webkul\User\Models\Admin;

class DashboardController extends Controller
{
    /**
     * Seconds a role's record counts stay cached.
     */
    protected const COUNTS_TTL = 60;

    public function __construct(
        protected EntityTypeRepository $entityTypeRepository,
        protected RecordRepository $recordRepository,
    ) {}

    /**
     * The cache key of one role's dashboard record counts.
     */
    public static function countsCacheKey(int|string|null $roleId): string
    {
        return 'masterdata.dashboard.counts.'.$roleId;
    }

    /**
     * Send the Master Data root to the records dashboard, which lives under `records` so the menu marks only
     * record pages as part of it.
     */
    public function home(): RedirectResponse
    {
        return to_route('admin.masterdata.dashboard.index');
    }

    /**
     * Display the active entity types the admin may view in display order, with their placement and record counts.
     *
     * Counts are cached per role, since roles share the same visible entity types. The scoped
     * permissions are method-injected because a controller instance can outlive a request.
     */
    public function index(EntityTypePermissions $entityTypePermissions, EntityTypePlacement $entityTypePlacement): View
    {
        abort_unless(bouncer()->hasPermission('masterdata'), 403);

        $admin = auth()->guard('admin')->user();

        abort_unless($admin instanceof Admin, 403);

        $visibleIds = $entityTypePermissions->visibleEntityTypeIds($admin);

        $entityTypes = $this->entityTypeRepository->listActive($visibleIds);

        return view('masterdata::dashboard.index', [
            'entityTypes' => $entityTypes,
            'placements'  => $entityTypePlacement->labels($entityTypes),
            'counts'      => Cache::remember(
                self::countsCacheKey($admin->role_id),
                self::COUNTS_TTL,
                fn (): array => $this->recordRepository->countsByEntityType($visibleIds)
            ),
        ]);
    }
}
