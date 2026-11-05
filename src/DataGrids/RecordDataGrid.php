<?php

namespace Webkul\MasterDataManagement\DataGrids;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Webkul\DataGrid\DataGrid;
use Webkul\MasterDataManagement\Enums\RecordStatus;
use Webkul\MasterDataManagement\Enums\XrefStatus;
use Webkul\MasterDataManagement\Http\Requests\RecordParentRequest;
use Webkul\MasterDataManagement\Models\EntityType;
use Webkul\MasterDataManagement\Models\Field;
use Webkul\MasterDataManagement\Quality\Completeness;
use Webkul\MasterDataManagement\Repositories\EntityTypeRepository;
use Webkul\MasterDataManagement\Repositories\FieldRepository;
use Webkul\MasterDataManagement\Repositories\RecordRepository;
use Webkul\MasterDataManagement\Security\EntityTypePermissions;
use Webkul\MasterDataManagement\Security\RecordPolicy;
use Webkul\MasterDataManagement\Services\RecordIndexer;
use Webkul\MasterDataManagement\Services\RecordPresenter;
use Webkul\User\Models\Admin;

class RecordDataGrid extends DataGrid
{
    /**
     * The prefix of the column index every searchable field gets.
     */
    protected const string FIELD_PREFIX = 'field_';

    /**
     * The most ancestors the parent column names before it elides the rest.
     */
    protected const int TRAIL_DEPTH = 3;

    /**
     * The most parents one parent filter accepts.
     */
    protected const int PARENT_FILTER_LIMIT = 20;

    /**
     * @var Collection<int, Field>|null
     */
    protected ?Collection $searchableFields = null;

    /**
     * The decoded values document of the row last rendered, so each field cell does not decode it again.
     *
     * @var array{0: int|string|null, 1: array<string, mixed>}
     */
    protected array $decodedValues = [null, []];

    /**
     * The visible ancestors of the rows on the page, keyed by record id.
     *
     * @var array<int, array{uuid: string, code: string, label: string}>
     */
    protected array $trailNodes = [];

    /**
     * The direct children of the grid's type under each row on the page, keyed by record id.
     *
     * @var array<int, int>
     */
    protected array $childCounts = [];

    /**
     * The visible hierarchical entity types whose records may sit directly under the grid's records.
     *
     * @var Collection<int, EntityType>|null
     */
    protected ?Collection $childTypes = null;

    public function __construct(
        protected RecordRepository $recordRepository,
        protected FieldRepository $fieldRepository,
        protected RecordPresenter $recordPresenter,
        protected EntityTypeRepository $entityTypeRepository,
        protected EntityTypePermissions $entityTypePermissions,
    ) {}

    /**
     * Prepare query builder.
     *
     * The rows come from the scoped repository query so the grid never shows a record the admin may not see.
     *
     * @return Builder
     */
    public function prepareQueryBuilder()
    {
        $queryBuilder = $this->recordRepository->scopedQuery($this->entityType()->id)
            ->toBase()
            ->select(
                'mdm_records.id',
                'mdm_records.uuid',
                'mdm_records.code',
                'mdm_records.label',
                'mdm_records.status',
                'mdm_records.status as lifecycle_status',
                'mdm_records.revision',
                'mdm_records.completeness',
                'mdm_records.updated_at',
                'mdm_records.values',
                'mdm_records.path',
            )
            ->selectRaw($this->externalIdsExpression().' as external_ids');

        foreach ($this->searchableFields() as $field) {
            $queryBuilder->selectSub($this->fieldValueSubQuery($field), self::FIELD_PREFIX.$field->code);
        }

        $this->addFilter('id', 'mdm_records.id');
        $this->addFilter('code', 'mdm_records.code');
        $this->addFilter('label', 'mdm_records.label');
        $this->addFilter('status', 'mdm_records.status');
        $this->addFilter('completeness', DB::raw($this->completenessBandExpression()));
        $this->addFilter('updated_at', 'mdm_records.updated_at');

        return $queryBuilder;
    }

