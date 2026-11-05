<?php

namespace Webkul\MasterDataManagement\DataGrids;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;
use Webkul\MasterDataManagement\Enums\MatchCandidateStatus;
use Webkul\MasterDataManagement\Security\EntityTypePermissions;
use Webkul\MasterDataManagement\Security\RecordScope;
use Webkul\User\Models\Admin;

class MatchCandidateDataGrid extends DataGrid
{
    public function __construct(
        protected EntityTypePermissions $entityTypePermissions,
        protected RecordScope $recordScope,
    ) {}

    /**
     * Prepare query builder.
     *
     * Rows of entity types the admin may not view are left out, and on scoped types so are rows matched to a record
     * outside the admin's view grants.
     *
     * @return Builder
     */
    public function prepareQueryBuilder()
    {
        $admin = auth()->guard('admin')->user();

        $visible = $admin instanceof Admin ? $this->entityTypePermissions->visibleEntityTypeIds($admin) : [];

        $queryBuilder = DB::table('mdm_match_candidates')
            ->join('mdm_source_systems', 'mdm_source_systems.id', '=', 'mdm_match_candidates.source_system_id')
            ->join('mdm_entity_types', 'mdm_entity_types.id', '=', 'mdm_match_candidates.entity_type_id')
            ->leftJoin('mdm_records as matched_records', 'matched_records.id', '=', 'mdm_match_candidates.matched_record_id')
            ->when($visible !== null, fn (Builder $query) => $query->whereIn('mdm_match_candidates.entity_type_id', $visible))
            ->select(
                'mdm_match_candidates.id',
                'mdm_match_candidates.external_id',
                'mdm_match_candidates.label',
                'mdm_match_candidates.status',
                'mdm_match_candidates.top_score',
                'mdm_match_candidates.auto_matched',
                'mdm_match_candidates.created_at',
                'mdm_source_systems.code as system_code',
                'mdm_entity_types.code as entity_type_code',
            );

        if ($admin instanceof Admin) {
            $this->recordScope->applyToRows($queryBuilder, $admin, 'mdm_match_candidates.entity_type_id', 'matched_records.path', 'mdm_match_candidates.matched_record_id');
        }

        $this->addFilter('id', 'mdm_match_candidates.id');
        $this->addFilter('external_id', 'mdm_match_candidates.external_id');
        $this->addFilter('label', 'mdm_match_candidates.label');
        $this->addFilter('status', 'mdm_match_candidates.status');
        $this->addFilter('top_score', 'mdm_match_candidates.top_score');
        $this->addFilter('auto_matched', 'mdm_match_candidates.auto_matched');
        $this->addFilter('created_at', 'mdm_match_candidates.created_at');
        $this->addFilter('system_code', 'mdm_source_systems.code');
        $this->addFilter('entity_type_code', 'mdm_entity_types.code');

        return $queryBuilder;
    }

    /**
     * Prepare columns.
     */
    public function prepareColumns(): void
    {
        $this->addColumn([
            'index'      => 'system_code',
            'label'      => trans('masterdata::app.matching.datagrid.system'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'entity_type_code',
            'label'      => trans('masterdata::app.matching.datagrid.entity-type'),
            'type'       => 'string',
            'searchable' => false,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'external_id',
            'label'      => trans('masterdata::app.matching.datagrid.external-id'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'label',
            'label'      => trans('masterdata::app.matching.datagrid.label'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'top_score',
            'label'      => trans('masterdata::app.matching.datagrid.top-score'),
            'type'       => 'integer',
            'searchable' => false,
            'filterable' => false,
            'sortable'   => true,
            'closure'    => fn ($row): string => $row->top_score === null ? '' : e(round((float) $row->top_score * 100).' %'),
        ]);

        $this->addColumn([
            'index'      => 'status',
            'label'      => trans('masterdata::app.matching.datagrid.status'),
            'type'       => 'dropdown',
            'searchable' => false,
            'filterable' => true,
            'sortable'   => true,
            'options'    => [
                'type'   => 'basic',
                'params' => [
                    'options' => array_map(
                        fn (MatchCandidateStatus $status): array => [
                            'label' => trans('masterdata::app.matching.statuses.'.$status->value),
                            'value' => $status->value,
                        ],
                        MatchCandidateStatus::cases()
                    ),
                ],
            ],
            'closure' => fn ($row): string => e(trans('masterdata::app.matching.statuses.'.$row->status)),
        ]);

        $this->addColumn([
            'index'      => 'auto_matched',
            'label'      => trans('masterdata::app.matching.datagrid.decision'),
            'type'       => 'boolean',
            'searchable' => false,
            'filterable' => true,
            'sortable'   => true,
            'options'    => [
                'type'   => 'basic',
                'params' => [
                    'options' => [
                        ['label' => trans('masterdata::app.matching.datagrid.auto'), 'value' => 1],
                        ['label' => trans('masterdata::app.matching.datagrid.manual'), 'value' => 0],
                    ],
                ],
            ],
            'closure' => fn ($row): string => (bool) $row->auto_matched
                ? "<span class='label-info'>".e(trans('masterdata::app.matching.datagrid.auto')).'</span>'
                : e(trans('masterdata::app.matching.datagrid.manual')),
        ]);

        $this->addColumn([
            'index'      => 'created_at',
            'label'      => trans('masterdata::app.matching.datagrid.submitted-at'),
            'type'       => 'date_range',
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
            'index'  => 'review',
            'icon'   => 'icon-view',
            'title'  => trans('masterdata::app.matching.datagrid.review'),
            'method' => 'GET',
            'url'    => fn ($row): string => route('admin.masterdata.matching.show', $row->id),
        ]);
    }
}
