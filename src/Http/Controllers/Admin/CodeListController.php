<?php

namespace Webkul\MasterDataManagement\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Core\Repositories\LocaleRepository;
use Webkul\MasterDataManagement\DataGrids\CodeListDataGrid;
use Webkul\MasterDataManagement\Enums\CodeListVersionStatus;
use Webkul\MasterDataManagement\Exceptions\CodeListVersionNotDraftException;
use Webkul\MasterDataManagement\Http\Controllers\Concerns\RespondsWithRedirect;
use Webkul\MasterDataManagement\Http\Requests\CodeListRequest;
use Webkul\MasterDataManagement\Http\Requests\SelectOptionsRequest;
use Webkul\MasterDataManagement\Models\CodeList;
use Webkul\MasterDataManagement\Models\CodeListVersion;
use Webkul\MasterDataManagement\Repositories\CodeListRepository;
use Webkul\MasterDataManagement\Repositories\CodeListVersionRepository;
use Webkul\MasterDataManagement\Services\CodeListPublisher;

class CodeListController extends Controller
{
    use RespondsWithRedirect;

    public function __construct(
        protected CodeListRepository $codeListRepository,
        protected CodeListVersionRepository $versionRepository,
        protected LocaleRepository $localeRepository,
    ) {}

    /**
     * Display the code list listing.
     */
    public function index(Request $request): View|JsonResponse
    {
        abort_unless(bouncer()->hasPermission('masterdata.code_lists'), 403);

        if ($request->ajax()) {
            return resolve(CodeListDataGrid::class)->toJson();
        }

        return view('masterdata::code-lists.index');
    }

    /**
     * Search the code lists for the admin async select.
     */
    public function options(SelectOptionsRequest $request): JsonResponse
    {
        return new JsonResponse($this->codeListRepository->searchOptions($request->term(), $request->ids(), $request->pageNumber(), SelectOptionsRequest::PER_PAGE));
    }

    /**
     * Show the code list creation form.
     */
    public function create(): View
    {
        abort_unless(bouncer()->hasPermission('masterdata.code_lists.create'), 403);

        return view('masterdata::code-lists.create', [
            'codeList' => new CodeList,
            'locales'  => $this->localeRepository->getActiveLocales(),
        ]);
    }

    /**
     * Store a newly created code list with an empty first draft.
     */
    public function store(CodeListRequest $request): JsonResponse
    {
        $codeList = $this->codeListRepository->createWithDraft($request->payload());

        return $this->redirectResponse(
            route('admin.masterdata.code_lists.edit', $codeList->id),
            trans('masterdata::app.code-lists.create-success'),
        );
    }

    /**
     * Show the code list edit form with the codes of the requested version, or of the latest one.
     */
    public function edit(Request $request, int $id): View
    {
        abort_unless(bouncer()->hasPermission('masterdata.code_lists.edit'), 403);

        $codeList = $this->codeListRepository->with('translations')->findOrFail($id);

        $versions = $this->versionRepository->allFor($id);

        $version = $versions->firstWhere('id', $request->integer('version')) ?? $versions->first();

        return view('masterdata::code-lists.edit', [
            'codeList' => $codeList,
            'locales'  => $this->localeRepository->getActiveLocales(),
            'versions' => $versions,
            'version'  => $version,
            'hasDraft' => $versions->contains(fn (CodeListVersion $item): bool => $item->status === CodeListVersionStatus::Draft),
        ]);
    }

    /**
     * Update the code list.
     */
    public function update(CodeListRequest $request, int $id): JsonResponse
    {
        $this->codeListRepository->findOrFail($id);

        $this->codeListRepository->update($request->payload(), $id);

        return $this->redirectResponse(
            route('admin.masterdata.code_lists.edit', $id),
            trans('masterdata::app.code-lists.update-success'),
        );
    }

    /**
     * Delete the code list and its aliases, refused while a field draws its values from it.
     */
    public function destroy(int $id): JsonResponse
    {
        abort_unless(bouncer()->hasPermission('masterdata.code_lists.delete'), 403);

        $this->codeListRepository->findOrFail($id);

        if ($this->codeListRepository->isUsedByFields($id)) {
            return new JsonResponse([
                'message' => trans('masterdata::app.code-lists.delete-in-use'),
            ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        $this->codeListRepository->deleteWithAliases($id);

        return new JsonResponse([
            'message' => trans('masterdata::app.code-lists.delete-success'),
        ]);
    }

    /**
     * Create the next draft version from the latest one, refused while a draft already exists.
     */
    public function storeVersion(int $id): JsonResponse
    {
        abort_unless(bouncer()->hasPermission('masterdata.code_lists.edit'), 403);

        $codeList = $this->codeListRepository->findOrFail($id);

        if ($this->versionRepository->hasDraft($id)) {
            return new JsonResponse([
                'message' => trans('masterdata::app.code-lists.versions.draft-exists'),
            ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        $draft = $this->codeListRepository->createDraftFrom($codeList);

        return $this->redirectResponse(
            route('admin.masterdata.code_lists.edit', ['id' => $id, 'version' => $draft->id]),
            trans('masterdata::app.code-lists.versions.create-success', ['version' => $draft->version]),
        );
    }

    /**
     * Publish a draft version of the code list.
     */
    public function publishVersion(int $id, int $versionId, CodeListPublisher $publisher): JsonResponse
    {
        abort_unless(bouncer()->hasPermission('masterdata.code_lists.edit'), 403);

        $this->codeListRepository->findOrFail($id);

        $version = $this->versionRepository->findForCodeList($id, $versionId) ?? abort(404);

        try {
            $published = $publisher->publish($version, auth()->guard('admin')->user());
        } catch (CodeListVersionNotDraftException $exception) {
            return new JsonResponse(['message' => $exception->getMessage()], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $this->redirectResponse(
            route('admin.masterdata.code_lists.edit', ['id' => $id, 'version' => $published->id]),
            trans('masterdata::app.code-lists.versions.publish-success', ['version' => $published->version]),
        );
    }
}
