<?php

namespace Webkul\MasterDataManagement\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\MasterDataManagement\DataGrids\SourceSystemDataGrid;
use Webkul\MasterDataManagement\Enums\SourceSystemType;
use Webkul\MasterDataManagement\Http\Controllers\Concerns\RespondsWithRedirect;
use Webkul\MasterDataManagement\Http\Requests\SelectOptionsRequest;
use Webkul\MasterDataManagement\Http\Requests\SourceSystemRequest;
use Webkul\MasterDataManagement\Models\SourceSystem;
use Webkul\MasterDataManagement\Repositories\SourceSystemRepository;

class SourceSystemController extends Controller
{
    use RespondsWithRedirect;

    public function __construct(protected SourceSystemRepository $sourceSystemRepository) {}

    /**
     * Display the source system listing.
     */
    public function index(Request $request): View|JsonResponse
    {
        abort_unless(bouncer()->hasPermission('masterdata.source_systems'), 403);

        if ($request->ajax()) {
            return resolve(SourceSystemDataGrid::class)->toJson();
        }

        return view('masterdata::source-systems.index');
    }

    /**
     * Search the source systems for the admin async select; inactive systems are included only on request.
     */
    public function options(SelectOptionsRequest $request): JsonResponse
    {
        return new JsonResponse($this->sourceSystemRepository->searchOptions(
            $request->term(),
            $request->ids(),
            $request->pageNumber(),
            SelectOptionsRequest::PER_PAGE,
            ! $request->boolean('inactive'),
        ));
    }

    /**
     * Show the source system creation form.
     */
    public function create(): View
    {
        abort_unless(bouncer()->hasPermission('masterdata.source_systems.create'), 403);

        return view('masterdata::source-systems.create', $this->formData(new SourceSystem));
    }

    /**
     * Store a newly created source system.
     */
    public function store(SourceSystemRequest $request): JsonResponse
    {
        $system = $this->sourceSystemRepository->create($request->validated());

        return $this->redirectResponse(route('admin.masterdata.source_systems.edit', $system->id), trans('masterdata::app.source-systems.create-success'));
    }

    /**
     * Show the source system edit form.
     */
    public function edit(int $id): View
    {
        abort_unless(bouncer()->hasPermission('masterdata.source_systems.edit'), 403);

        return view('masterdata::source-systems.edit', $this->formData($this->sourceSystemRepository->findOrFail($id)));
    }

    /**
     * Update the source system.
     */
    public function update(SourceSystemRequest $request, int $id): JsonResponse
    {
        $this->sourceSystemRepository->findOrFail($id);

        $this->sourceSystemRepository->update($request->validated(), $id);

        return $this->redirectResponse(route('admin.masterdata.source_systems.edit', $id), trans('masterdata::app.source-systems.update-success'));
    }

    /**
     * Delete the source system.
     */
    public function destroy(int $id): JsonResponse
    {
        abort_unless(bouncer()->hasPermission('masterdata.source_systems.delete'), 403);

        $this->sourceSystemRepository->findOrFail($id);

        if ($this->sourceSystemRepository->hasXrefs($id)) {
            return new JsonResponse([
                'message' => trans('masterdata::app.source-systems.delete-has-xrefs'),
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        $this->sourceSystemRepository->delete($id);

        return new JsonResponse([
            'message' => trans('masterdata::app.source-systems.delete-success'),
        ]);
    }

    /**
     * Build the shared create/edit form view data.
     *
     * @return array<string, mixed>
     */
    protected function formData(SourceSystem $sourceSystem): array
    {
        return [
            'sourceSystem' => $sourceSystem,
            'typeOptions'  => array_map(
                fn (SourceSystemType $type): array => ['id' => $type->value, 'label' => trans('masterdata::app.source-systems.types.'.$type->value)],
                SourceSystemType::cases()
            ),
        ];
    }
}
