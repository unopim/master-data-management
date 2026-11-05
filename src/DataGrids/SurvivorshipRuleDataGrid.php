<?php

namespace Webkul\MasterDataManagement\DataGrids;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;
use Webkul\MasterDataManagement\Survivorship\StrategyRegistry;

class SurvivorshipRuleDataGrid extends DataGrid
{
    protected $sortColumn = 'sort_order';

    protected $sortOrder = 'asc';

    protected $itemsPerPage = 50;

    public function __construct(protected StrategyRegistry $strategyRegistry) {}

    /**
     * Prepare query builder.
     *
     * @return Builder
     */
    public function prepareQueryBuilder()
    {
        $queryBuilder = DB::table('mdm_survivorship_rules')
            ->where('mdm_survivorship_rules.entity_type_id', (int) request()->route('entityTypeId'))
            ->select(
                'mdm_survivorship_rules.id',
                'mdm_survivorship_rules.target',
                'mdm_survivorship_rules.strategy',
                'mdm_survivorship_rules.sort_order',
            );

        $this->addFilter('id', 'mdm_survivorship_rules.id');
        $this->addFilter('target', 'mdm_survivorship_rules.target');
        $this->addFilter('strategy', 'mdm_survivorship_rules.strategy');
        $this->addFilter('sort_order', 'mdm_survivorship_rules.sort_order');

        return $queryBuilder;
    }

    /**
     * Prepare columns.
     */
    public function prepareColumns(): void
    {
        $this->addColumn([
            'index'      => 'target',
            'label'      => trans('masterdata::app.survivorship-rules.datagrid.target'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'strategy',
            'label'      => trans('masterdata::app.survivorship-rules.datagrid.strategy'),
            'type'       => 'dropdown',
            'searchable' => false,
            'filterable' => true,
            'sortable'   => true,
            'options'    => [
                'type'   => 'basic',
                'params' => [
                    'options' => array_map(
                        fn (array $option): array => ['label' => $option['label'], 'value' => $option['id']],
                        $this->strategyRegistry->options(),
                    ),
                ],
            ],
            'closure' => fn ($row): string => e($this->strategyRegistry->label((string) $row->strategy)),
        ]);

        $this->addColumn([
            'index'      => 'sort_order',
            'label'      => trans('masterdata::app.survivorship-rules.datagrid.sort-order'),
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
            'title'  => trans('masterdata::app.survivorship-rules.datagrid.edit'),
            'method' => 'PUT',
            'url'    => fn ($row): string => route('admin.masterdata.survivorship_rules.update', ['entityTypeId' => $entityTypeId, 'id' => $row->id]),
        ]);

        $this->addAction([
            'index'  => 'delete',
            'icon'   => 'icon-delete',
            'title'  => trans('masterdata::app.survivorship-rules.datagrid.delete'),
            'method' => 'DELETE',
            'url'    => fn ($row): string => route('admin.masterdata.survivorship_rules.destroy', ['entityTypeId' => $entityTypeId, 'id' => $row->id]),
        ]);
    }
}
