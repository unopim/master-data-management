<?php

namespace Webkul\MasterDataManagement\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\MasterDataManagement\DataGrids\IconDataGrid;
use Webkul\MasterDataManagement\Http\Controllers\Concerns\RespondsWithRedirect;
use Webkul\MasterDataManagement\Http\Requests\IconRequest;
use Webkul\MasterDataManagement\Models\Icon;
use Webkul\MasterDataManagement\Repositories\IconRepository;
use Webkul\MasterDataManagement\Services\IconStore;

class IconController extends Controller
{
    use RespondsWithRedirect;

    public function __construct(
        protected IconRepository $iconRepository,
        protected IconStore $iconStore,
    ) {}

    /**
     * Display the icon library.
     */
    public function index(Request $request): View|JsonResponse
    {
        abort_unless(bouncer()->hasPermission('masterdata.icons'), 403);

        if ($request->ajax()) {
            return resolve(IconDataGrid::class)->toJson();
        }

        return view('masterdata::icons.index');
    }

    /**
     * Show the icon creation form.
     */
    public function create(): View
    {
        abort_unless(bouncer()->hasPermission('masterdata.icons.create'), 403);

        return view('masterdata::icons.create', ['icon' => new Icon]);
    }

    /**
     * Store a newly uploaded icon.
     */
    public function store(IconRequest $request): JsonResponse
    {
        $icon = $this->iconStore->save(
            $request->payload(),
            $request->upload(),
            null,
            fn (array $payload) => $this->iconRepository->create($payload),
        );

        return $this->redirectResponse(route('admin.masterdata.icons.edit', $icon->id), trans('masterdata::app.icons.create-success'));
    }

    /**
     * Show the icon edit form.
     */
    public function edit(int $id): View
    {
        abort_unless(bouncer()->hasPermission('masterdata.icons.edit'), 403);

        return view('masterdata::icons.edit', [
            'icon'       => $this->iconRepository->findOrFail($id),
            'usageCount' => $this->iconRepository->usageCount($id),
        ]);
    }

    /**
     * Rename the icon or replace its file; every entity type showing it picks up the change.
     */
    public function update(IconRequest $request, int $id): JsonResponse
    {
        $icon = $this->iconRepository->findOrFail($id);

        $this->iconStore->save(
            $request->payload(),
            $request->upload(),
            $icon->path,
            fn (array $payload) => $this->iconRepository->update($payload, $id),
        );

        return $this->redirectResponse(route('admin.masterdata.icons.edit', $id), trans('masterdata::app.icons.update-success'));
    }

    /**
     * Delete an icon no entity type shows, with its file.
     */
    public function destroy(int $id): JsonResponse
    {
        abort_unless(bouncer()->hasPermission('masterdata.icons.delete'), 403);

        $icon = $this->iconRepository->findOrFail($id);

        $usageCount = $this->iconRepository->usageCount($id);

        if ($usageCount > 0) {
            return new JsonResponse([
                'message' => trans_choice('masterdata::app.icons.delete-in-use', $usageCount, ['count' => $usageCount]),
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        $this->iconRepository->delete($id);

        $this->iconStore->delete($icon->path);

        return new JsonResponse([
            'message' => trans('masterdata::app.icons.delete-success'),
        ]);
    }

    /**
     * Serve the icon file to any admin who can open master data.
     */
    public function file(int $id): StreamedResponse
    {
        abort_unless(bouncer()->hasPermission('masterdata'), 403);

        return $this->iconStore->response($this->iconRepository->findOrFail($id)->path);
    }
}
