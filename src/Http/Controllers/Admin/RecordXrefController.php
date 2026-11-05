<?php

namespace Webkul\MasterDataManagement\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\MasterDataManagement\Enums\RecordStatus;
use Webkul\MasterDataManagement\Exceptions\XrefConflictException;
use Webkul\MasterDataManagement\Http\Controllers\Concerns\ResolvesAdmin;
use Webkul\MasterDataManagement\Http\Controllers\Concerns\RespondsWithRedirect;
use Webkul\MasterDataManagement\Http\Requests\XrefLinkRequest;
use Webkul\MasterDataManagement\Models\Record;
use Webkul\MasterDataManagement\Repositories\RecordRepository;
use Webkul\MasterDataManagement\Repositories\SourceSystemRepository;
use Webkul\MasterDataManagement\Services\XrefWriter;
use Webkul\MasterDataManagement\ValueObjects\SourceContext;

/**
 * Manual crosswalk maintenance on the record page: link a source system's external id, or unlink it. Both pass the
 * change gate, so on a type that requires approval they wait in the approvals inbox.
 */
class RecordXrefController extends Controller
{
    use ResolvesAdmin, RespondsWithRedirect;

    public function __construct(
        protected RecordRepository $recordRepository,
        protected SourceSystemRepository $sourceSystemRepository,
        protected XrefWriter $xrefWriter,
    ) {}

    /**
     * Link an external id of a source system to the record.
     *
     * @throws ValidationException
     */
    public function store(XrefLinkRequest $request, string $uuid): JsonResponse
    {
        $record = $this->editableRecord($uuid);

        $system = $this->sourceSystemRepository->findOrFail((int) $request->validated('source_system_id'));

        try {
            $gate = $this->xrefWriter->link($record, $system, (string) $request->validated('external_id'), $this->admin(), new SourceContext('admin'));
        } catch (XrefConflictException $exception) {
            throw ValidationException::withMessages(['external_id' => $exception->getMessage()]);
        }

        $url = route('admin.masterdata.records.edit', $record->uuid);

        return $gate->isPending()
            ? $this->pendingResponse($url)
            : $this->redirectResponse($url, trans('masterdata::app.records.xrefs.link-success'));
    }

    /**
     * Unlink an active external id from the record; the entry is kept as retired for the lineage.
     *
     * @throws ValidationException
     */
    public function destroy(string $uuid, int $xrefId): JsonResponse
    {
        $record = $this->editableRecord($uuid);

        $gate = $this->xrefWriter->unlink($record, $xrefId, $this->admin(), new SourceContext('admin')) ?? abort(404);

        $url = route('admin.masterdata.records.edit', $record->uuid);

        return $gate->isPending()
            ? $this->pendingResponse($url)
            : $this->redirectResponse($url, trans('masterdata::app.records.xrefs.unlink-success'));
    }

    /**
     * Find a draft or active record the admin may edit, else 404 / 403.
     */
    protected function editableRecord(string $uuid): Record
    {
        $record = $this->recordRepository->findByUuid($uuid) ?? abort(404);

        $this->authorize('update', $record);

        abort_unless(in_array($record->status, [RecordStatus::Draft, RecordStatus::Active], true), 422, trans('masterdata::app.records.not-editable'));

        return $record;
    }
}
