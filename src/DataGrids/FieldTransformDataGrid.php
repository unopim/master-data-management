<?php

namespace Webkul\MasterDataManagement\DataGrids;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;
use Webkul\MasterDataManagement\Standardization\TransformRegistry;

class FieldTransformDataGrid extends DataGrid
{
    protected $sortColumn = 'field_code';

    protected $sortOrder = 'asc';

    protected $itemsPerPage = 50;

    public function __construct(protected TransformRegistry $transformRegistry) {}

    /**
     * Prepare query builder.
     *
     * @return Builder
     */
    public function prepareQueryBuilder()
    {
        $queryBuilder = DB::table('mdm_field_transforms')
            ->join('mdm_fields', 'mdm_fields.id', '=', 'mdm_field_transforms.field_id')
            ->where('mdm_fields.entity_type_id', (int) request()->route('entityTypeId'))
            ->select(
                'mdm_field_transforms.id',
                'mdm_fields.code as field_code',
                'mdm_field_transforms.transform',
                'mdm_field_transforms.sort_order',
            );

        $this->addFilter('id', 'mdm_field_transforms.id');
        $this->addFilter('field_code', 'mdm_fields.code');
        $this->addFilter('transform', 'mdm_field_transforms.transform');
        $this->addFilter('sort_order', 'mdm_field_transforms.sort_order');

        return $queryBuilder;
    }

    /**
     * Prepare columns.
     */
    public function prepareColumns(): void
    {
        $this->addColumn([
            'index'      => 'field_code',
            'label'      => trans('masterdata::app.field-transforms.datagrid.field'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'transform',
            'label'      => trans('masterdata::app.field-transforms.datagrid.transform'),
            'type'       => 'dropdown',
            'searchable' => false,
            'filterable' => true,
            'sortable'   => true,
            'options'    => [
                'type'   => 'basic',
                'params' => [
                    'options' => array_map(
                        fn (array $option): array => ['label' => $option['label'], 'value' => $option['id']],
                        $this->transformRegistry->options(),
                    ),
                ],
            ],
            'closure' => fn ($row): string => e($this->transformRegistry->label((string) $row->transform)),
        ]);

        $this->addColumn([
            'index'      => 'sort_order',
            'label'      => trans('masterdata::app.field-transforms.datagrid.sort-order'),
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
            'title'  => trans('masterdata::app.field-transforms.datagrid.edit'),
            'method' => 'PUT',
            'url'    => fn ($row): string => route('admin.masterdata.field_transforms.update', ['entityTypeId' => $entityTypeId, 'id' => $row->id]),
        ]);

        $this->addAction([
            'index'  => 'delete',
            'icon'   => 'icon-delete',
            'title'  => trans('masterdata::app.field-transforms.datagrid.delete'),
            'method' => 'DELETE',
            'url'    => fn ($row): string => route('admin.masterdata.field_transforms.destroy', ['entityTypeId' => $entityTypeId, 'id' => $row->id]),
        ]);
    }
}
