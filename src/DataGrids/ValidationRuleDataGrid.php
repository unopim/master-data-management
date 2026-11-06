<?php

namespace Webkul\MasterDataManagement\DataGrids;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;
use Webkul\MasterDataManagement\Enums\ValidationSeverity;
use Webkul\MasterDataManagement\Validation\RuleTypeRegistry;

class ValidationRuleDataGrid extends DataGrid
{
    protected $sortColumn = 'sort_order';

    protected $sortOrder = 'asc';

    protected $itemsPerPage = 50;

    public function __construct(protected RuleTypeRegistry $ruleTypeRegistry) {}

    /**
     * Prepare query builder.
     *
     * @return Builder
     */
    public function prepareQueryBuilder()
    {
        $queryBuilder = DB::table('mdm_validation_rules')
            ->join('mdm_fields', 'mdm_fields.id', '=', 'mdm_validation_rules.field_id')
            ->where('mdm_fields.entity_type_id', (int) request()->route('entityTypeId'))
            ->select(
                'mdm_validation_rules.id',
                'mdm_fields.code as field_code',
                'mdm_validation_rules.type',
                'mdm_validation_rules.severity',
                'mdm_validation_rules.sort_order',
            );

        $this->addFilter('id', 'mdm_validation_rules.id');
        $this->addFilter('field_code', 'mdm_fields.code');
        $this->addFilter('type', 'mdm_validation_rules.type');
        $this->addFilter('severity', 'mdm_validation_rules.severity');
        $this->addFilter('sort_order', 'mdm_validation_rules.sort_order');

        return $queryBuilder;
    }

    /**
     * Prepare columns.
     */
    public function prepareColumns(): void
    {
        $this->addColumn([
            'index'      => 'field_code',
            'label'      => trans('masterdata::app.validation-rules.datagrid.field'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'type',
            'label'      => trans('masterdata::app.validation-rules.datagrid.type'),
            'type'       => 'dropdown',
            'searchable' => false,
            'filterable' => true,
            'sortable'   => true,
            'options'    => [
                'type'   => 'basic',
                'params' => [
                    'options' => array_map(
                        fn (array $option): array => ['label' => $option['label'], 'value' => $option['id']],
                        $this->ruleTypeRegistry->options(),
                    ),
                ],
            ],
            'closure' => fn ($row): string => e($this->ruleTypeRegistry->label((string) $row->type)),
        ]);

        $this->addColumn([
            'index'      => 'severity',
            'label'      => trans('masterdata::app.validation-rules.datagrid.severity'),
            'type'       => 'dropdown',
            'searchable' => false,
            'filterable' => true,
            'sortable'   => true,
            'options'    => [
                'type'   => 'basic',
                'params' => [
                    'options' => array_map(
                        fn (ValidationSeverity $severity): array => ['label' => trans('masterdata::app.validation-rules.severities.'.$severity->value), 'value' => $severity->value],
                        ValidationSeverity::cases(),
                    ),
                ],
            ],
            'closure' => fn ($row): string => e(ValidationSeverity::tryFrom((string) $row->severity) === null
                ? (string) $row->severity
                : trans('masterdata::app.validation-rules.severities.'.$row->severity)),
        ]);

        $this->addColumn([
            'index'      => 'sort_order',
            'label'      => trans('masterdata::app.validation-rules.datagrid.sort-order'),
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
            'title'  => trans('masterdata::app.validation-rules.datagrid.edit'),
            'method' => 'PUT',
            'url'    => fn ($row): string => route('admin.masterdata.validation_rules.update', ['entityTypeId' => $entityTypeId, 'id' => $row->id]),
        ]);

        $this->addAction([
            'index'  => 'delete',
            'icon'   => 'icon-delete',
            'title'  => trans('masterdata::app.validation-rules.datagrid.delete'),
            'method' => 'DELETE',
            'url'    => fn ($row): string => route('admin.masterdata.validation_rules.destroy', ['entityTypeId' => $entityTypeId, 'id' => $row->id]),
        ]);
    }
}
