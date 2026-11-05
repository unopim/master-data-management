<?php

namespace Webkul\MasterDataManagement\DataGrids;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;
use Webkul\MasterDataManagement\Enums\QualityIssueStatus;
use Webkul\MasterDataManagement\Enums\QualitySeverity;
use Webkul\MasterDataManagement\Quality\IssuePresenter;
use Webkul\MasterDataManagement\Quality\QualityEvaluator;
use Webkul\MasterDataManagement\Quality\Rules\QualityRule;
use Webkul\MasterDataManagement\Security\EntityTypePermissions;
use Webkul\User\Models\Admin;

class QualityIssueDataGrid extends DataGrid
{
    public function __construct(
        protected EntityTypePermissions $entityTypePermissions,
        protected QualityEvaluator $qualityEvaluator,
        protected IssuePresenter $issuePresenter,
    ) {}

    /**
     * Prepare query builder.
     *
     * Issues of entity types the admin may not view are left out.
     *
     * @return Builder
     */
    public function prepareQueryBuilder()
    {
        $admin = auth()->guard('admin')->user();

        $visible = $admin instanceof Admin ? $this->entityTypePermissions->visibleEntityTypeIds($admin) : [];

        $queryBuilder = DB::table('mdm_dq_issues')
            ->join('mdm_records', 'mdm_records.id', '=', 'mdm_dq_issues.record_id')
            ->join('mdm_entity_types', 'mdm_entity_types.id', '=', 'mdm_dq_issues.entity_type_id')
            ->when($visible !== null, fn (Builder $query) => $query->whereIn('mdm_dq_issues.entity_type_id', $visible))
            ->select(
                'mdm_dq_issues.id',
                'mdm_dq_issues.rule',
                'mdm_dq_issues.rule as rule_code',
                'mdm_dq_issues.severity',
                'mdm_dq_issues.status',
                'mdm_dq_issues.status as status_code',
                'mdm_dq_issues.details',
                'mdm_dq_issues.detected_at',
                'mdm_records.code as record_code',
                'mdm_records.uuid as record_uuid',
                'mdm_records.label as record_label',
                'mdm_entity_types.code as entity_type_code',
            );

        $this->addFilter('id', 'mdm_dq_issues.id');
        $this->addFilter('entity_type_code', 'mdm_entity_types.code');
        $this->addFilter('record_code', 'mdm_records.code');
        $this->addFilter('rule', 'mdm_dq_issues.rule');
        $this->addFilter('severity', 'mdm_dq_issues.severity');
        $this->addFilter('status', 'mdm_dq_issues.status');
        $this->addFilter('detected_at', 'mdm_dq_issues.detected_at');

        return $queryBuilder;
    }

    /**
     * Prepare columns.
     *
     * The message reads `rule_code`, since the rule column's closure replaces `rule` with its label first; the
     * actions read `status_code` for the same reason.
     */
    public function prepareColumns(): void
    {
        $this->addColumn([
            'index'      => 'entity_type_code',
            'label'      => trans('masterdata::app.quality.datagrid.entity-type'),
            'type'       => 'string',
            'searchable' => false,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'record_code',
            'label'      => trans('masterdata::app.quality.datagrid.record'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
            'closure'    => fn ($row): string => '<a class="text-primary-600 hover:underline" href="'.e(route('admin.masterdata.records.edit', $row->record_uuid)).'">'
                .e(trim($row->record_code.' '.($row->record_label ? '· '.$row->record_label : ''))).'</a>',
        ]);

        $this->addColumn([
            'index'      => 'rule',
            'label'      => trans('masterdata::app.quality.datagrid.rule'),
            'type'       => 'dropdown',
            'searchable' => false,
            'filterable' => true,
            'sortable'   => true,
            'options'    => [
                'type'   => 'basic',
                'params' => [
                    'options' => array_map(
                        fn (QualityRule $rule): array => ['label' => $this->issuePresenter->ruleLabel($rule->code()), 'value' => $rule->code()],
                        $this->qualityEvaluator->rules(),
                    ),
                ],
            ],
            'closure' => fn ($row): string => e($this->issuePresenter->ruleLabel($row->rule)),
        ]);

        $this->addColumn([
            'index'      => 'details',
            'label'      => trans('masterdata::app.quality.datagrid.message'),
            'type'       => 'string',
            'searchable' => false,
            'filterable' => false,
            'sortable'   => false,
            'closure'    => fn ($row): string => e($this->issuePresenter->message($row->rule_code, $row->details)),
        ]);

        $this->addColumn([
            'index'      => 'severity',
            'label'      => trans('masterdata::app.quality.datagrid.severity'),
            'type'       => 'dropdown',
            'searchable' => false,
            'filterable' => true,
            'sortable'   => true,
            'options'    => [
                'type'   => 'basic',
                'params' => [
                    'options' => array_map(
                        fn (QualitySeverity $severity): array => [
                            'label' => trans('masterdata::app.quality.severities.'.$severity->value),
                            'value' => $severity->value,
                        ],
                        QualitySeverity::cases()
                    ),
                ],
            ],
            'closure' => fn ($row): string => e(trans('masterdata::app.quality.severities.'.$row->severity)),
        ]);

        $this->addColumn([
            'index'      => 'status',
            'label'      => trans('masterdata::app.quality.datagrid.status'),
            'type'       => 'dropdown',
            'searchable' => false,
            'filterable' => true,
            'sortable'   => true,
            'options'    => [
                'type'   => 'basic',
                'params' => [
                    'options' => array_map(
                        fn (QualityIssueStatus $status): array => [
                            'label' => trans('masterdata::app.quality.statuses.'.$status->value),
                            'value' => $status->value,
                        ],
                        QualityIssueStatus::cases()
                    ),
                ],
            ],
            'closure' => fn ($row): string => e(trans('masterdata::app.quality.statuses.'.$row->status)),
        ]);

        $this->addColumn([
            'index'      => 'detected_at',
            'label'      => trans('masterdata::app.quality.datagrid.detected-at'),
            'type'       => 'date_range',
            'searchable' => false,
            'filterable' => true,
            'sortable'   => true,
        ]);
    }

    /**
     * Prepare actions: open the record, and resolve or ignore an open issue.
     */
    public function prepareActions(): void
    {
        $this->addAction([
            'index'  => 'view',
            'icon'   => 'icon-view',
            'title'  => trans('masterdata::app.quality.datagrid.view-record'),
            'method' => 'GET',
            'url'    => fn ($row): string => route('admin.masterdata.records.edit', $row->record_uuid),
        ]);

        if (! bouncer()->hasPermission('masterdata.quality.resolve')) {
            return;
        }

        $open = fn ($row): bool => $row->status_code === QualityIssueStatus::Open->value;

        $this->addAction([
            'index'     => 'resolve',
            'icon'      => 'icon-done',
            'title'     => trans('masterdata::app.quality.actions.resolve'),
            'method'    => 'POST',
            'url'       => fn ($row): string => route('admin.masterdata.quality.issues.resolve', $row->id),
            'condition' => $open,
        ]);

        $this->addAction([
            'index'     => 'ignore',
            'icon'      => 'icon-cancel',
            'title'     => trans('masterdata::app.quality.actions.ignore'),
            'method'    => 'POST',
            'url'       => fn ($row): string => route('admin.masterdata.quality.issues.ignore', $row->id),
            'condition' => $open,
        ]);
    }
}
