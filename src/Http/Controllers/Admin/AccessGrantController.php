<?php

namespace Webkul\MasterDataManagement\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\MasterDataManagement\DataGrids\AccessGrantDataGrid;
use Webkul\MasterDataManagement\Http\Controllers\Concerns\ResolvesAdmin;
use Webkul\MasterDataManagement\Http\Controllers\Concerns\RespondsWithRedirect;
use Webkul\MasterDataManagement\Http\Requests\AccessGrantRequest;
use Webkul\MasterDataManagement\Http\Requests\AccessRootOptionsRequest;
use Webkul\MasterDataManagement\Models\AccessGrant;
use Webkul\MasterDataManagement\Models\AccessGrantProxy;
use Webkul\MasterDataManagement\Repositories\AccessGrantRepository;
use Webkul\MasterDataManagement\Repositories\EntityTypeRepository;
use Webkul\MasterDataManagement\Repositories\RecordRepository;
use Webkul\MasterDataManagement\Services\AccessGrantFormOptions;
use Webkul\MasterDataManagement\Services\RecordPresenter;

/**
 * The Data access screen: every role and user grant on a record subtree, and the form that adds or changes one.
 */
class AccessGrantController extends Controller
{
    use ResolvesAdmin, RespondsWithRedirect;

    /**
     * The most records the subtree root picker returns per search.
     */
    public const int OPTIONS_LIMIT = 50;

    public function __construct(
        protected AccessGrantRepository $accessGrantRepository,
        protected RecordRepository $recordRepository,
        protected EntityTypeRepository $entityTypeRepository,
        protected RecordPresenter $recordPresenter,
        protected AccessGrantFormOptions $formOptions,
    ) {}

    /**
     * Display the grant listing.
     */
    public function index(Request $request): View|JsonResponse
    {
        abort_unless(bouncer()->hasPermission('masterdata.access'), 403);

        if ($request->ajax()) {
            return resolve(AccessGrantDataGrid::class)->toJson();
        }

        return view('masterdata::access.index');
    }

    /**
     * Search the draft and active records the admin may see, of any entity type, for the subtree root picker.
     */
    public function options(AccessRootOptionsRequest $request): JsonResponse
    {
        $uuids = array_values(array_map(strval(...), (array) $request->validated('identifiers.values', [])));

        $options = $this->recordRepository
            ->parentCandidates(null, $this->entityTypeRepository->getModel()->newQuery()->pluck('id')->map(intval(...))->all(), self::OPTIONS_LIMIT, $this->admin(), (string) $request->validated('query', ''), $uuids)
            ->map($this->recordPresenter->option(...))
            ->values()
            ->all();

        return new JsonResponse(['options' => $options, 'page' => 1, 'lastPage' => 1]);
    }

    /**
     * Show the grant creation form.
     */
    public function create(): View
    {
        abort_unless(bouncer()->hasPermission('masterdata.access.create'), 403);

        $class = AccessGrantProxy::modelClass();

        return view('masterdata::access.create', $this->formData(new $class(['actions' => ['view']])));
    }

    /**
     * Store a new grant.
     */
    public function store(AccessGrantRequest $request): JsonResponse
    {
        $this->accessGrantRepository->create($request->payload());

        return $this->redirectResponse(route('admin.masterdata.access.index'), trans('masterdata::app.access.create-success'));
    }

    /**
     * Show the grant edit form.
     */
    public function edit(int $id): View
    {
        abort_unless(bouncer()->hasPermission('masterdata.access.edit'), 403);

        return view('masterdata::access.edit', $this->formData($this->accessGrantRepository->with('record')->findOrFail($id)));
    }

    /**
     * Update the grant.
     */
    public function update(AccessGrantRequest $request, int $id): JsonResponse
    {
        $this->accessGrantRepository->findOrFail($id);

        $this->accessGrantRepository->update($request->payload(), $id);

        return $this->redirectResponse(route('admin.masterdata.access.edit', $id), trans('masterdata::app.access.update-success'));
    }

    /**
     * Delete the grant.
     */
    public function destroy(int $id): JsonResponse
    {
        abort_unless(bouncer()->hasPermission('masterdata.access.delete'), 403);

        $this->accessGrantRepository->findOrFail($id);

        $this->accessGrantRepository->delete($id);

        return new JsonResponse(['message' => trans('masterdata::app.access.delete-success')]);
    }

    /**
     * Build the shared create/edit form view data.
     *
     * @return array<string, mixed>
     */
    protected function formData(AccessGrant $grant): array
    {
        return [
            'grant'              => $grant,
            'principalOptions'   => $this->formOptions->principalTypes(),
            'roleOptions'        => $this->formOptions->roles(),
            'userOptions'        => $this->formOptions->users(),
            'entityTypeOptions'  => $this->formOptions->entityTypes(),
            'actionOptions'      => $this->formOptions->actions(),
        ];
    }
}
