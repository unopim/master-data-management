<?php

namespace Webkul\MasterDataManagement\DataGrids;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;
use Webkul\MasterDataManagement\Models\Icon;

class IconDataGrid extends DataGrid
{
    /**
     * Prepare query builder.
     *
     * @return Builder
     */
    public function prepareQueryBuilder()
    {
        $usage = DB::table('mdm_entity_types')
            ->selectRaw('count(*)')
            ->whereColumn('mdm_entity_types.icon_id', 'mdm_icons.id');

        $queryBuilder = DB::table('mdm_icons')
            ->select('mdm_icons.id', 'mdm_icons.code', 'mdm_icons.name', 'mdm_icons.path')
            ->selectSub($usage, 'usage_count');

        $this->addFilter('id', 'mdm_icons.id');
        $this->addFilter('code', 'mdm_icons.code');
        $this->addFilter('name', 'mdm_icons.name');

        return $queryBuilder;
    }

    /**
     * Prepare columns.
     */
    public function prepareColumns(): void
    {
        $this->addColumn([
            'index'      => 'id',
            'label'      => trans('masterdata::app.icons.datagrid.id'),
            'type'       => 'integer',
            'searchable' => false,
            'filterable' => false,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'path',
            'label'      => trans('masterdata::app.icons.datagrid.preview'),
            'type'       => 'string',
            'searchable' => false,
            'filterable' => false,
            'sortable'   => false,
            'closure'    => fn ($row): string => view('masterdata::icons._mask', [
                'url'    => Icon::urlFor((int) $row->id, (string) $row->path),
                'vector' => Icon::isVectorPath((string) $row->path),
                'class'  => 'w-6 h-6 text-primary-600 dark:text-primary-400',
            ])->render(),
        ]);

        $this->addColumn([
            'index'      => 'code',
            'label'      => trans('masterdata::app.icons.datagrid.code'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'name',
            'label'      => trans('masterdata::app.icons.datagrid.name'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'usage_count',
            'label'      => trans('masterdata::app.icons.datagrid.usage'),
            'type'       => 'integer',
            'searchable' => false,
            'filterable' => false,
            'sortable'   => false,
        ]);
    }

    /**
     * Prepare actions.
     */
    public function prepareActions(): void
    {
        if (bouncer()->hasPermission('masterdata.icons.edit')) {
            $this->addAction([
                'index'  => 'edit',
                'icon'   => 'icon-edit',
                'title'  => trans('masterdata::app.icons.datagrid.edit'),
                'method' => 'GET',
                'url'    => fn ($row): string => route('admin.masterdata.icons.edit', $row->id),
            ]);
        }

        if (bouncer()->hasPermission('masterdata.icons.delete')) {
            $this->addAction([
                'index'  => 'delete',
                'icon'   => 'icon-delete',
                'title'  => trans('masterdata::app.icons.datagrid.delete'),
                'method' => 'DELETE',
                'url'    => fn ($row): string => route('admin.masterdata.icons.destroy', $row->id),
            ]);
        }
    }
}
