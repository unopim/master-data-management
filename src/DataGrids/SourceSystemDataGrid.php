<?php

namespace Webkul\MasterDataManagement\DataGrids;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;
use Webkul\MasterDataManagement\Enums\SourceSystemType;

class SourceSystemDataGrid extends DataGrid
{
    /**
     * Prepare query builder.
     *
     * @return Builder
     */
    public function prepareQueryBuilder()
    {
        $queryBuilder = DB::table('mdm_source_systems')->select(
            'mdm_source_systems.id',
            'mdm_source_systems.code',
            'mdm_source_systems.name',
            'mdm_source_systems.type',
            'mdm_source_systems.priority',
            'mdm_source_systems.is_active'
        );

        $this->addFilter('id', 'mdm_source_systems.id');
        $this->addFilter('code', 'mdm_source_systems.code');
        $this->addFilter('name', 'mdm_source_systems.name');
        $this->addFilter('type', 'mdm_source_systems.type');
        $this->addFilter('priority', 'mdm_source_systems.priority');
        $this->addFilter('is_active', 'mdm_source_systems.is_active');

        return $queryBuilder;
    }

    /**
     * Prepare columns.
     */
    public function prepareColumns(): void
    {
        $this->addColumn([
            'index'      => 'id',
            'label'      => trans('masterdata::app.source-systems.datagrid.id'),
            'type'       => 'integer',
            'searchable' => false,
            'filterable' => false,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'code',
            'label'      => trans('masterdata::app.source-systems.datagrid.code'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'name',
            'label'      => trans('masterdata::app.source-systems.datagrid.name'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'type',
            'label'      => trans('masterdata::app.source-systems.datagrid.type'),
            'type'       => 'dropdown',
            'searchable' => false,
            'filterable' => true,
            'sortable'   => true,
            'options'    => [
                'type'   => 'basic',
                'params' => [
                    'options' => array_map(
                        fn (SourceSystemType $type): array => [
                            'label' => trans('masterdata::app.source-systems.types.'.$type->value),
                            'value' => $type->value,
                        ],
                        SourceSystemType::cases()
                    ),
                ],
            ],
            'closure' => fn ($row): string => e(trans('masterdata::app.source-systems.types.'.$row->type)),
        ]);

        $this->addColumn([
            'index'      => 'priority',
            'label'      => trans('masterdata::app.source-systems.datagrid.priority'),
            'type'       => 'integer',
            'searchable' => false,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'is_active',
            'label'      => trans('masterdata::app.source-systems.datagrid.is-active'),
            'type'       => 'boolean',
            'searchable' => false,
            'filterable' => true,
            'sortable'   => true,
            'closure'    => function ($row): string {
                $active = (bool) $row->is_active;
                $label = e(trans('masterdata::app.source-systems.'.($active ? 'active' : 'inactive')));

                return $active
                    ? '<span class="label-active">'.$label.'</span>'
                    : '<span class="label-info text-gray-600 dark:text-gray-300">'.$label.'</span>';
            },
        ]);
    }

    /**
     * Prepare actions.
     */
    public function prepareActions(): void
    {
        if (bouncer()->hasPermission('masterdata.source_systems.edit')) {
            $this->addAction([
                'index'  => 'edit',
                'icon'   => 'icon-edit',
                'title'  => trans('masterdata::app.source-systems.datagrid.edit'),
                'method' => 'GET',
                'url'    => fn ($row): string => route('admin.masterdata.source_systems.edit', $row->id),
            ]);
        }

        if (bouncer()->hasPermission('masterdata.source_systems.delete')) {
            $this->addAction([
                'index'  => 'delete',
                'icon'   => 'icon-delete',
                'title'  => trans('masterdata::app.source-systems.datagrid.delete'),
                'method' => 'DELETE',
                'url'    => fn ($row): string => route('admin.masterdata.source_systems.destroy', $row->id),
            ]);
        }
    }
}
