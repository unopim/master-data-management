<?php

namespace Webkul\MasterDataManagement\DataGrids;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;
use Webkul\MasterDataManagement\Matching\ComparatorRegistry;

class MatchRuleDataGrid extends DataGrid
{
    protected $sortColumn = 'sort_order';

    protected $sortOrder = 'asc';

    protected $itemsPerPage = 50;

    public function __construct(protected ComparatorRegistry $comparatorRegistry) {}

    /**
     * Prepare query builder.
     *
     * @return Builder
     */
    public function prepareQueryBuilder()
    {
        $queryBuilder = DB::table('mdm_match_rules')
            ->where('mdm_match_rules.entity_type_id', (int) request()->route('entityTypeId'))
            ->select(
                'mdm_match_rules.id',
                'mdm_match_rules.target',
                'mdm_match_rules.comparator',
                'mdm_match_rules.weight',
                'mdm_match_rules.is_blocking',
                'mdm_match_rules.sort_order',
            );

        $this->addFilter('id', 'mdm_match_rules.id');
        $this->addFilter('target', 'mdm_match_rules.target');
        $this->addFilter('comparator', 'mdm_match_rules.comparator');
        $this->addFilter('weight', 'mdm_match_rules.weight');
        $this->addFilter('is_blocking', 'mdm_match_rules.is_blocking');
        $this->addFilter('sort_order', 'mdm_match_rules.sort_order');

        return $queryBuilder;
    }

    /**
     * Prepare columns.
     */
    public function prepareColumns(): void
    {
        $this->addColumn([
            'index'      => 'target',
            'label'      => trans('masterdata::app.match-rules.datagrid.target'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'comparator',
            'label'      => trans('masterdata::app.match-rules.datagrid.comparator'),
            'type'       => 'dropdown',
            'searchable' => false,
            'filterable' => true,
            'sortable'   => true,
            'options'    => [
                'type'   => 'basic',
                'params' => [
                    'options' => array_map(
                        fn (array $option): array => ['label' => $option['label'], 'value' => $option['id']],
                        $this->comparatorRegistry->options(),
                    ),
                ],
            ],
            'closure' => fn ($row): string => e($this->comparatorRegistry->label((string) $row->comparator)),
        ]);

        $this->addColumn([
            'index'      => 'weight',
            'label'      => trans('masterdata::app.match-rules.datagrid.weight'),
            'type'       => 'string',
            'searchable' => false,
            'filterable' => false,
            'sortable'   => true,
            'closure'    => fn ($row): string => e((string) (float) $row->weight),
        ]);

        $this->addColumn([
            'index'      => 'is_blocking',
            'label'      => trans('masterdata::app.match-rules.datagrid.blocking'),
            'type'       => 'boolean',
            'searchable' => false,
            'filterable' => true,
            'sortable'   => true,
            'closure'    => fn ($row): string => e(trans('masterdata::app.match-rules.datagrid.'.($row->is_blocking ? 'yes' : 'no'))),
        ]);

        $this->addColumn([
            'index'      => 'sort_order',
            'label'      => trans('masterdata::app.match-rules.datagrid.sort-order'),
            'type'       => 'integer',
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
        $entityTypeId = (int) request()->route('entityTypeId');

        $this->addAction([
            'index'  => 'edit',
            'icon'   => 'icon-edit',
            'title'  => trans('masterdata::app.match-rules.datagrid.edit'),
            'method' => 'PUT',
            'url'    => fn ($row): string => route('admin.masterdata.match_rules.update', ['entityTypeId' => $entityTypeId, 'id' => $row->id]),
        ]);

        $this->addAction([
            'index'  => 'delete',
            'icon'   => 'icon-delete',
            'title'  => trans('masterdata::app.match-rules.datagrid.delete'),
            'method' => 'DELETE',
            'url'    => fn ($row): string => route('admin.masterdata.match_rules.destroy', ['entityTypeId' => $entityTypeId, 'id' => $row->id]),
        ]);
    }
}
