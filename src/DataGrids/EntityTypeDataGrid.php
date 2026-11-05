<?php

namespace Webkul\MasterDataManagement\DataGrids;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;
use Webkul\MasterDataManagement\Enums\EntityTypeStatus;
use Webkul\MasterDataManagement\Enums\MasteringStyle;
use Webkul\MasterDataManagement\Models\Icon;
use Webkul\MasterDataManagement\Repositories\EntityTypeRepository;

class EntityTypeDataGrid extends DataGrid
{
    protected $sortColumn = 'sort_order';

    protected $sortOrder = 'asc';

    /**
     * Names of the allowed parent types referenced by the current page, keyed by id.
     *
     * @var array<int, string>
     */
    protected array $parentNames = [];

    /**
     * Record totals of the current page keyed by entity type id, read before the cells are formatted.
     *
     * @var array<int, int>
     */
    protected array $recordCounts = [];

    public function __construct(protected EntityTypeRepository $entityTypeRepository) {}

    /**
     * Prepare query builder.
     *
     * The record and active field totals are correlated count subqueries, so only the rows of the requested page are
     * counted and the pagination total query drops them.
     *
     * @return Builder
     */
    public function prepareQueryBuilder()
    {
        $queryBuilder = DB::table('mdm_entity_types')
            ->leftJoin('mdm_entity_type_translations as requested_translation', function ($leftJoin): void {
                $leftJoin->on('requested_translation.entity_type_id', '=', 'mdm_entity_types.id')
                    ->where('requested_translation.locale', core()->getRequestedLocaleCode());
            })
            ->leftJoin('mdm_icons', 'mdm_icons.id', '=', 'mdm_entity_types.icon_id')
            ->select(
                'mdm_entity_types.id',
                'mdm_entity_types.code',
                'mdm_entity_types.icon',
                'mdm_entity_types.icon_id',
                'mdm_icons.path as icon_path',
                'mdm_entity_types.mastering_style',
                'mdm_entity_types.status',
                'mdm_entity_types.schema_version',
                'mdm_entity_types.sort_order',
                'mdm_entity_types.allowed_parent_type_ids',
                'mdm_entity_types.updated_at',
                'requested_translation.name as name'
            )
            ->selectSub(fn (Builder $query): Builder => $query->from('mdm_records')
                ->selectRaw('count(*)')
                ->whereColumn('mdm_records.entity_type_id', 'mdm_entity_types.id'), 'records_count')
            ->selectSub(fn (Builder $query): Builder => $query->from('mdm_fields')
                ->selectRaw('count(*)')
                ->whereColumn('mdm_fields.entity_type_id', 'mdm_entity_types.id')
                ->where('mdm_fields.is_archived', false), 'fields_count');

        $this->addFilter('id', 'mdm_entity_types.id');
        $this->addFilter('code', 'mdm_entity_types.code');
        $this->addFilter('mastering_style', 'mdm_entity_types.mastering_style');
        $this->addFilter('status', 'mdm_entity_types.status');
        $this->addFilter('schema_version', 'mdm_entity_types.schema_version');
        $this->addFilter('sort_order', 'mdm_entity_types.sort_order');
        $this->addFilter('updated_at', 'mdm_entity_types.updated_at');
        $this->addFilter('name', 'requested_translation.name');

        return $queryBuilder;
    }

