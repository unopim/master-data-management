<?php

namespace Webkul\MasterDataManagement\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\MasterDataManagement\Http\Controllers\Concerns\ResolvesAdmin;
use Webkul\MasterDataManagement\Http\Requests\RecordGraphRequest;
use Webkul\MasterDataManagement\Repositories\RecordRepository;
use Webkul\MasterDataManagement\Services\RecordGraph;

class RecordGraphController extends Controller
{
    use ResolvesAdmin;

    public function __construct(
        protected RecordRepository $recordRepository,
        protected RecordGraph $recordGraph,
    ) {}

    /**
     * The nodes and edges around the record that the admin may see.
     */
    public function show(RecordGraphRequest $request, string $uuid): JsonResponse
    {
        $record = $this->recordRepository->findByUuid($uuid) ?? abort(404);

        $this->authorize('view', $record);

        return new JsonResponse($this->recordGraph->around($record, $request->hops(), $request->limit(), $this->admin()));
    }
}
