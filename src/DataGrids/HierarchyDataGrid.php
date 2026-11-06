<?php

namespace Webkul\MasterDataManagement\DataGrids;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

class HierarchyDataGrid extends DataGrid
{
    protected $sortColumn = 'code';

    protected $sortOrder = 'asc';

    protected $itemsPerPage = 50;

    /**
     * Prepare query builder.
     *
     * @return Builder
     */
    public function prepareQueryBuilder()
    {
        $queryBuilder = DB::table('mdm_hierarchies')
            ->leftJoin('mdm_hierarchy_translations as requested_translation', function ($leftJoin): void {
                $leftJoin->on('requested_translation.hierarchy_id', '=', 'mdm_hierarchies.id')
                    ->where('requested_translation.locale', core()->getRequestedLocaleCode());
            })
            ->where('mdm_hierarchies.entity_type_id', (int) request()->route('entityTypeId'))
            ->select(
                'mdm_hierarchies.id',
                'mdm_hierarchies.code',
                'mdm_hierarchies.is_primary',
                'requested_translation.label as label',
            );

        $this->addFilter('id', 'mdm_hierarchies.id');
        $this->addFilter('code', 'mdm_hierarchies.code');
        $this->addFilter('is_primary', 'mdm_hierarchies.is_primary');
        $this->addFilter('label', 'requested_translation.label');

        return $queryBuilder;
    }

    /**
     * Prepare columns.
     */
    public function prepareColumns(): void
    {
        $this->addColumn([
            'index'      => 'code',
            'label'      => trans('masterdata::app.hierarchies.datagrid.code'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'label',
            'label'      => trans('masterdata::app.hierarchies.datagrid.label'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
            'closure'    => fn ($row): string => (string) ($row->label ?: ($row->is_primary ? trans('masterdata::app.hierarchies.primary-label') : $row->code)),
        ]);

        $this->addColumn([
            'index'      => 'is_primary',
            'label'      => trans('masterdata::app.hierarchies.datagrid.kind'),
            'type'       => 'boolean',
            'searchable' => false,
            'filterable' => true,
            'sortable'   => true,
            'closure'    => fn ($row): string => $row->is_primary
                ? '<span class="label-active">'.e(trans('masterdata::app.hierarchies.kinds.primary')).'</span>'
                : '<span class="label-info">'.e(trans('masterdata::app.hierarchies.kinds.alternate')).'</span>',
        ]);
    }

    /**
     * Prepare actions.
     */
    public function prepareActions(): void
    {
        $entityTypeId = (int) request()->route('entityTypeId');

        $this->addAction([
            'index'  => 'edit',
            'icon'   => 'icon-edit',
            'title'  => trans('masterdata::app.hierarchies.datagrid.edit'),
            'method' => 'PUT',
            'url'    => fn ($row): string => route('admin.masterdata.hierarchies.update', ['entityTypeId' => $entityTypeId, 'id' => $row->id]),
        ]);

        $this->addAction([
            'index'  => 'delete',
            'icon'   => 'icon-delete',
            'title'  => trans('masterdata::app.hierarchies.datagrid.delete'),
            'method' => 'DELETE',
            'url'    => fn ($row): string => route('admin.masterdata.hierarchies.destroy', ['entityTypeId' => $entityTypeId, 'id' => $row->id]),
        ]);
    }
}