    /**
     * Prepare columns.
     */
    public function prepareColumns(): void
    {
        $this->addColumn([
            'index'      => 'id',
            'label'      => trans('masterdata::app.entity-types.datagrid.id'),
            'type'       => 'integer',
            'searchable' => false,
            'filterable' => false,
            'sortable'   => true,
            'visible'    => false,
        ]);

        $this->addColumn([
            'index'      => 'name',
            'label'      => trans('masterdata::app.entity-types.datagrid.name'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
            'closure'    => $this->nameCell(...),
        ]);

        $this->addColumn([
            'index'      => 'code',
            'label'      => trans('masterdata::app.entity-types.datagrid.code'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
            'closure'    => fn ($row): string => '<span class="font-mono text-xs">'.e($row->code).'</span>',
        ]);

        $this->addColumn([
            'index'      => 'mastering_style',
            'label'      => trans('masterdata::app.entity-types.datagrid.mastering-style'),
            'type'       => 'dropdown',
            'searchable' => false,
            'filterable' => true,
            'sortable'   => true,
            'options'    => [
                'type'   => 'basic',
                'params' => [
                    'options' => array_map(
                        fn (MasteringStyle $style): array => [
                            'label' => trans('masterdata::app.entity-types.mastering-styles.'.$style->value),
                            'value' => $style->value,
                        ],
                        MasteringStyle::cases()
                    ),
                ],
            ],
            'closure' => fn ($row): string => e(trans('masterdata::app.entity-types.mastering-styles.'.$row->mastering_style)),
        ]);

        $this->addColumn([
            'index'      => 'allowed_parent_type_ids',
            'label'      => trans('masterdata::app.entity-types.datagrid.allowed-parents'),
            'type'       => 'string',
            'searchable' => false,
            'filterable' => false,
            'sortable'   => false,
            'closure'    => fn ($row): string => e(implode(', ', array_filter(array_map(
                fn (int $id): ?string => $this->parentNames[$id] ?? null,
                $this->parentIds($row->allowed_parent_type_ids)
            )))),
        ]);

        $this->addColumn([
            'index'      => 'status',
            'label'      => trans('masterdata::app.entity-types.datagrid.status'),
            'type'       => 'dropdown',
            'searchable' => false,
            'filterable' => true,
            'sortable'   => true,
            'options'    => [
                'type'   => 'basic',
                'params' => [
                    'options' => array_map(
                        fn (EntityTypeStatus $status): array => [
                            'label' => trans('masterdata::app.entity-types.statuses.'.$status->value),
                            'value' => $status->value,
                        ],
                        EntityTypeStatus::cases()
                    ),
                ],
            ],
            'closure' => fn ($row): string => $this->statusBadge((string) $row->status),
        ]);

        $this->addColumn([
            'index'      => 'schema_version',
            'label'      => trans('masterdata::app.entity-types.datagrid.schema-version'),
            'type'       => 'integer',
            'searchable' => false,
            'filterable' => false,
            'sortable'   => true,
            'closure'    => fn ($row): string => e(trans('masterdata::app.entity-types.datagrid.schema-version-value', ['version' => (int) $row->schema_version])),
        ]);

        $this->addColumn([
            'index'      => 'sort_order',
            'label'      => trans('masterdata::app.entity-types.datagrid.sort-order'),
            'type'       => 'integer',
            'searchable' => false,
            'filterable' => false,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'fields_count',
            'label'      => trans('masterdata::app.entity-types.datagrid.fields-count'),
            'type'       => 'integer',
            'searchable' => false,
            'filterable' => false,
            'sortable'   => true,
            'closure'    => fn ($row): string => '<span class="block text-right tabular-nums">'.(int) $row->fields_count.'</span>',
        ]);

        $this->addColumn([
            'index'      => 'records_count',
            'label'      => trans('masterdata::app.entity-types.datagrid.records-count'),
            'type'       => 'integer',
            'searchable' => false,
            'filterable' => false,
            'sortable'   => true,
            'closure'    => fn ($row): string => '<span class="block text-right tabular-nums">'.(int) $row->records_count.'</span>',
        ]);

        $this->addColumn([
            'index'      => 'updated_at',
            'label'      => trans('masterdata::app.entity-types.datagrid.updated-at'),
            'type'       => 'date_range',
            'searchable' => false,
            'filterable' => true,
            'sortable'   => true,
        ]);
    }

    /**
     * Sort by the requested column, or by default in display order: sort order, then code.
     *
     * @return Builder
     */
    public function processRequestedSorting(array $requestedSort)
    {
        $queryBuilder = parent::processRequestedSorting($requestedSort);

        return isset($requestedSort['column']) ? $queryBuilder : $queryBuilder->orderBy('mdm_entity_types.code');
    }

    /**
     * Prepare actions.
     *
     * Delete is offered only for types without records, matching the guard of the destroy endpoint.
     */
    public function prepareActions(): void
    {
        if (bouncer()->hasPermission('masterdata.entity_types.edit')) {
            $this->addAction([
                'index'  => 'edit',
                'icon'   => 'icon-edit',
                'title'  => trans('masterdata::app.entity-types.datagrid.edit'),
                'method' => 'GET',
                'url'    => fn ($row): string => route('admin.masterdata.entity_types.edit', $row->id),
            ]);
        }

        if (bouncer()->hasPermission('masterdata.entity_types.delete')) {
            $this->addAction([
                'index'     => 'delete',
                'icon'      => 'icon-delete',
                'title'     => trans('masterdata::app.entity-types.datagrid.delete'),
                'method'    => 'DELETE',
                'url'       => fn ($row): string => route('admin.masterdata.entity_types.destroy', $row->id),
                'condition' => fn ($row): bool => ($this->recordCounts[(int) $row->id] ?? 0) === 0,
            ]);
        }
    }

    /**
     * Prepare mass actions.
     */
    public function prepareMassActions(): void
    {
        if (bouncer()->hasPermission('masterdata.entity_types.delete')) {
            $this->addMassAction([
                'icon'    => 'icon-delete',
                'title'   => trans('masterdata::app.entity-types.datagrid.delete'),
                'method'  => 'POST',
                'url'     => route('admin.masterdata.entity_types.mass_delete'),
                'options' => ['actionType' => 'delete'],
            ]);
        }
    }

    /**
     * Format the page of rows after resolving the names of every allowed parent type it references in one lookup.
     *
     * @return array<string, mixed>
     */
    public function formatData(): array
    {
        $parentIds = [];

        foreach ($this->paginator->items() as $row) {
            array_push($parentIds, ...$this->parentIds($row->allowed_parent_type_ids ?? null));

            $this->recordCounts[(int) $row->id] = (int) $row->records_count;
        }

        $this->parentNames = $this->entityTypeRepository->namesByIds(array_values(array_unique($parentIds)));

        return parent::formatData();
    }

    /**
     * Decode the allowed parent type ids stored as JSON on the row.
     *
     * @return list<int>
     */
    protected function parentIds(mixed $value): array
    {
        $ids = is_string($value) ? json_decode($value, true) : $value;

        return is_array($ids) ? array_values(array_map(intval(...), array_filter($ids, is_numeric(...)))) : [];
    }

    /**
     * Render the entity type name after its library or uploaded icon.
     */
    protected function nameCell(object $row): string
    {
        $name = '<span class="font-semibold text-gray-800 dark:text-white">'.e($row->name ?: $row->code).'</span>';

        if ($row->icon_id && $row->icon_path) {
            $icon = view('masterdata::icons._mask', [
                'url'    => Icon::urlFor((int) $row->icon_id, $row->icon_path),
                'vector' => Icon::isVectorPath((string) $row->icon_path),
                'class'  => 'w-6 h-6 text-primary-600 dark:text-primary-400',
            ])->render();
        } elseif (in_array($row->icon, config('masterdata.entity_type_icons', []), true)) {
            $icon = '<span class="'.e($row->icon).' text-xl shrink-0 text-primary-600 dark:text-primary-400" aria-hidden="true"></span>';
        } else {
            return $name;
        }

        return '<div class="flex items-center gap-2.5">'.$icon.$name.'</div>';
    }

    /**
     * Render the status as a badge.
     */
    protected function statusBadge(string $status): string
    {
        $class = EntityTypeStatus::tryFrom($status)?->badgeClass() ?? EntityTypeStatus::Archived->badgeClass();

        return '<span class="'.$class.'">'.e(trans('masterdata::app.entity-types.statuses.'.$status)).'</span>';
    }
}