    /**
     * Prepare columns.
     */
    public function prepareColumns(): void
    {
        $this->addColumn([
            'index'      => 'code',
            'label'      => trans('masterdata::app.records.datagrid.code'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'label',
            'label'      => trans('masterdata::app.records.datagrid.label'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);

        if ($this->entityType()->is_hierarchical) {
            $this->addHierarchyColumns();
        }

        $this->addColumn([
            'index'      => 'status',
            'label'      => trans('masterdata::app.records.datagrid.status'),
            'type'       => 'dropdown',
            'searchable' => false,
            'filterable' => true,
            'sortable'   => true,
            'options'    => [
                'type'   => 'basic',
                'params' => [
                    'options' => array_map(
                        fn (RecordStatus $status): array => [
                            'label' => trans('masterdata::app.records.statuses.'.$status->value),
                            'value' => $status->value,
                        ],
                        RecordStatus::cases()
                    ),
                ],
            ],
            'closure' => fn ($row): string => e(trans('masterdata::app.records.statuses.'.$row->status)),
        ]);

        $this->addColumn([
            'index'      => 'external_ids',
            'label'      => trans('masterdata::app.records.datagrid.external-ids'),
            'type'       => 'string',
            'searchable' => false,
            'filterable' => false,
            'sortable'   => false,
            'closure'    => fn ($row): string => e((string) $row->external_ids),
        ]);

        $this->addColumn([
            'index'      => 'completeness',
            'label'      => trans('masterdata::app.records.datagrid.completeness'),
            'type'       => 'dropdown',
            'searchable' => false,
            'filterable' => true,
            'sortable'   => true,
            'options'    => [
                'type'   => 'basic',
                'params' => [
                    'options' => array_map(
                        fn (string $band): array => [
                            'label' => trans('masterdata::app.quality.completeness.bands.'.$band),
                            'value' => $band,
                        ],
                        Completeness::BANDS,
                    ),
                ],
            ],
            'closure' => fn ($row): string => $this->completenessPill($row->completeness),
        ]);

        foreach ($this->searchableFields() as $field) {
            $this->addFieldColumn($field);
        }

        $this->addColumn([
            'index'      => 'updated_at',
            'label'      => trans('masterdata::app.records.datagrid.updated-at'),
            'type'       => 'date_range',
            'searchable' => false,
            'filterable' => true,
            'sortable'   => true,
        ]);
    }

    /**
     * Prepare actions.
     *
     * Every row shares the grid's entity type, so each ability is checked once for the type. The first action opens
     * the record, as an editor when the admin may update it and read-only otherwise. Lifecycle conditions read
     * `lifecycle_status`, since the status column closure has already replaced `status` with its label.
     */
    public function prepareActions(): void
    {
        $subject = RecordPolicy::subjectForEntityType($this->entityType()->id);

        if (Gate::allows('update', $subject)) {
            $this->addAction([
                'index'  => 'edit',
                'icon'   => 'icon-edit',
                'title'  => trans('masterdata::app.records.datagrid.edit'),
                'method' => 'GET',
                'url'    => fn ($row): string => route('admin.masterdata.records.edit', $row->uuid),
            ]);
        } elseif (Gate::allows('view', $subject)) {
            $this->addAction([
                'index'  => 'view',
                'icon'   => 'icon-view',
                'title'  => trans('masterdata::app.records.datagrid.view'),
                'method' => 'GET',
                'url'    => fn ($row): string => route('admin.masterdata.records.edit', $row->uuid),
            ]);
        }

        if (Gate::allows('delete', $subject)) {
            $this->addAction([
                'index'     => 'retire',
                'icon'      => 'icon-delete',
                'title'     => trans('masterdata::app.records.datagrid.retire'),
                'method'    => 'POST',
                'url'       => fn ($row): string => route('admin.masterdata.records.retire', ['uuid' => $row->uuid, 'revision' => $row->revision]),
                'condition' => fn ($row): bool => in_array($row->lifecycle_status, [RecordStatus::Draft->value, RecordStatus::Active->value], true),
            ]);

            $this->addAction([
                'index'     => 'reactivate',
                'icon'      => 'icon-play',
                'title'     => trans('masterdata::app.records.datagrid.reactivate'),
                'method'    => 'POST',
                'url'       => fn ($row): string => route('admin.masterdata.records.reactivate', ['uuid' => $row->uuid, 'revision' => $row->revision]),
                'condition' => fn ($row): bool => $row->lifecycle_status === RecordStatus::Retired->value,
            ]);
        }

        if (Gate::allows('erase', $subject)) {
            $this->addAction([
                'index'     => 'erase',
                'icon'      => 'icon-cancel',
                'title'     => trans('masterdata::app.records.datagrid.erase'),
                'method'    => 'POST',
                'url'       => fn ($row): string => route('admin.masterdata.records.erase', ['uuid' => $row->uuid, 'revision' => $row->revision]),
                'condition' => fn ($row): bool => $row->lifecycle_status === RecordStatus::Retired->value,
            ]);
        }
    }

    /**
     * Offer retiring the selected records when the admin may retire records of the type.
     */
    public function prepareMassActions(): void
    {
        if (! Gate::allows('delete', RecordPolicy::subjectForEntityType($this->entityType()->id))) {
            return;
        }

        $this->addMassAction([
            'icon'    => 'icon-delete',
            'title'   => trans('masterdata::app.records.datagrid.retire'),
            'method'  => 'POST',
            'url'     => route('admin.masterdata.records.mass_retire', $this->entityType()->code),
            'options' => ['actionType' => 'delete'],
        ]);
    }

    /**
     * Format the page of rows, dropping the values document the field cells were rendered from.
     *
     * @return array<string, mixed>
     */
    public function formatData(): array
    {
        if ($this->entityType()->is_hierarchical) {
            $this->loadHierarchy($this->paginator->items());
        }

        $data = parent::formatData();

        foreach ($data['records'] as $record) {
            unset($record->values, $record->path);
        }

        return $data;
    }

    /**
     * Apply the requested filters, the searchable field columns through the repository's field filter.
     *
     * @param  array<string, mixed>  $requestedFilters
     * @return Builder
     *
     * @throws ValidationException
     */
    public function processRequestedFilters(array $requestedFilters)
    {
        $fieldFilters = [];

        $parentFilter = $requestedFilters['parent'] ?? null;

        unset($requestedFilters['parent']);

        foreach ($requestedFilters as $index => $values) {
            if (is_string($index) && str_starts_with($index, self::FIELD_PREFIX) && is_array($values)) {
                $fieldFilters[substr($index, strlen(self::FIELD_PREFIX))] = $values;

                unset($requestedFilters[$index]);
            }
        }

        $queryBuilder = parent::processRequestedFilters($requestedFilters);

        foreach ($fieldFilters as $code => $values) {
            $field = $this->searchableFields()->firstWhere('code', $code);

            if ($field instanceof Field) {
                $this->applyFieldFilter($field, $values);
            }
        }

        if (is_array($parentFilter) && $parentFilter !== [] && $this->entityType()->is_hierarchical) {
            $this->applyParentFilter($parentFilter);
        }

        return $queryBuilder;
    }

    /**
     * Add the parent trail column, filterable by parent, and the child count column when any type nests under it.
     */
    protected function addHierarchyColumns(): void
    {
        $this->addColumn([
            'index'      => 'parent',
            'label'      => trans('masterdata::app.records.datagrid.parent'),
            'type'       => 'dropdown',
            'searchable' => false,
            'filterable' => true,
            'sortable'   => false,
            'options'    => [
                'type'     => 'searchable',
                'route'    => route('admin.masterdata.records.options', $this->entityType()->code),
                'track_by' => 'id',
                'label_by' => 'label',
                'params'   => [],
            ],
            'closure'    => $this->parentTrail(...),
        ]);

        if ($this->childTypes()->isEmpty()) {
            return;
        }

        $this->addColumn([
            'index'      => 'children',
            'label'      => trans('masterdata::app.records.datagrid.children'),
            'type'       => 'integer',
            'searchable' => false,
            'filterable' => false,
            'sortable'   => false,
            'closure'    => $this->childrenLink(...),
        ]);
    }

    /**
     * Load the visible ancestors and the child counts of the page's rows, one query each.
     *
     * @param  array<int, object>  $rows
     */
    protected function loadHierarchy(array $rows): void
    {
        $ids = array_map(fn (object $row): int => (int) $row->id, $rows);

        $ancestorIds = array_values(array_unique(array_merge([], ...array_map($this->ancestorIds(...), $rows))));

        $this->trailNodes = $this->recordRepository->trailNodes($ancestorIds);

        $this->childCounts = $this->recordRepository->childCounts(
            $ids,
            $this->childTypes()->map(fn (EntityType $type): int => (int) $type->id)->all(),
        );
    }

    /**
     * The visible hierarchical entity types whose records may sit directly under the grid's records.
     *
     * @return Collection<int, EntityType>
     */
    protected function childTypes(): Collection
    {
        if ($this->childTypes !== null) {
            return $this->childTypes;
        }

        $admin = auth()->guard('admin')->user();

        $visible = $this->entityTypeRepository->listActive($admin instanceof Admin ? $this->entityTypePermissions->visibleEntityTypeIds($admin) : []);

        return $this->childTypes = $this->recordPresenter->childTypes((int) $this->entityType()->id, $visible->toBase());
    }

    /**
     * The ids of the row's ancestors from the root down, read from its materialised path.
     *
     * @return list<int>
     */
    protected function ancestorIds(object $row): array
    {
        $ids = array_map(intval(...), array_filter(explode('/', (string) ($row->path ?? '')), is_numeric(...)));

        return array_values(array_filter($ids, fn (int $id): bool => $id !== (int) $row->id));
    }

    /**
     * Render the row's nearest ancestors as links to their record pages, or the top level marker for a root.
     */
    protected function parentTrail(object $row): string
    {
        $ids = $this->ancestorIds($row);

        if ($ids === []) {
            return '<span class="text-gray-600 dark:text-gray-300">'.e(trans('masterdata::app.records.datagrid.top-level')).'</span>';
        }

        $parts = count($ids) > self::TRAIL_DEPTH ? ['<span aria-hidden="true">&hellip;</span>'] : [];

        foreach (array_slice($ids, -self::TRAIL_DEPTH) as $id) {
            $node = $this->trailNodes[$id] ?? null;

            $parts[] = $node === null
                ? '<span aria-hidden="true">&hellip;</span>'
                : '<a href="'.e(route('admin.masterdata.records.edit', $node['uuid'])).'" class="text-primary-600 hover:text-primary-700 dark:text-primary-400">'.e($node['label'] !== '' ? $node['label'] : $node['code']).'</a>';
        }

        $separator = '<span class="text-gray-400 dark:text-gray-500" aria-hidden="true">'.e(trans('masterdata::app.hierarchy.trail-separator')).'</span>';

        return '<span class="flex flex-wrap items-center gap-1">'.implode($separator, $parts).'</span>';
    }

    /**
     * Render the row's child count, linked to the grid of those children when one type holds them all and to the
     * record page, which lists children of every type, otherwise.
     */
    protected function childrenLink(object $row): string
    {
        $count = $this->childCounts[(int) $row->id] ?? 0;

        if ($count === 0) {
            return '<span class="text-gray-600 dark:text-gray-300">0</span>';
        }

        $childType = $this->childTypes()->count() === 1 ? $this->childTypes()->first() : null;

        $url = $childType instanceof EntityType
            ? $this->recordPresenter->childrenUrl($childType, (string) $row->uuid)
            : route('admin.masterdata.records.edit', $row->uuid);

        return '<a href="'.e($url).'" class="text-primary-600 hover:text-primary-700 dark:text-primary-400" title="'.e(trans('masterdata::app.records.datagrid.view-children', ['count' => $count])).'">'.$count.'</a>';
    }

    /**
     * Constrain the grid to the direct children of the requested parents the admin may see, or to the top level
     * for the root sentinel; parents that resolve to nothing match no rows.
     *
     * @param  array<array-key, mixed>  $values
     *
     * @throws ValidationException
     */
    protected function applyParentFilter(array $values): void
    {
        $values = array_values(array_unique(array_filter($values, is_string(...))));

        if ($values === [] || count($values) > self::PARENT_FILTER_LIMIT) {
            throw ValidationException::withMessages([
                'filters' => trans('masterdata::app.records.datagrid.invalid-filter', ['field' => trans('masterdata::app.records.datagrid.parent')]),
            ]);
        }

        $root = in_array(RecordParentRequest::ROOT, $values, true);

        $parentIds = $this->recordRepository->visibleIdsByUuids(array_values(array_diff($values, [RecordParentRequest::ROOT])));

        $this->queryBuilder->where(fn (Builder $group) => $group
            ->whereIn('mdm_records.parent_id', $parentIds)
            ->when($root, fn (Builder $query) => $query->orWhereNull('mdm_records.parent_id')));
    }

    /**
     * The active searchable fields of the grid's entity type, in display order.
     *
     * @return Collection<int, Field>
     */
    protected function searchableFields(): Collection
    {
        return $this->searchableFields ??= $this->fieldRepository->searchableFor($this->entityType()->id)->toBase();
    }

    /**
     * Add the column that shows and filters one searchable field.
     */
    protected function addFieldColumn(Field $field): void
    {
        $kind = $this->fieldKind($field);

        $this->addColumn([
            'index'          => self::FIELD_PREFIX.$field->code,
            'label'          => $field->label ?: $field->code,
            'type'           => match ($kind) {
                'numeric' => 'integer',
                'date'    => 'date_range',
                default   => 'string',
            },
            'searchable'     => false,
            'filterable'     => true,
            'sortable'       => true,
            'default_filter' => false,
            'closure'        => fn ($row): string => e($kind === 'text'
                ? $this->storedDisplayValue($row, $field)
                : $this->displayValue($kind, $row->{self::FIELD_PREFIX.$field->code} ?? null)),
        ]);
    }

    /**
     * The kind of index value the field holds: text, numeric or date.
     */
    protected function fieldKind(Field $field): string
    {
        return match ($field->type) {
            'integer', 'decimal', 'boolean', 'measurement' => 'numeric',
            'date', 'datetime'                             => 'date',
            default                                        => 'text',
        };
    }

    /**
     * Build the correlated sub-select that reads one indexed value of a record for the field.
     *
     * A per-locale field holds one row per locale, so the first locale by code stands for the record.
     */
    protected function fieldValueSubQuery(Field $field): Builder
    {
        $table = RecordIndexer::TABLE;

        $column = match ($this->fieldKind($field)) {
            'numeric' => 'value_numeric',
            'date'    => 'value_date',
            default   => 'value_normalized',
        };

        return DB::table($table)
            ->select($table.'.'.$column)
            ->whereColumn($table.'.record_id', 'mdm_records.id')
            ->where($table.'.field_code', $field->code)
            ->orderBy($table.'.locale')
            ->limit(1);
    }

    /**
     * Format an indexed value for the cell: decimals lose their trailing zeros.
     */
    protected function displayValue(string $kind, mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        if ($kind === 'numeric' && is_numeric($value)) {
            return (string) (float) $value;
        }

        return (string) $value;
    }

    /**
     * The field's value as stored on the record, not its normalised index form, so the cell keeps the original case
     * and spelling; a per-locale field shows the first locale by code, like the sort column.
     */
    protected function storedDisplayValue(object $row, Field $field): string
    {
        if ($this->decodedValues[0] !== $row->id) {
            $values = is_string($row->values ?? null) ? json_decode($row->values, true) : null;

            $this->decodedValues = [$row->id, is_array($values) ? $values : []];
        }

        $values = $this->decodedValues[1];

        if ($field->value_per_locale) {
            $locales = (array) ($values['locale_specific'] ?? []);

            ksort($locales, SORT_STRING);

            $value = array_find(array_map(fn (mixed $section): mixed => is_array($section) ? ($section[$field->code] ?? null) : null, $locales), fn (mixed $item): bool => $item !== null);
        } else {
            $value = $values['common'][$field->code] ?? null;
        }

        return match (true) {
            is_array($value)  => implode(', ', array_map(fn (mixed $item): string => is_scalar($item) ? (string) $item : '', $value)),
            is_bool($value)   => $value ? '1' : '0',
            is_scalar($value) => (string) $value,
            default           => '',
        };
    }

    /**
     * Constrain the grid to the records whose indexed value of the field matches any requested value.
     *
     * @param  array<array-key, mixed>  $values
     *
     * @throws ValidationException
     */
    protected function applyFieldFilter(Field $field, array $values): void
    {
        $typeId = $this->entityType()->id;

        $key = $this->recordRepository->scopedQuery($typeId)->getModel()->getQualifiedKeyName();

        try {
            $this->queryBuilder->where(function (Builder $group) use ($field, $values, $typeId, $key): void {
                foreach ($this->fieldComparisons($values, $this->fieldKind($field)) as $comparisons) {
                    $probe = $this->recordRepository->scopedQuery($typeId)->select($key);

                    foreach ($comparisons as [$operator, $operand]) {
                        $this->recordRepository->whereFieldValue($probe, $typeId, $field->code, $operator, $operand);
                    }

                    $group->orWhereIn($key, $probe);
                }
            });
        } catch (InvalidArgumentException) {
            throw ValidationException::withMessages([
                'filters' => trans('masterdata::app.records.datagrid.invalid-filter', ['field' => $field->label ?: $field->code]),
            ]);
        }
    }

    /**
     * Turn the requested values of a field column into groups of operator and operand, one group per alternative.
     *
     * @param  array<array-key, mixed>  $values
     * @return list<list<array{0: string, 1: mixed}>>
     *
     * @throws InvalidArgumentException
     */
    protected function fieldComparisons(array $values, string $kind): array
    {
        $groups = [];

        foreach ($values as $value) {
            if (is_array($value) && isset($value['operator'])) {
                $groups[] = $this->conditionComparisons(
                    $this->scalarOperand($value['operator']),
                    $this->scalarOperand($value['value'] ?? ''),
                    $this->scalarOperand($value['value2'] ?? ''),
                );

                continue;
            }

            if (is_array($value)) {
                $groups[] = array_values(array_filter([
                    ($from = $this->scalarOperand($value[0] ?? '')) !== '' ? ['>=', $from] : null,
                    ($to = $this->scalarOperand($value[1] ?? '')) !== '' ? ['<=', $to] : null,
                ]));

                continue;
            }

            $groups[] = [[$kind === 'text' ? 'like' : '=', $this->scalarOperand($value)]];
        }

        return $groups;
    }

    /**
     * Refuse a filter part that is not a scalar, so malformed arrays never reach a cast or the index query.
     *
     * @throws InvalidArgumentException
     */
    protected function scalarOperand(mixed $value): string|int|float|bool
    {
        if (! is_scalar($value)) {
            throw new InvalidArgumentException('A grid filter operator and operand must be scalar.');
        }

        return $value;
    }

    /**
     * Map a grid filter operator to the repository's field comparisons.
     *
     * @return list<array{0: string, 1: mixed}>
     *
     * @throws InvalidArgumentException
     */
    protected function conditionComparisons(string|int|float|bool $operator, mixed $value, mixed $value2): array
    {
        return match ($operator) {
            'eq'               => [['=', $value]],
            'gt'               => [['>', $value]],
            'gte'              => [['>=', $value]],
            'lt'               => [['<', $value]],
            'lte'              => [['<=', $value]],
            'within_range'     => [['>=', $value], ['<=', $value2]],
            'like', 'contains' => [['like', $value]],
            default            => throw new InvalidArgumentException(sprintf('Unsupported grid filter operator [%s].', (string) $operator)),
        };
    }

    /**
     * The entity type bound on the grid's route.
     */
    protected function entityType(): EntityType
    {
        $entityType = request()->route('entity_type');

        abort_unless($entityType instanceof EntityType, 404);

        return $entityType;
    }

    /**
     * Build the correlated sub-select that aggregates a record's active "system:external_id" pairs.
     *
     * PostgreSQL has no GROUP_CONCAT, so it alone takes STRING_AGG; every other engine uses the MySQL form.
     * Identifiers go through the query grammar so a DB_PREFIX is applied.
     */
    protected function externalIdsExpression(): string
    {
        $grammar = DB::getQueryGrammar();

        $xrefs = $grammar->wrapTable('mdm_xrefs');
        $systems = $grammar->wrapTable('mdm_source_systems');
        $pair = 'CONCAT('.$grammar->wrap('mdm_source_systems.code').", ':', ".$grammar->wrap('mdm_xrefs.external_id').')';

        $aggregate = DB::connection()->getDriverName() === 'pgsql'
            ? "STRING_AGG($pair, ', ' ORDER BY $pair)"
            : "GROUP_CONCAT($pair ORDER BY $pair SEPARATOR ', ')";

        return '(select '.$aggregate.' from '.$xrefs.' inner join '.$systems.' on '
            .$grammar->wrap('mdm_source_systems.id').' = '.$grammar->wrap('mdm_xrefs.source_system_id')
            .' where '.$grammar->wrap('mdm_xrefs.record_id').' = '.$grammar->wrap('mdm_records.id')
            .' and '.$grammar->wrap('mdm_xrefs.status')." = '".XrefStatus::Active->value."')";
    }

    /**
     * Build the CASE expression that puts a record's completeness into a filter band.
     *
     * The column goes through the query grammar so a DB_PREFIX and identifier quoting apply.
     */
    protected function completenessBandExpression(): string
    {
        $column = DB::getQueryGrammar()->wrap('mdm_records.completeness');

        return Completeness::bandCase($column);
    }

    /**
     * Render a completeness score as the coloured pill the product grid uses.
     */
    protected function completenessPill(mixed $score): string
    {
        if ($score === null) {
            return e(trans('masterdata::app.quality.completeness.not-scored'));
        }

        $score = (int) $score;

        $class = match (Completeness::band($score)) {
            Completeness::BAND_COMPLETE => 'pill-high',
            Completeness::BAND_PARTIAL  => 'pill-medium',
            default                     => 'pill-low',
        };

        return '<span class="inline-flex items-center justify-center px-2 py-1 min-w-10 rounded-md text-xs text-white '.$class.'">'.$score.'%</span>';
    }
}
