<?php

namespace Webkul\MasterDataManagement\DataGrids;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;
use Webkul\MasterDataManagement\Enums\ChangeOperation;
use Webkul\MasterDataManagement\Enums\ChangeRequestStatus;
use Webkul\MasterDataManagement\Security\EntityTypePermissions;
use Webkul\MasterDataManagement\Security\RecordScope;
use Webkul\User\Models\Admin;

class ChangeRequestDataGrid extends DataGrid
{
    protected $sortColumn = 'mdm_change_requests.id';

    public function __construct(
        protected EntityTypePermissions $entityTypePermissions,
        protected RecordScope $recordScope,
    ) {}

    /**
     * Prepare query builder.
     *
     * Requests of entity types the admin may not view are left out, and on scoped types so are requests whose record,
     * or whose proposed parent for a create, lies outside the admin's view grants.
     *
     * @return Builder
     */
    public function prepareQueryBuilder()
    {
        $admin = auth()->guard('admin')->user();

        $visible = $admin instanceof Admin ? $this->entityTypePermissions->visibleEntityTypeIds($admin) : [];

        $queryBuilder = DB::table('mdm_change_requests')
            ->join('mdm_entity_types', 'mdm_entity_types.id', '=', 'mdm_change_requests.entity_type_id')
            ->leftJoin('mdm_records', 'mdm_records.id', '=', 'mdm_change_requests.record_id')
            ->leftJoin('mdm_records as proposed_parents', 'proposed_parents.id', '=', 'mdm_change_requests.parent_id')
            ->leftJoin('admins as requesters', 'requesters.id', '=', 'mdm_change_requests.requested_by')
            ->leftJoin('admins as deciders', 'deciders.id', '=', 'mdm_change_requests.decided_by')
            ->when($visible !== null, fn (Builder $query) => $query->whereIn('mdm_change_requests.entity_type_id', $visible))
            ->select(
                'mdm_change_requests.id',
                'mdm_change_requests.operation',
                'mdm_change_requests.status',
                'mdm_change_requests.created_at',
                'mdm_change_requests.decided_at',
                'mdm_entity_types.code as entity_type_code',
                'mdm_records.code as record_code',
                'requesters.name as requester_name',
                'deciders.name as decider_name',
            );

        if ($admin instanceof Admin) {
            $this->recordScope->applyToRows($queryBuilder, $admin, 'mdm_change_requests.entity_type_id', 'mdm_records.path', 'mdm_change_requests.record_id');

            $queryBuilder->where(fn (Builder $creates) => $creates
                ->whereNotNull('mdm_change_requests.record_id')
                ->orWhere(fn (Builder $placed) => $this->recordScope->applyToRows($placed->whereNull('mdm_change_requests.record_id'), $admin, 'mdm_change_requests.entity_type_id', 'proposed_parents.path')));
        }

        $this->addFilter('id', 'mdm_change_requests.id');
        $this->addFilter('operation', 'mdm_change_requests.operation');
        $this->addFilter('status', 'mdm_change_requests.status');
        $this->addFilter('created_at', 'mdm_change_requests.created_at');
        $this->addFilter('decided_at', 'mdm_change_requests.decided_at');
        $this->addFilter('entity_type_code', 'mdm_entity_types.code');
        $this->addFilter('record_code', 'mdm_records.code');
        $this->addFilter('requester_name', 'requesters.name');
        $this->addFilter('decider_name', 'deciders.name');

        return $queryBuilder;
    }

    /**
     * Prepare columns.
     */
    public function prepareColumns(): void
    {
        $this->addColumn([
            'index'      => 'id',
            'label'      => trans('masterdata::app.approvals.datagrid.id'),
            'type'       => 'integer',
            'searchable' => false,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'entity_type_code',
            'label'      => trans('masterdata::app.approvals.datagrid.entity-type'),
            'type'       => 'string',
            'searchable' => false,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'record_code',
            'label'      => trans('masterdata::app.approvals.datagrid.record'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
            'closure'    => fn ($row): string => e($row->record_code ?? trans('masterdata::app.approvals.new-record')),
        ]);

        $this->addColumn([
            'index'      => 'operation',
            'label'      => trans('masterdata::app.approvals.datagrid.operation'),
            'type'       => 'dropdown',
            'searchable' => false,
            'filterable' => true,
            'sortable'   => true,
            'options'    => [
                'type'   => 'basic',
                'params' => [
                    'options' => array_map(
                        fn (ChangeOperation $operation): array => [
                            'label' => trans('masterdata::app.approvals.operations.'.$operation->value),
                            'value' => $operation->value,
                        ],
                        ChangeOperation::cases()
                    ),
                ],
            ],
            'closure' => fn ($row): string => e(trans('masterdata::app.approvals.operations.'.$row->operation)),
        ]);

        $this->addColumn([
            'index'      => 'status',
            'label'      => trans('masterdata::app.approvals.datagrid.status'),
            'type'       => 'dropdown',
            'searchable' => false,
            'filterable' => true,
            'sortable'   => true,
            'options'    => [
                'type'   => 'basic',
                'params' => [
                    'options' => array_map(
                        fn (ChangeRequestStatus $status): array => [
                            'label' => trans('masterdata::app.approvals.statuses.'.$status->value),
                            'value' => $status->value,
                        ],
                        ChangeRequestStatus::cases()
                    ),
                ],
            ],
            'closure' => fn ($row): string => e(trans('masterdata::app.approvals.statuses.'.$row->status)),
        ]);

        $this->addColumn([
            'index'      => 'requester_name',
            'label'      => trans('masterdata::app.approvals.datagrid.requested-by'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'created_at',
            'label'      => trans('masterdata::app.approvals.datagrid.requested-at'),
            'type'       => 'date_range',
            'searchable' => false,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'decider_name',
            'label'      => trans('masterdata::app.approvals.datagrid.decided-by'),
            'type'       => 'string',
            'searchable' => false,
            'filterable' => true,
            'sortable'   => true,
        ]);
    }

    /**
     * Prepare actions.
     */
    public function prepareActions(): void
    {
        $this->addAction([
            'index'  => 'review',
            'icon'   => 'icon-view',
            'title'  => trans('masterdata::app.approvals.datagrid.review'),
            'method' => 'GET',
            'url'    => fn ($row): string => route('admin.masterdata.approvals.show', $row->id),
        ]);
    }
}
