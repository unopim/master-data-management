<?php

namespace Webkul\MasterDataManagement\DataGrids;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;
use Webkul\MasterDataManagement\Enums\AliasScope;

class AliasDataGrid extends DataGrid
{
    /**
     * Prepare query builder.
     *
     * The raw scope, locale and source system values are selected again, so the edit modal opens prefilled from the row.
     *
     * @return Builder
     */
    public function prepareQueryBuilder()
    {
        $queryBuilder = DB::table('mdm_aliases')
            ->leftJoin('mdm_code_lists as scope_code_list', function ($leftJoin): void {
                $leftJoin->on('scope_code_list.id', '=', 'mdm_aliases.scope_id')
                    ->where('mdm_aliases.scope_type', AliasScope::CodeList->value);
            })
            ->leftJoin('mdm_fields as scope_field', function ($leftJoin): void {
                $leftJoin->on('scope_field.id', '=', 'mdm_aliases.scope_id')
                    ->where('mdm_aliases.scope_type', AliasScope::Field->value);
            })
            ->leftJoin('mdm_entity_types as scope_entity_type', 'scope_entity_type.id', '=', 'scope_field.entity_type_id')
            ->leftJoin('mdm_source_systems as source_system', 'source_system.id', '=', 'mdm_aliases.source_system_id')
            ->select(
                'mdm_aliases.id',
                'mdm_aliases.scope_type',
                'mdm_aliases.scope_type as scope_type_value',
                'mdm_aliases.scope_id',
                'mdm_aliases.term',
                'mdm_aliases.target',
                'mdm_aliases.locale',
                'mdm_aliases.source_system_id',
                'scope_code_list.code as scope_code_list_code',
                'scope_field.code as scope_field_code',
                'scope_entity_type.code as scope_entity_type_code',
                'source_system.name as source_system'
            );

        $this->addFilter('id', 'mdm_aliases.id');
        $this->addFilter('scope_type', 'mdm_aliases.scope_type');
        $this->addFilter('term', 'mdm_aliases.term');
        $this->addFilter('target', 'mdm_aliases.target');
        $this->addFilter('locale', 'mdm_aliases.locale');
        $this->addFilter('source_system', 'source_system.name');

        return $queryBuilder;
    }

    /**
     * Prepare columns.
     */
    public function prepareColumns(): void
    {
        foreach (['term' => 'term', 'target' => 'target'] as $index => $key) {
            $this->addColumn([
                'index'      => $index,
                'label'      => trans('masterdata::app.aliases.datagrid.'.$key),
                'type'       => 'string',
                'searchable' => true,
                'filterable' => true,
                'sortable'   => true,
            ]);
        }

        $this->addColumn([
            'index'      => 'scope_type',
            'label'      => trans('masterdata::app.aliases.datagrid.scope-type'),
            'type'       => 'dropdown',
            'searchable' => false,
            'filterable' => true,
            'sortable'   => true,
            'options'    => [
                'type'   => 'basic',
                'params' => [
                    'options' => array_map(
                        fn (AliasScope $scope): array => [
                            'label' => trans('masterdata::app.aliases.scopes.'.$scope->value),
                            'value' => $scope->value,
                        ],
                        AliasScope::cases()
                    ),
                ],
            ],
            'closure' => fn ($row): string => e(trans('masterdata::app.aliases.scopes.'.$row->scope_type)),
        ]);

        $this->addColumn([
            'index'      => 'scope',
            'label'      => trans('masterdata::app.aliases.datagrid.scope'),
            'type'       => 'string',
            'searchable' => false,
            'filterable' => false,
            'sortable'   => false,
            'closure'    => fn ($row): string => e(match ($row->scope_type_value) {
                AliasScope::CodeList->value => (string) $row->scope_code_list_code,
                AliasScope::Field->value    => $row->scope_field_code === null ? '' : $row->scope_entity_type_code.'.'.$row->scope_field_code,
                default                     => '',
            }),
        ]);

        $this->addColumn([
            'index'      => 'locale',
            'label'      => trans('masterdata::app.aliases.datagrid.locale'),
            'type'       => 'string',
            'searchable' => false,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'source_system',
            'label'      => trans('masterdata::app.aliases.datagrid.source-system'),
            'type'       => 'string',
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
        $this->addAction([
            'index'  => 'edit',
            'icon'   => 'icon-edit',
            'title'  => trans('masterdata::app.aliases.datagrid.edit'),
            'method' => 'PUT',
            'url'    => fn ($row): string => route('admin.masterdata.aliases.update', $row->id),
        ]);

        $this->addAction([
            'index'  => 'delete',
            'icon'   => 'icon-delete',
            'title'  => trans('masterdata::app.aliases.datagrid.delete'),
            'method' => 'DELETE',
            'url'    => fn ($row): string => route('admin.masterdata.aliases.destroy', $row->id),
        ]);
    }
}
