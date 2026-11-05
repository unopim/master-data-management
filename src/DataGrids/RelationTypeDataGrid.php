<?php

namespace Webkul\MasterDataManagement\DataGrids;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;
use Webkul\MasterDataManagement\Enums\RelationCardinality;

class RelationTypeDataGrid extends DataGrid
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
        $queryBuilder = DB::table('mdm_relation_types')
            ->join('mdm_entity_types as target_type', 'target_type.id', '=', 'mdm_relation_types.to_entity_type_id')
            ->leftJoin('mdm_relation_type_translations as requested_translation', function ($leftJoin): void {
                $leftJoin->on('requested_translation.relation_type_id', '=', 'mdm_relation_types.id')
                    ->where('requested_translation.locale', core()->getRequestedLocaleCode());
            })
            ->where('mdm_relation_types.from_entity_type_id', (int) request()->route('entityTypeId'))
            ->select(
                'mdm_relation_types.id',
                'mdm_relation_types.code',
                'mdm_relation_types.cardinality',
                'mdm_relation_types.is_system',
                'target_type.code as target_type',
                'requested_translation.label as label'
            );

        $this->addFilter('id', 'mdm_relation_types.id');
        $this->addFilter('code', 'mdm_relation_types.code');
        $this->addFilter('cardinality', 'mdm_relation_types.cardinality');
        $this->addFilter('is_system', 'mdm_relation_types.is_system');
        $this->addFilter('target_type', 'target_type.code');
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
            'label'      => trans('masterdata::app.relation-types.datagrid.code'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'label',
            'label'      => trans('masterdata::app.relation-types.datagrid.label'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'target_type',
            'label'      => trans('masterdata::app.relation-types.datagrid.target-type'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'cardinality',
            'label'      => trans('masterdata::app.relation-types.datagrid.cardinality'),
            'type'       => 'dropdown',
            'searchable' => false,
            'filterable' => true,
            'sortable'   => true,
            'options'    => [
                'type'   => 'basic',
                'params' => [
                    'options' => array_map(
                        fn (RelationCardinality $case): array => ['label' => trans('masterdata::app.relation-types.cardinalities.'.$case->value), 'value' => $case->value],
                        RelationCardinality::cases(),
                    ),
                ],
            ],
            'closure' => fn ($row): string => e(trans('masterdata::app.relation-types.cardinalities.'.$row->cardinality)),
        ]);

        $this->addColumn([
            'index'      => 'is_system',
            'label'      => trans('masterdata::app.relation-types.datagrid.system'),
            'type'       => 'boolean',
            'searchable' => false,
            'filterable' => true,
            'sortable'   => true,
            'closure'    => fn ($row): string => e(trans('masterdata::app.relation-types.datagrid.'.($row->is_system ? 'yes' : 'no'))),
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
            'title'  => trans('masterdata::app.relation-types.datagrid.edit'),
            'method' => 'PUT',
            'url'    => fn ($row): string => route('admin.masterdata.relation_types.update', ['entityTypeId' => $entityTypeId, 'id' => $row->id]),
        ]);

        $this->addAction([
            'index'  => 'delete',
            'icon'   => 'icon-delete',
            'title'  => trans('masterdata::app.relation-types.datagrid.delete'),
            'method' => 'DELETE',
            'url'    => fn ($row): string => route('admin.masterdata.relation_types.destroy', ['entityTypeId' => $entityTypeId, 'id' => $row->id]),
        ]);
    }
}
