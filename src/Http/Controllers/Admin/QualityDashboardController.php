<?php

namespace Webkul\MasterDataManagement\Http\Controllers\Admin;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Date;
use Illuminate\View\View;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\MasterDataManagement\Enums\TrendRange;
use Webkul\MasterDataManagement\Http\Controllers\Concerns\ResolvesAdmin;
use Webkul\MasterDataManagement\Http\Requests\QualityDashboardRequest;
use Webkul\MasterDataManagement\Models\EntityType;
use Webkul\MasterDataManagement\Quality\StewardWorkload;
use Webkul\MasterDataManagement\Quality\TrendChart;
use Webkul\MasterDataManagement\Repositories\EntityTypeRepository;
use Webkul\MasterDataManagement\Repositories\QualitySnapshotRepository;
use Webkul\MasterDataManagement\Security\AccessGrantResolver;
use Webkul\MasterDataManagement\Security\EntityTypePermissions;

/**
 * The data quality dashboard: the latest snapshot of each entity type with its change since the one before, the
 * trend of one type and the open tasks waiting on each steward.
 *
 * Snapshots aggregate a whole entity type, so a scoped type's scorecard and trend are shown only to admins whose
 * access is not limited to subtrees; its steward tasks are still counted within the admin's grants.
 */
class QualityDashboardController extends Controller
{
    use ResolvesAdmin;

    /**
     * Seconds an admin's dashboard reads stay cached.
     */
    protected const int CACHE_TTL = 60;

    public function __construct(
        protected EntityTypeRepository $entityTypeRepository,
        protected QualitySnapshotRepository $qualitySnapshotRepository,
        protected StewardWorkload $stewardWorkload,
    ) {}

    /**
     * Display the dashboard; the scoped permission services are method-injected because a controller instance can
     * outlive a request.
     */
    public function index(QualityDashboardRequest $request, EntityTypePermissions $entityTypePermissions, AccessGrantResolver $accessGrantResolver): View
    {
        $admin = $this->admin() ?? abort(403);

        $visibleIds = $entityTypePermissions->visibleEntityTypeIds($admin);

        $types = $this->entityTypeRepository->listActive($visibleIds)
            ->filter(fn (EntityType $type): bool => $accessGrantResolver->bypasses($admin) || ! $accessGrantResolver->isScoped((int) $type->id))
            ->values();

        $typeIds = array_map(intval(...), $types->modelKeys());

        $range = $request->range();

        $selectedId = in_array($request->entityTypeId(), $typeIds, true) ? $request->entityTypeId() : ($typeIds[0] ?? null);

        $data = Cache::remember(
            'masterdata.quality_dashboard.'.$admin->id.'.'.$selectedId.'.'.$range->value,
            self::CACHE_TTL,
            fn (): array => [
                'scorecards' => $this->qualitySnapshotRepository->latestWithPrevious($typeIds),
                'trend'      => $selectedId === null ? [] : $this->qualitySnapshotRepository->trend($selectedId, Date::today()->subDays($range->value - 1)),
                'workload'   => $this->stewardWorkload->forAdmin($admin, $visibleIds, Date::now()),
            ],
        );

        return view('masterdata::quality-dashboard.index', [
            'types'      => $types,
            'selected'   => $types->firstWhere('id', $selectedId),
            'range'      => $range,
            'ranges'     => TrendRange::cases(),
            'scorecards' => $data['scorecards'],
            'chart'      => new TrendChart($data['trend']),
            'workload'   => $data['workload'],
        ]);
    }
}
