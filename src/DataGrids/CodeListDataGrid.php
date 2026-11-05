<?php

namespace Webkul\MasterDataManagement\DataGrids;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;
use Webkul\MasterDataManagement\Enums\CodeListVersionStatus;

class CodeListDataGrid extends DataGrid
{
    /**
     * Prepare query builder.
     *
     * @return Builder
     */
    public function prepareQueryBuilder()
    {
        $publishedVersion = DB::table('mdm_code_list_versions')
            ->selectRaw('max(version)')
            ->whereColumn('mdm_code_list_versions.code_list_id', 'mdm_code_lists.id')
            ->where('mdm_code_list_versions.status', CodeListVersionStatus::Published->value);

        $queryBuilder = DB::table('mdm_code_lists')
            ->leftJoin('mdm_code_list_translations as requested_translation', function ($leftJoin): void {
                $leftJoin->on('requested_translation.code_list_id', '=', 'mdm_code_lists.id')
                    ->where('requested_translation.locale', core()->getRequestedLocaleCode());
            })
            ->select(
                'mdm_code_lists.id',
                'mdm_code_lists.code',
                'mdm_code_lists.owner',
                'mdm_code_lists.is_versioned',
                'requested_translation.name as name'
            )
            ->selectSub($publishedVersion, 'published_version');

        $this->addFilter('id', 'mdm_code_lists.id');
        $this->addFilter('code', 'mdm_code_lists.code');
        $this->addFilter('owner', 'mdm_code_lists.owner');
        $this->addFilter('is_versioned', 'mdm_code_lists.is_versioned');
        $this->addFilter('name', 'requested_translation.name');

        return $queryBuilder;
    }

    /**
     * Prepare columns.
     */
    public function prepareColumns(): void
    {
        $this->addColumn([
            'index'      => 'code',
            'label'      => trans('masterdata::app.code-lists.datagrid.code'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'name',
            'label'      => trans('masterdata::app.code-lists.datagrid.name'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'owner',
            'label'      => trans('masterdata::app.code-lists.datagrid.owner'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'published_version',
            'label'      => trans('masterdata::app.code-lists.datagrid.published-version'),
            'type'       => 'integer',
            'searchable' => false,
            'filterable' => false,
            'sortable'   => false,
            'closure'    => fn ($row): string => $row->published_version === null
                ? e(trans('masterdata::app.code-lists.datagrid.none'))
                : e(trans('masterdata::app.code-lists.versions.label', ['version' => $row->published_version])),
        ]);

        $this->addColumn([
            'index'      => 'is_versioned',
            'label'      => trans('masterdata::app.code-lists.datagrid.is-versioned'),
            'type'       => 'boolean',
            'searchable' => false,
            'filterable' => true,
            'sortable'   => true,
            'closure'    => fn ($row): string => e(trans('masterdata::app.code-lists.datagrid.'.($row->is_versioned ? 'yes' : 'no'))),
        ]);
    }

    /**
     * Prepare actions.
     */
    public function prepareActions(): void
    {
        if (bouncer()->hasPermission('masterdata.code_lists.edit')) {
            $this->addAction([
                'index'  => 'edit',
                'icon'   => 'icon-edit',
                'title'  => trans('masterdata::app.code-lists.datagrid.edit'),
                'method' => 'GET',
                'url'    => fn ($row): string => route('admin.masterdata.code_lists.edit', $row->id),
            ]);
        }

        if (bouncer()->hasPermission('masterdata.code_lists.delete')) {
            $this->addAction([
                'index'  => 'delete',
                'icon'   => 'icon-delete',
                'title'  => trans('masterdata::app.code-lists.datagrid.delete'),
                'method' => 'DELETE',
                'url'    => fn ($row): string => route('admin.masterdata.code_lists.destroy', $row->id),
            ]);
        }
    }
}
