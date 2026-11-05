<?php

namespace Webkul\MasterDataManagement\Http\Controllers\Admin;

use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\MasterDataManagement\Http\Controllers\Concerns\ResolvesAdmin;
use Webkul\MasterDataManagement\Http\Requests\CrosswalkLookupRequest;
use Webkul\MasterDataManagement\Models\Record;
use Webkul\MasterDataManagement\Models\SourceSystem;
use Webkul\MasterDataManagement\Repositories\EntityTypeRepository;
use Webkul\MasterDataManagement\Repositories\SourceSystemRepository;
use Webkul\MasterDataManagement\Security\EntityTypePermissions;
use Webkul\MasterDataManagement\Security\RecordPolicy;
use Webkul\MasterDataManagement\Services\XrefService;
use Webkul\User\Models\Admin;

/**
 * The crosswalk lookup page: one source system's external id to the golden record and its ids in every system,
 * resolved by the same service as the REST `xref/resolve` endpoint.
 */
class CrosswalkController extends Controller
{
    use ResolvesAdmin;

    public function __construct(
        protected XrefService $xrefService,
        protected SourceSystemRepository $sourceSystemRepository,
        protected EntityTypeRepository $entityTypeRepository,
        protected EntityTypePermissions $entityTypePermissions,
    ) {}

    /**
     * Show the lookup form and, once submitted, the resolved record or the not-found state.
     *
     * A type the role may not view and a record the admin may not see both show the not-found state.
     */
    public function index(CrosswalkLookupRequest $request): View
    {
        $admin = $this->admin();

        $result = null;

        if ($request->isLookup()) {
            $system = $this->sourceSystemRepository->find((int) $request->validated('source_system_id'));

            $typeId = (int) $request->validated('entity_type_id');

            $record = $system && Gate::allows('view', RecordPolicy::subjectForEntityType($typeId))
                ? $this->xrefService->resolve($system, $typeId, (string) $request->validated('external_id'))
                : null;

            if ($record instanceof Record && Gate::denies('view', $record)) {
                $record = null;
            }

            $record?->load('entityType.translations');

            $result = [
                'system' => $system instanceof SourceSystem ? $this->sourceSystemRepository->optionLabel($system) : '',
                'record' => $record,
                'ids'    => $record instanceof Record ? $this->idsWithNames($record) : [],
            ];
        }

        return view('masterdata::crosswalk.index', [
            'typeOptions'   => $this->entityTypeRepository->options($admin instanceof Admin ? $this->entityTypePermissions->visibleEntityTypeIds($admin) : []),
            'lookup'        => $request->isLookup() ? $request->validated() : [],
            'result'        => $result,
        ]);
    }

    /**
     * The record's active ids per source system, as the resolve API returns them, with each system's name.
     *
     * @return list<array{code: string, name: string, ids: list<string>}>
     */
    protected function idsWithNames(Record $record): array
    {
        $names = $this->sourceSystemRepository->namesByCode();

        $rows = [];

        foreach ($this->xrefService->idsFor($record) as $code => $ids) {
            $rows[] = ['code' => (string) $code, 'name' => $names[$code] ?? (string) $code, 'ids' => $ids];
        }

        return $rows;
    }
}
