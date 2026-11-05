<?php

namespace Webkul\MasterDataManagement\DataGrids;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;
use Webkul\MasterDataManagement\FieldTypes\FieldTypeRegistry;

class FieldDataGrid extends DataGrid
{
    protected $sortColumn = 'position';

    protected $sortOrder = 'asc';

    protected $itemsPerPage = 50;

    /**
     * Prepare query builder.
     *
     * @return Builder
     */
    public function prepareQueryBuilder()
    {
        $queryBuilder = DB::table('mdm_fields')
            ->leftJoin('mdm_field_translations as requested_translation', function ($leftJoin): void {
                $leftJoin->on('requested_translation.field_id', '=', 'mdm_fields.id')
                    ->where('requested_translation.locale', core()->getRequestedLocaleCode());
            })
            ->where('mdm_fields.entity_type_id', (int) request()->route('entityTypeId'))
            ->select(
                'mdm_fields.id',
                'mdm_fields.code',
                'mdm_fields.type',
                'mdm_fields.is_required',
                'mdm_fields.is_searchable',
                'mdm_fields.is_archived',
                'mdm_fields.position',
                'requested_translation.label as label'
            );

        $this->addFilter('id', 'mdm_fields.id');
        $this->addFilter('code', 'mdm_fields.code');
        $this->addFilter('type', 'mdm_fields.type');
        $this->addFilter('is_required', 'mdm_fields.is_required');
        $this->addFilter('is_searchable', 'mdm_fields.is_searchable');
        $this->addFilter('is_archived', 'mdm_fields.is_archived');
        $this->addFilter('position', 'mdm_fields.position');
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
            'label'      => trans('masterdata::app.fields.datagrid.code'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'label',
            'label'      => trans('masterdata::app.fields.datagrid.label'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'type',
            'label'      => trans('masterdata::app.fields.datagrid.type'),
            'type'       => 'dropdown',
            'searchable' => false,
            'filterable' => true,
            'sortable'   => true,
            'options'    => [
                'type'   => 'basic',
                'params' => [
                    'options' => array_map(
                        fn (string $code): array => ['label' => $this->typeLabel($code), 'value' => $code],
                        resolve(FieldTypeRegistry::class)->codes()
                    ),
                ],
            ],
            'closure' => fn ($row): string => e($this->typeLabel($row->type)),
        ]);

        foreach (['is_required' => 'required', 'is_searchable' => 'searchable', 'is_archived' => 'archived'] as $index => $key) {
            $this->addColumn([
                'index'      => $index,
                'label'      => trans('masterdata::app.fields.datagrid.'.$key),
                'type'       => 'boolean',
                'searchable' => false,
                'filterable' => true,
                'sortable'   => true,
                'closure'    => fn ($row): string => e(trans('masterdata::app.fields.datagrid.'.($row->{$index} ? 'yes' : 'no'))),
            ]);
        }

        $this->addColumn([
            'index'      => 'position',
            'label'      => trans('masterdata::app.fields.datagrid.position'),
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
            'title'  => trans('masterdata::app.fields.datagrid.edit'),
            'method' => 'PUT',
            'url'    => fn ($row): string => route('admin.masterdata.fields.update', ['entityTypeId' => $entityTypeId, 'id' => $row->id]),
        ]);

        $this->addAction([
            'index'  => 'delete',
            'icon'   => 'icon-delete',
            'title'  => trans('masterdata::app.fields.datagrid.delete'),
            'method' => 'DELETE',
            'url'    => fn ($row): string => route('admin.masterdata.fields.destroy', ['entityTypeId' => $entityTypeId, 'id' => $row->id]),
        ]);
    }

    /**
     * The translated name of a field type, falling back to its code for types another package registers.
     */
    protected function typeLabel(string $code): string
    {
        $key = 'masterdata::app.fields.types.'.$code;

        return trans()->has($key) ? trans($key) : $code;
    }
}
