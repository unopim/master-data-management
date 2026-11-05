<?php

namespace Webkul\MasterDataManagement\DataGrids;

use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;
use Webkul\MasterDataManagement\Enums\GrantAction;
use Webkul\MasterDataManagement\Enums\GrantPrincipal;
use Webkul\MasterDataManagement\Repositories\AccessGrantRepository;

class AccessGrantDataGrid extends DataGrid
{
    protected $sortColumn = 'mdm_access_grants.id';

    /**
     * Prepare query builder.
     *
     * @return Builder
     */
    public function prepareQueryBuilder()
    {
        $queryBuilder = DB::table('mdm_access_grants')
            ->join('mdm_records', 'mdm_records.id', '=', 'mdm_access_grants.record_id')
            ->leftJoin('mdm_entity_types', 'mdm_entity_types.id', '=', 'mdm_access_grants.entity_type_id')
            ->leftJoin('roles', fn (JoinClause $join) => $join
                ->on('roles.id', '=', 'mdm_access_grants.principal_id')
                ->where('mdm_access_grants.principal_type', GrantPrincipal::Role->value))
            ->leftJoin('admins', fn (JoinClause $join) => $join
                ->on('admins.id', '=', 'mdm_access_grants.principal_id')
                ->where('mdm_access_grants.principal_type', GrantPrincipal::User->value))
            ->select(
                'mdm_access_grants.id',
                'mdm_access_grants.principal_type',
                'mdm_access_grants.actions',
                'roles.name as role_name',
                'admins.name as user_name',
                'mdm_entity_types.code as entity_type_code',
                'mdm_records.code as record_code',
                'mdm_records.label as record_label',
            );

        $this->addFilter('id', 'mdm_access_grants.id');
        $this->addFilter('principal_type', 'mdm_access_grants.principal_type');
        $this->addFilter('entity_type_code', 'mdm_entity_types.code');
        $this->addFilter('record_code', 'mdm_records.code');

        return $queryBuilder;
    }

    /**
     * Prepare columns.
     */
    public function prepareColumns(): void
    {
        $this->addColumn([
            'index'      => 'id',
            'label'      => trans('masterdata::app.access.datagrid.id'),
            'type'       => 'integer',
            'searchable' => false,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'principal_type',
            'label'      => trans('masterdata::app.access.datagrid.principal-type'),
            'type'       => 'dropdown',
            'searchable' => false,
            'filterable' => true,
            'sortable'   => true,
            'options'    => [
                'type'   => 'basic',
                'params' => [
                    'options' => array_map(
                        fn (GrantPrincipal $type): array => ['label' => trans('masterdata::app.access.principals.'.$type->value), 'value' => $type->value],
                        GrantPrincipal::cases(),
                    ),
                ],
            ],
            'closure' => fn ($row): string => e(trans('masterdata::app.access.principals.'.$row->principal_type)),
        ]);

        $this->addColumn([
            'index'      => 'principal',
            'label'      => trans('masterdata::app.access.datagrid.principal'),
            'type'       => 'string',
            'searchable' => false,
            'filterable' => false,
            'sortable'   => false,
            'closure'    => fn ($row): string => e((string) ($row->role_name ?? $row->user_name ?? trans('masterdata::app.access.datagrid.missing-principal'))),
        ]);

        $this->addColumn([
            'index'      => 'entity_type_code',
            'label'      => trans('masterdata::app.access.datagrid.entity-type'),
            'type'       => 'string',
            'searchable' => false,
            'filterable' => true,
            'sortable'   => true,
            'closure'    => fn ($row): string => e((string) ($row->entity_type_code ?? trans('masterdata::app.access.form.any-entity-type'))),
        ]);

        $this->addColumn([
            'index'      => 'record_code',
            'label'      => trans('masterdata::app.access.datagrid.root'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
            'closure'    => fn ($row): string => e(trim($row->record_code.' '.($row->record_label ?? ''))),
        ]);

        $this->addColumn([
            'index'      => 'actions',
            'label'      => trans('masterdata::app.access.datagrid.actions'),
            'type'       => 'string',
            'searchable' => false,
            'filterable' => false,
            'sortable'   => false,
            'closure'    => fn ($row): string => e(implode(', ', array_map(
                fn (string $action): string => trans('masterdata::app.access.actions.'.GrantAction::from($action)->value),
                AccessGrantRepository::normalizeActions((array) json_decode((string) $row->actions, true)),
            ))),
        ]);
    }

    /**
     * Prepare actions.
     */
    public function prepareActions(): void
    {
        if (bouncer()->hasPermission('masterdata.access.edit')) {
            $this->addAction([
                'index'  => 'edit',
                'icon'   => 'icon-edit',
                'title'  => trans('masterdata::app.access.datagrid.edit'),
                'method' => 'GET',
                'url'    => fn ($row): string => route('admin.masterdata.access.edit', $row->id),
            ]);
        }

        if (bouncer()->hasPermission('masterdata.access.delete')) {
            $this->addAction([
                'index'  => 'delete',
                'icon'   => 'icon-delete',
                'title'  => trans('masterdata::app.access.datagrid.delete'),
                'method' => 'DELETE',
                'url'    => fn ($row): string => route('admin.masterdata.access.destroy', $row->id),
            ]);
        }
    }
}
