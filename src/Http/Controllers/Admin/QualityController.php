<?php

namespace Webkul\MasterDataManagement\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\MasterDataManagement\DataGrids\QualityIssueDataGrid;
use Webkul\MasterDataManagement\Enums\QualityIssueStatus;
use Webkul\MasterDataManagement\Enums\QualitySeverity;
use Webkul\MasterDataManagement\Http\Controllers\Concerns\ResolvesAdmin;
use Webkul\MasterDataManagement\Http\Controllers\Concerns\RespondsWithRedirect;
use Webkul\MasterDataManagement\Http\Requests\QualityDecisionRequest;
use Webkul\MasterDataManagement\Http\Requests\QualityScanRequest;
use Webkul\MasterDataManagement\Jobs\RunQualityScan;
use Webkul\MasterDataManagement\Models\EntityType;
use Webkul\MasterDataManagement\Models\QualityIssue;
use Webkul\MasterDataManagement\Quality\QualityScanner;
use Webkul\MasterDataManagement\Repositories\EntityTypeRepository;
use Webkul\MasterDataManagement\Repositories\QualityIssueRepository;
use Webkul\MasterDataManagement\Repositories\RecordRepository;
use Webkul\MasterDataManagement\Security\EntityTypePermissions;
use Webkul\MasterDataManagement\Security\RecordPolicy;
use Webkul\User\Models\Admin;

/**
 * The data quality page: completeness per entity type, the issues grid, queued scans and steward decisions.
 *
 * Rows are limited to the entity types the role may view; deciding an issue also needs record edit on its type.
 */
class QualityController extends Controller
{
    use ResolvesAdmin, RespondsWithRedirect;

    public function __construct(
        protected EntityTypeRepository $entityTypeRepository,
        protected EntityTypePermissions $entityTypePermissions,
        protected RecordRepository $recordRepository,
        protected QualityIssueRepository $qualityIssueRepository,
        protected QualityScanner $qualityScanner,
    ) {}

    /**
     * Show the per-type summary and the issues grid.
     */
    public function index(Request $request): View|JsonResponse
    {
        abort_unless(bouncer()->hasPermission('masterdata.quality'), 403);

        if ($request->ajax()) {
            return resolve(QualityIssueDataGrid::class)->toJson();
        }

        $admin = $this->admin();

        $types = $this->entityTypeRepository->listActive($admin instanceof Admin ? $this->entityTypePermissions->visibleEntityTypeIds($admin) : []);

        $ids = array_map(intval(...), $types->modelKeys());

        $completeness = $this->recordRepository->completenessSummary($ids);

        $issues = $this->qualityIssueRepository->openCountsByEntityType($ids);

        $scanned = $this->qualityScanner->lastScannedAt($ids);

        return view('masterdata::quality.index', [
            'canScan' => bouncer()->hasPermission('masterdata.quality.scan'),
            'summary' => $types->map(fn (EntityType $type): array => [
                'type'       => $type,
                'records'    => $completeness[$type->id]['records'] ?? 0,
                'average'    => $completeness[$type->id]['average'] ?? null,
                'complete'   => $completeness[$type->id]['complete'] ?? 0,
                'errors'     => $issues[$type->id][QualitySeverity::Error->value] ?? 0,
                'warnings'   => ($issues[$type->id][QualitySeverity::Warning->value] ?? 0) + ($issues[$type->id][QualitySeverity::Info->value] ?? 0),
                'scanned_at' => $scanned[$type->id] ?? null,
            ])->all(),
        ]);
    }

    /**
     * Queue a quality scan of an entity type.
     */
    public function scan(QualityScanRequest $request, int $id): JsonResponse
    {
        $type = $this->entityTypeRepository->findOrFail($id);

        Gate::authorize('masterdata.records.viewAny', $type);

        dispatch(new RunQualityScan((int) $type->id));

        $message = trans('masterdata::app.quality.scan.queued', ['type' => $type->name ?: $type->code]);

        return $this->redirectResponse(route('admin.masterdata.quality.index'), $message);
    }

    /**
     * Mark an open issue resolved.
     */
    public function resolve(QualityDecisionRequest $request, int $id): JsonResponse
    {
        return $this->decide($request, $id, QualityIssueStatus::Resolved, trans('masterdata::app.quality.resolve-success'));
    }

    /**
     * Mark an open issue ignored, so later scans keep it closed.
     */
    public function ignore(QualityDecisionRequest $request, int $id): JsonResponse
    {
        return $this->decide($request, $id, QualityIssueStatus::Ignored, trans('masterdata::app.quality.ignore-success'));
    }

    /**
     * Record the steward's decision on an open issue of a type the admin may edit records of.
     */
    protected function decide(QualityDecisionRequest $request, int $id, QualityIssueStatus $status, string $message): JsonResponse
    {
        $issue = $this->qualityIssueRepository->find($id);

        abort_unless($issue instanceof QualityIssue, 404);

        Gate::authorize('update', RecordPolicy::subjectForEntityType((int) $issue->entity_type_id));

        if (! $issue->isOpen()) {
            return new JsonResponse(['message' => trans('masterdata::app.quality.not-open')], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        $admin = $this->admin() ?? abort(403);

        $this->qualityIssueRepository->decide($issue, $status, (int) $admin->id);

        if ($request->validated('return_to') === QualityDecisionRequest::RETURN_TO_RECORD) {
            return $this->redirectResponse(route('admin.masterdata.records.edit', (string) $request->validated('record')), $message);
        }

        return new JsonResponse(['message' => $message]);
    }
}
