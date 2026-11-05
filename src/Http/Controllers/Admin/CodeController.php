<?php

namespace Webkul\MasterDataManagement\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\MasterDataManagement\DataGrids\CodeDataGrid;
use Webkul\MasterDataManagement\Enums\CodeListVersionStatus;
use Webkul\MasterDataManagement\Http\Controllers\Concerns\RespondsWithRedirect;
use Webkul\MasterDataManagement\Http\Requests\CodeRequest;
use Webkul\MasterDataManagement\Models\Code;
use Webkul\MasterDataManagement\Models\CodeListVersion;
use Webkul\MasterDataManagement\Repositories\CodeListVersionRepository;
use Webkul\MasterDataManagement\Repositories\CodeRepository;

class CodeController extends Controller
{
    use RespondsWithRedirect;

    public function __construct(
        protected CodeListVersionRepository $versionRepository,
        protected CodeRepository $codeRepository,
    ) {}

    /**
     * The codes grid of the version.
     */
    public function index(int $id, int $versionId): JsonResponse
    {
        abort_unless(bouncer()->hasPermission('masterdata.code_lists.edit'), 403);

        $this->version($id, $versionId);

        return resolve(CodeDataGrid::class)->toJson();
    }

    /**
     * Store a new code in the draft version.
     */
    public function store(CodeRequest $request, int $id, int $versionId): JsonResponse
    {
        if (! $this->isDraft($version = $this->version($id, $versionId))) {
            return $this->notDraft();
        }

        $this->codeRepository->create($request->payload());

        return $this->respond($version, trans('masterdata::app.codes.create-success'));
    }

    /**
     * Update a code of the draft version.
     */
    public function update(CodeRequest $request, int $id, int $versionId, int $codeId): JsonResponse
    {
        if (! $this->isDraft($version = $this->version($id, $versionId))) {
            return $this->notDraft();
        }

        $this->code($versionId, $codeId);

        $this->codeRepository->update($request->payload(), $codeId);

        return $this->respond($version, trans('masterdata::app.codes.update-success'));
    }

    /**
     * Delete a code of the draft version.
     */
    public function destroy(int $id, int $versionId, int $codeId): JsonResponse
    {
        abort_unless(bouncer()->hasPermission('masterdata.code_lists.edit'), 403);

        if (! $this->isDraft($this->version($id, $versionId))) {
            return $this->notDraft();
        }

        $this->code($versionId, $codeId);

        $this->codeRepository->delete($codeId);

        return new JsonResponse([
            'message' => trans('masterdata::app.codes.delete-success'),
        ]);
    }

    /**
     * Flash the message and answer with it and the version to return to.
     */
    protected function respond(CodeListVersion $version, string $message): JsonResponse
    {
        return $this->redirectResponse(route('admin.masterdata.code_lists.edit', ['id' => $version->code_list_id, 'version' => $version->id]), $message);
    }

    protected function notDraft(): JsonResponse
    {
        return new JsonResponse([
            'message' => trans('masterdata::app.code-lists.version-not-draft'),
        ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
    }

    protected function isDraft(CodeListVersion $version): bool
    {
        return $version->status === CodeListVersionStatus::Draft;
    }

    protected function version(int $codeListId, int $versionId): CodeListVersion
    {
        return $this->versionRepository->findForCodeList($codeListId, $versionId) ?? abort(404);
    }

    protected function code(int $versionId, int $codeId): Code
    {
        return $this->codeRepository->findForVersion($versionId, $codeId) ?? abort(404);
    }
}
