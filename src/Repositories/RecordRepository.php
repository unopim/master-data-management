<?php

namespace Webkul\MasterDataManagement\Repositories;

use Closure;
use Illuminate\Container\Container;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Str;
use InvalidArgumentException;
use LogicException;
use Prettus\Repository\Contracts\CriteriaInterface;
use Webkul\Core\Eloquent\Repository;
use Webkul\MasterDataManagement\Contracts\Record;
use Webkul\MasterDataManagement\Enums\RecordStatus;
use Webkul\MasterDataManagement\FieldTypes\FieldTypeRegistry;
use Webkul\MasterDataManagement\Models\Field;
use Webkul\MasterDataManagement\Models\Record as RecordModel;
use Webkul\MasterDataManagement\Quality\Completeness;
use Webkul\MasterDataManagement\Security\EntityTypePermissions;
use Webkul\MasterDataManagement\Security\RecordScope;
use Webkul\MasterDataManagement\Services\Normalizer;
use Webkul\MasterDataManagement\Services\RecordIndexer;
use Webkul\User\Models\Admin;

/**
 * Reads must go through scopedQuery() or a scoped finder and writes through RecordWriter, so the
 * inherited unscoped readers and writers throw LogicException instead of bypassing the record
 * scope, the ChangeGate or the revision lock.
 */
class RecordRepository extends Repository
{
    public function __construct(
        protected RecordScope $recordScope,
        protected FieldRepository $fieldRepository,
        protected Normalizer $normalizer,
        protected EntityTypePermissions $entityTypePermissions,
        Container $container
    ) {
        parent::__construct($container);
    }

    /**
     * Get model class name.
     */
    public function model(): string
    {
        return Record::class;
    }

    /**
     * Start a record query for one entity type, restricted to what the admin may see.
     *
     * Without an explicit admin it falls back to the authenticated admin, so request-context callers
     * cannot forget the scope. Queue and console callers without an admin read unscoped.
     */
    public function scopedQuery(int $entityTypeId, ?Admin $admin = null): Builder
    {
        $query = $this->model->newQuery()->where('entity_type_id', $entityTypeId);

        $admin ??= $this->currentAdmin();

        return $admin instanceof Admin ? $this->recordScope->apply($query, $admin, $entityTypeId) : $query;
    }

    /**
     * Find a record by its public UUID, following a merge to the surviving record (one hop).
     *
     * Merges never cross entity types, so the located row's type scopes the survivor too.
     */
    public function findByUuid(string $uuid, ?Admin $admin = null): ?RecordModel
    {
        if (! Str::isUuid($uuid)) {
            return null;
        }

        $located = $this->model->newQuery()->where('uuid', $uuid)->first(['id', 'entity_type_id', 'merged_into_id']);

        if (! $located) {
            return null;
        }

        return $this->scopedQuery($located->entity_type_id, $admin)
            ->whereKey($located->merged_into_id ?? $located->id)
            ->first();
    }

    /**
     * Find a record by id, restricted to what the authenticated admin may see.
     *
     * @param  int|string  $id
     * @param  array<int, string>  $columns
     */
    public function find($id, $columns = ['*']): ?RecordModel
    {
        $entityTypeId = $this->model->newQuery()->whereKey($id)->value('entity_type_id');

        if ($entityTypeId === null) {
            return null;
        }

        return $this->scopedQuery((int) $entityTypeId)->whereKey($id)->first($columns);
    }

    /**
     * Find a record by id or fail, restricted to what the authenticated admin may see.
     *
     * @param  int|string  $id
     * @param  array<int, string>  $columns
     *
     * @throws ModelNotFoundException
     */
    public function findOrFail($id, $columns = ['*']): RecordModel
    {
        return $this->find($id, $columns)
            ?? throw (new ModelNotFoundException)->setModel($this->model::class, [$id]);
    }

    /**
     * Cursor-paginate the records of an entity type the admin may see, ordered by id.
     */
    public function paginateForEntityType(int $entityTypeId, int $perPage = 50, ?Admin $admin = null): CursorPaginator
    {
        return $this->scopedQuery($entityTypeId, $admin)->orderBy('id')->cursorPaginate($perPage);
    }

    /**
     * Find a record by its business code within an entity type.
     */
    public function findByCode(int $entityTypeId, string $code, ?Admin $admin = null): ?RecordModel
    {
        return $this->scopedQuery($entityTypeId, $admin)->where('code', $code)->first();
    }

    /**
     * Find the records of an entity type with the given codes, restricted to what the admin may see.
     *
     * @param  list<string>  $codes
     * @return Collection<string, RecordModel> Keyed by code.
     */
    public function findManyByCodes(int $entityTypeId, array $codes, ?Admin $admin = null): Collection
    {
        if ($codes === []) {
            return new Collection;
        }

        return $this->scopedQuery($entityTypeId, $admin)
            ->whereIn('code', array_values(array_unique($codes)))
            ->get()
            ->keyBy('code');
    }

    /**
     * Find the records of an entity type with the given ids, restricted to what the admin may see.
     *
     * @param  list<int>  $ids
     * @return Collection<int, RecordModel> Keyed by id.
     */
    public function findManyForEntityType(int $entityTypeId, array $ids, ?Admin $admin = null): Collection
    {
        if ($ids === []) {
            return new Collection;
        }

        return $this->scopedQuery($entityTypeId, $admin)
            ->whereKey(array_values(array_unique($ids)))
            ->get()
            ->keyBy('id');
    }

    /**
     * Whether any record exists for the entity type, regardless of row scope.
     */
    public function existsForEntityType(int $entityTypeId): bool
    {
        return $this->model->newQuery()->where('entity_type_id', $entityTypeId)->exists();
    }

    /**
     * Whether the code is already used within the entity type, regardless of row scope.
     */
    public function codeExists(int $entityTypeId, string $code): bool
    {
        return $this->model->newQuery()
            ->where('entity_type_id', $entityTypeId)
            ->where('code', $code)
            ->exists();
    }

    /**
     * Count the records per entity type in one grouped query, regardless of row scope.
     *
     * @param  list<int>|null  $entityTypeIds  Limit the counts to these types; null counts every type.
     * @return array<int, int> Record counts keyed by entity type id.
     */
    public function countsByEntityType(?array $entityTypeIds = null): array
    {
        if ($entityTypeIds === []) {
            return [];
        }

        return $this->model->newQuery()
            ->when($entityTypeIds !== null, fn (Builder $query) => $query->whereIn('entity_type_id', $entityTypeIds))
            ->selectRaw('entity_type_id, count(*) as total')
            ->groupBy('entity_type_id')
            ->pluck('total', 'entity_type_id')
            ->map(fn ($total): int => (int) $total)
            ->all();
    }

    /**
     * Bump the revision only if it still equals the expected one.
     *
     * Runs as a single conditional UPDATE so concurrent writers on any engine cannot both win.
     */
    public function claimRevision(RecordModel $record, int $expectedRevision): bool
    {
        return $this->model->newQuery()
            ->whereKey($record->getKey())
            ->where('revision', $expectedRevision)
            ->increment('revision') === 1;
    }

    /**
     * Move the record's updated_at to now without a revision, history entry or model event.
     */
    public function touchUpdatedAt(RecordModel $record): void
    {
        $now = $record->freshTimestamp();

        $this->model->newQuery()->whereKey($record->getKey())->toBase()->update(['updated_at' => $now]);

        $record->setAttribute('updated_at', $now)->syncOriginalAttribute('updated_at');
    }

    /**
     * Constrain a record query to records whose indexed value of a searchable field matches.
     *
     * Text compares normalised values with `=` or a prefix `like`; numeric and date values compare with
     * `=`, `<`, `<=`, `>` or `>=`. The field type converts the operand, so a value it cannot convert matches
     * nothing. Without a locale, a per-locale field matches a value in any locale.
     *
     * @throws InvalidArgumentException When the field is not searchable or the operator does not fit its values.
     */
    public function whereFieldValue(Builder $query, int $entityTypeId, string $fieldCode, string $operator, mixed $value, ?string $locale = null): Builder
    {
        $field = $this->fieldRepository->searchableFor($entityTypeId)->firstWhere('code', $fieldCode);

        if (! $field instanceof Field) {
            throw new InvalidArgumentException(sprintf('Master data field [%s] is not searchable.', $fieldCode));
        }

        $comparison = $this->indexComparison($field, strtolower($operator), $value);

        if ($comparison === null) {
            return $query->whereIn($query->getModel()->getQualifiedKeyName(), []);
        }

        [$column, $operator, $operand] = $comparison;

        $table = RecordIndexer::TABLE;

        return $query->whereExists(fn (QueryBuilder $index) => $index
            ->from($table)
            ->whereColumn($table.'.record_id', $query->getModel()->getQualifiedKeyName())
            ->where($table.'.entity_type_id', $entityTypeId)
            ->where($table.'.field_code', $fieldCode)
            ->when($locale !== null, fn (QueryBuilder $index) => $index->where($table.'.locale', $locale))
            ->where($table.'.'.$column, $operator, $operand));
    }

    /**
     * Start a query over the record's descendants of every entity type, deepest levels included.
     *
     * It goes through the cross-type row scope, so scoped types keep only the rows in the admin's subtrees.
     */
    public function descendantsQuery(RecordModel $record, ?Admin $admin = null): Builder
    {
        return $this->crossTypeQuery($admin)
            ->where('path', 'like', $record->path.'%')
            ->whereKeyNot($record->id);
    }

    /**
     * Get the record's direct children of every entity type the admin may see, ordered by id.
     *
     * @return Collection<int, RecordModel>
     */
    public function childrenOf(RecordModel $record, int $limit = 100, ?Admin $admin = null): Collection
    {
        return $this->crossTypeQuery($admin)
            ->where('parent_id', $record->id)
            ->with('entityType.translations')
            ->orderBy('id')
            ->limit($limit)
            ->get();
    }

    /**
     * Get the record's ancestors the admin may see, from the root down, read from its path in one query.
     *
     * @return Collection<int, RecordModel>
     */
    public function ancestorsOf(RecordModel $record, ?Admin $admin = null): Collection
    {
        $ids = array_values(array_diff(
            array_map(intval(...), array_filter(explode('/', (string) $record->path), is_numeric(...))),
            [(int) $record->id],
        ));

        if ($ids === []) {
            return $this->model->newCollection();
        }

        return $this->crossTypeQuery($admin)
            ->whereKey($ids)
            ->with('entityType.translations')
            ->orderBy('depth')
            ->get();
    }

    /**
     * Map the given record ids the admin may see to their codes, across entity types, in one query.
     *
     * @param  list<int>  $ids
     * @return array<int, string> Codes keyed by record id.
     */
    public function codesByIds(array $ids, ?Admin $admin = null): array
    {
        if ($ids === []) {
            return [];
        }

        return $this->crossTypeQuery($admin)
            ->whereKey($ids)
            ->toBase()
            ->pluck('code', 'id')
            ->mapWithKeys(fn (mixed $code, mixed $id): array => [(int) $id => (string) $code])
            ->all();
    }

    /**
     * Map the given record ids the admin may see to their uuid, code and label, across entity types, in one query.
     *
     * @param  list<int>  $ids
     * @return array<int, array{uuid: string, code: string, label: string}>
     */
    public function trailNodes(array $ids, ?Admin $admin = null): array
    {
        if ($ids === []) {
            return [];
        }

        return $this->crossTypeQuery($admin)
            ->whereKey($ids)
            ->toBase()
            ->get(['id', 'uuid', 'code', 'label'])
            ->mapWithKeys(fn (object $row): array => [(int) $row->id => [
                'uuid'  => (string) $row->uuid,
                'code'  => (string) $row->code,
                'label' => (string) $row->label,
            ]])
            ->all();
    }

    /**
     * Count the direct children of the given entity types the admin may see under each given parent, in one query.
     *
     * @param  list<int>  $parentIds
     * @param  list<int>  $entityTypeIds
     * @return array<int, int> Child counts keyed by parent id.
     */
    public function childCounts(array $parentIds, array $entityTypeIds, ?Admin $admin = null): array
    {
        if ($parentIds === [] || $entityTypeIds === []) {
            return [];
        }

        return $this->crossTypeQuery($admin)
            ->whereIn('entity_type_id', $entityTypeIds)
            ->whereIn('parent_id', $parentIds)
            ->toBase()
            ->select('parent_id')
            ->selectRaw('count(*) as aggregate')
            ->groupBy('parent_id')
            ->pluck('aggregate', 'parent_id')
            ->mapWithKeys(fn (mixed $count, mixed $id): array => [(int) $id => (int) $count])
            ->all();
    }

    /**
     * The ids of the records with the given uuids the admin may see, across entity types.
     *
     * @param  list<string>  $uuids
     * @return list<int>
     */
    public function visibleIdsByUuids(array $uuids, ?Admin $admin = null): array
    {
        if ($uuids === []) {
            return [];
        }

        return $this->crossTypeQuery($admin)
            ->whereIn('uuid', $uuids)
            ->toBase()
            ->pluck('id')
            ->map(fn (mixed $id): int => (int) $id)
            ->values()
            ->all();
    }

    /**
     * Map uuids to record ids in one query, following a merge to the surviving record (one hop).
     *
     * Unscoped: the record writer resolves reference values it already validated.
     *
     * @param  list<string>  $uuids
     * @return array<string, int> Record ids keyed by the given uuid.
     */
    public function idsByUuids(array $uuids): array
    {
        if ($uuids === []) {
            return [];
        }

        return $this->model->newQuery()
            ->whereIn('uuid', $uuids)
            ->toBase()
            ->get(['id', 'uuid', 'merged_into_id'])
            ->mapWithKeys(fn (object $row): array => [(string) $row->uuid => (int) ($row->merged_into_id ?? $row->id)])
            ->all();
    }

    /**
     * The given uuids of the records the admin may see, through the entity type grants and the record scope.
     *
     * Without an explicit admin it falls back to the authenticated admin; queue and console callers see every uuid.
     *
     * @param  list<string>  $uuids
     * @return list<string>
     */
    public function visibleUuids(array $uuids, ?Admin $admin = null): array
    {
        if ($uuids === []) {
            return [];
        }

        return $this->crossTypeQuery($admin)
            ->whereIn('uuid', $uuids)
            ->toBase()
            ->pluck('uuid')
            ->map(fn (mixed $uuid): string => (string) $uuid)
            ->values()
            ->all();
    }

    /**
     * Get the draft or active records of the given entity types that can become a record's parent or reference target.
     *
     * For an existing record, the record itself and its descendants are left out, since either would close a cycle.
     * The optional term matches the code or label case-insensitively; the optional uuids restrict the result to them.
     *
     * @param  list<int>  $entityTypeIds
     * @param  list<string>  $uuids
     * @return Collection<int, RecordModel>
     */
    public function parentCandidates(?RecordModel $record, array $entityTypeIds, int $limit = 100, ?Admin $admin = null, ?string $term = null, array $uuids = []): Collection
    {
        if ($entityTypeIds === []) {
            return $this->model->newCollection();
        }

        $term = trim((string) $term);

        $pattern = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $term).'%';

        return $this->crossTypeQuery($admin)
            ->whereIn('entity_type_id', $entityTypeIds)
            ->whereIn('status', [RecordStatus::Draft->value, RecordStatus::Active->value])
            ->when($record instanceof RecordModel, fn (Builder $query) => $query
                ->whereKeyNot($record->id)
                ->where('path', 'not like', $record->path.'%'))
            ->when($uuids !== [], fn (Builder $query) => $query->whereIn('uuid', $uuids))
            ->when($term !== '', fn (Builder $query) => $query->where(fn (Builder $match) => $match
                ->whereLike('code', $pattern)
                ->orWhereLike('label', $pattern)))
            ->with('entityType.translations')
            ->orderBy('code')
            ->limit($limit)
            ->get(['id', 'uuid', 'entity_type_id', 'code', 'label', 'path']);
    }

    /**
     * Fetch the bounded set of draft and active records the match suggester scores, restricted to what the admin may see.
     *
     * A record qualifies when its code equals one of the codes, or its code or label starts with one of the prefixes
     * (case-insensitively), or its indexed label field value starts with one of the normalised prefixes, or it shares
     * the value of a blocking key: the code or label case-insensitively, a searchable field by its indexed value.
     * Blocking keys on fields that are not searchable are left out, since only indexed values can be looked up.
     *
     * @param  list<string>  $codes
     * @param  list<string>  $prefixes
     * @param  list<array{0: string, 1: string}>  $blockingKeys  Target (code, label or a field code) and value pairs.
     * @return Collection<int, RecordModel>
     */
    public function matchCandidates(int $entityTypeId, array $codes, array $prefixes, int $limit, ?Admin $admin = null, ?string $labelFieldCode = null, array $blockingKeys = [], bool $withValues = false): Collection
    {
        $searchableCodes = $this->fieldRepository->searchableFor($entityTypeId)->pluck('code')->all();

        $blockingKeys = array_values(array_filter(
            $blockingKeys,
            fn (array $key): bool => in_array($key[0], ['code', 'label'], true) || in_array($key[0], $searchableCodes, true),
        ));

        if ($codes === [] && $prefixes === [] && $blockingKeys === []) {
            return $this->model->newCollection();
        }

        $searchable = $labelFieldCode !== null && in_array($labelFieldCode, $searchableCodes, true);

        $columns = ['id', 'uuid', 'entity_type_id', 'code', 'label', 'status', 'parent_id', 'path'];

        return $this->scopedQuery($entityTypeId, $admin)
            ->whereIn('status', [RecordStatus::Draft->value, RecordStatus::Active->value])
            ->whereNull('merged_into_id')
            ->where(function (Builder $match) use ($codes, $prefixes, $searchable, $entityTypeId, $labelFieldCode, $blockingKeys): void {
                if ($codes !== []) {
                    $match->orWhereIn('code', $codes);
                }

                foreach ($prefixes as $prefix) {
                    $pattern = $this->escapeLike($prefix).'%';

                    $match->orWhereLike('code', $pattern)->orWhereLike('label', $pattern);

                    if ($searchable) {
                        $match->orWhere(fn (Builder $indexed): Builder => $this->whereFieldValue($indexed, $entityTypeId, (string) $labelFieldCode, 'like', $prefix));
                    }
                }

                foreach ($blockingKeys as [$target, $value]) {
                    if (in_array($target, ['code', 'label'], true)) {
                        $match->orWhereLike($target, $this->escapeLike($value));

                        continue;
                    }

                    $match->orWhere(fn (Builder $indexed): Builder => $this->whereFieldValue($indexed, $entityTypeId, $target, '=', $value));
                }
            })
            ->orderBy('id')
            ->limit($limit)
            ->get($withValues ? [...$columns, 'values'] : $columns);
    }

    /**
     * Escape the LIKE wildcards of a value so it matches literally.
     */
    protected function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }

    /**
     * Start a query over every record below the path prefix except the given one, regardless of row scope.
     *
     * @internal For HierarchyService path maintenance, which must reach rows the current admin cannot see.
     */
    public function pathPrefixQuery(string $pathPrefix, int $exceptId): Builder
    {
        return $this->model->newQuery()
            ->where('path', 'like', $pathPrefix.'%')
            ->whereKeyNot($exceptId);
    }

    /**
     * Read the hierarchy columns of one record regardless of row scope, optionally locking the row.
     *
     * @internal For HierarchyService, which walks parent_id chains through rows the admin may not see.
     */
    public function hierarchyRow(int $id, bool $lock = false): ?RecordModel
    {
        return $this->model->newQuery()
            ->whereKey($id)
            ->when($lock, fn (Builder $query) => $query->lockForUpdate())
            ->first(['id', 'uuid', 'entity_type_id', 'parent_id', 'path', 'depth']);
    }

    /**
     * The lifecycle status of one record regardless of row scope; null for an unknown id.
     */
    public function statusOf(int $id): ?RecordStatus
    {
        $status = $this->model->newQuery()->whereKey($id)->toBase()->value('status');

        return is_string($status) ? RecordStatus::tryFrom($status) : null;
    }

    /**
     * Whether a record below the path prefix has a path that does not extend its parent's, regardless of row scope.
     *
     * Such a row is waiting for a queued path rewrite.
     */
    public function hasStaleDescendants(string $pathPrefix, int $exceptId): bool
    {
        $table = $this->model->getTable();

        $grammar = $this->model->getConnection()->getQueryGrammar();

        $childPath = $grammar->wrap('child.path');

        $parentPath = $grammar->wrap('parent.path');

        return $this->model->getConnection()->table($table.' as child')
            ->join($table.' as parent', 'parent.id', '=', 'child.parent_id')
            ->where('child.path', 'like', $pathPrefix.'%')
            ->where('child.id', '<>', $exceptId)
            ->whereRaw('SUBSTRING('.$childPath.', 1, LENGTH('.$parentPath.')) <> '.$parentPath)
            ->exists();
    }

    /**
     * The length of the longest path below the prefix, regardless of row scope; 0 without descendants.
     */
    public function maxPathLength(string $pathPrefix, int $exceptId): int
    {
        $path = $this->model->getConnection()->getQueryGrammar()->wrap('path');

        return (int) $this->pathPrefixQuery($pathPrefix, $exceptId)->max(new Expression('LENGTH('.$path.')'));
    }

    /**
     * Walk the ids of every record of the entity type in batches, regardless of row scope.
     *
     * @param  Closure(list<int>): void  $callback
     */
    public function chunkIdsForEntityType(int $entityTypeId, int $size, Closure $callback): void
    {
        $this->model->newQuery()
            ->where('entity_type_id', $entityTypeId)
            ->select('id')
            ->chunkById($size, function (Collection $records) use ($callback): void {
                $callback($records->modelKeys());
            });
    }

    /**
     * Read the records with a row lock for re-indexing, regardless of row scope; call inside a transaction.
     *
     * @param  list<int>  $ids
     * @return Collection<int, RecordModel>
     */
    public function lockForIndexing(array $ids): Collection
    {
        return $this->model->newQuery()->whereKey($ids)->orderBy('id')->lockForUpdate()->get();
    }

    /**
     * Read the records with their entity type for a quality scan, regardless of row scope.
     *
     * @internal For the quality scanner, which checks every record of a type.
     *
     * @param  list<int>  $ids
     * @return Collection<int, RecordModel>
     */
    public function forQualityScan(array $ids): Collection
    {
        return $this->model->newQuery()->whereKey($ids)->orderBy('id')->get();
    }

    /**
     * Read the code, entity type and status of the given records with their type code, keyed by id, regardless of
     * row scope.
     *
     * @internal For quality checks, which compare a record with a parent the current admin may not see.
     *
     * @param  list<int>  $ids
     * @return array<int, object{id: int, code: string, entity_type_id: int, status: string, type_code: string}>
     */
    public function typeSummaries(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $table = $this->model->getTable();

        return $this->model->getConnection()->table($table)
            ->join('mdm_entity_types', 'mdm_entity_types.id', '=', $table.'.entity_type_id')
            ->whereIn($table.'.id', $ids)
            ->get([$table.'.id', $table.'.code', $table.'.entity_type_id', $table.'.status', 'mdm_entity_types.code as type_code'])
            ->keyBy(fn (object $row): int => (int) $row->id)
            ->all();
    }

    /**
     * Store the completeness scores of records, one UPDATE per distinct score, without touching updated_at.
     *
     * @internal Completeness is derived from the values RecordWriter stored; only the quality scanner refreshes it.
     *
     * @param  array<int, ?int>  $scores  Scores keyed by record id.
     */
    public function storeCompleteness(array $scores): void
    {
        $groups = [];

        foreach ($scores as $id => $score) {
            $groups[$score === null ? '' : (string) $score][] = (int) $id;
        }

        foreach ($groups as $score => $ids) {
            $this->model->newQuery()
                ->whereKey($ids)
                ->toBase()
                ->update(['completeness' => $score === '' ? null : (int) $score]);
        }
    }

    /**
     * The ids of the record's direct children regardless of row scope, up to the limit.
     *
     * @internal For RecordMerger, which moves every child of the merged record.
     *
     * @return list<int>
     */
    public function childIds(int $parentId, int $limit): array
    {
        return $this->model->newQuery()
            ->where('parent_id', $parentId)
            ->orderBy('id')
            ->limit($limit)
            ->pluck('id')
            ->map(intval(...))
            ->all();
    }

    /**
     * Read and lock one full record row regardless of row scope.
     *
     * @internal For RecordMerger, which moves children the admin may not see.
     */
    public function lockedRow(int $id): ?RecordModel
    {
        return $this->model->newQuery()->whereKey($id)->lockForUpdate()->first();
    }

    /**
     * Point records merged into one record at another; returns the repointed ids.
     *
     * @return list<int>
     */
    public function repointMergedInto(int $fromId, int $toId): array
    {
        $ids = $this->model->newQuery()->where('merged_into_id', $fromId)->pluck('id')->map(intval(...))->all();

        foreach (array_chunk($ids, 500) as $chunk) {
            $this->model->newQuery()->whereKey($chunk)->toBase()->update(['merged_into_id' => $toId]);
        }

        return $ids;
    }

    /**
     * Point the given merged records back at their original survivor while they still point at the current one.
     *
     * @param  list<int>  $ids
     */
    public function restoreMergedInto(array $ids, int $currentId, int $originalId): void
    {
        foreach (array_chunk($ids, 500) as $chunk) {
            $this->model->newQuery()->whereKey($chunk)->where('merged_into_id', $currentId)->toBase()->update(['merged_into_id' => $originalId]);
        }
    }

    /**
     * Summarise the completeness of the draft and active records per entity type in one grouped query,
     * regardless of row scope.
     *
     * @param  list<int>  $entityTypeIds
     * @return array<int, array{records: int, average: ?int, complete: int, unscored: int}> Keyed by entity type id.
     */
    public function completenessSummary(array $entityTypeIds): array
    {
        if ($entityTypeIds === []) {
            return [];
        }

        $completeness = $this->model->getConnection()->getQueryGrammar()->wrap('completeness');

        return $this->model->newQuery()
            ->toBase()
            ->whereIn('entity_type_id', $entityTypeIds)
            ->whereIn('status', [RecordStatus::Draft->value, RecordStatus::Active->value])
            ->groupBy('entity_type_id')
            ->select('entity_type_id')
            ->selectRaw('count(*) as records')
            ->selectRaw('avg('.$completeness.') as average')
            ->selectRaw('sum(case when '.$completeness.' >= '.Completeness::COMPLETE.' then 1 else 0 end) as complete')
            ->selectRaw('sum(case when '.$completeness.' is null then 1 else 0 end) as unscored')
            ->get()
            ->mapWithKeys(fn (object $row): array => [(int) $row->entity_type_id => [
                'records'  => (int) $row->records,
                'average'  => $row->average === null ? null : (int) round((float) $row->average),
                'complete' => (int) $row->complete,
                'unscored' => (int) $row->unscored,
            ]])
            ->all();
    }

    public function all($columns = ['*']): never
    {
        throw $this->unscopedRead(__FUNCTION__);
    }

    public function get($columns = ['*']): never
    {
        throw $this->unscopedRead(__FUNCTION__);
    }

    public function first($columns = ['*']): never
    {
        throw $this->unscopedRead(__FUNCTION__);
    }

    public function firstOrNew(array $attributes = []): never
    {
        throw $this->unscopedRead(__FUNCTION__);
    }

    public function firstOrCreate(array $attributes = []): never
    {
        throw $this->unscopedRead(__FUNCTION__);
    }

    public function limit($limit, $columns = ['*']): never
    {
        throw $this->unscopedRead(__FUNCTION__);
    }

    public function paginate($limit = null, $columns = ['*'], $method = 'paginate'): never
    {
        throw $this->unscopedRead(__FUNCTION__);
    }

    public function simplePaginate($limit = null, $columns = ['*']): never
    {
        throw $this->unscopedRead(__FUNCTION__);
    }

    public function findByField($field, $value = null, $columns = ['*']): never
    {
        throw $this->unscopedRead(__FUNCTION__);
    }

    public function findWhere(array $where, $columns = ['*']): never
    {
        throw $this->unscopedRead(__FUNCTION__);
    }

    public function findWhereIn($field, array $values, $columns = ['*']): never
    {
        throw $this->unscopedRead(__FUNCTION__);
    }

    public function findWhereNotIn($field, array $values, $columns = ['*']): never
    {
        throw $this->unscopedRead(__FUNCTION__);
    }

    public function findWhereBetween($field, array $values, $columns = ['*']): never
    {
        throw $this->unscopedRead(__FUNCTION__);
    }

    public function findOneByField($field, $value = null, $columns = ['*']): never
    {
        throw $this->unscopedRead(__FUNCTION__);
    }

    public function findOneWhere(array $where, $columns = ['*']): never
    {
        throw $this->unscopedRead(__FUNCTION__);
    }

    public function count(array $where = [], $columns = '*'): never
    {
        throw $this->unscopedRead(__FUNCTION__);
    }

    public function sum($columns): never
    {
        throw $this->unscopedRead(__FUNCTION__);
    }

    public function avg($columns): never
    {
        throw $this->unscopedRead(__FUNCTION__);
    }

    public function lists($column, $key = null): never
    {
        throw $this->unscopedRead(__FUNCTION__);
    }

    public function pluck($column, $key = null): never
    {
        throw $this->unscopedRead(__FUNCTION__);
    }

    public function getByCriteria(CriteriaInterface $criteria): never
    {
        throw $this->unscopedRead(__FUNCTION__);
    }

    /**
     * Insert a record row.
     *
     * @internal Use RecordWriter::create(); this skips validation and the change gate.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes)
    {
        return parent::create($attributes);
    }

    public function update(array $attributes, $id): never
    {
        throw $this->bypassesWriter(__FUNCTION__);
    }

    public function updateOrCreate(array $attributes, array $values = []): never
    {
        throw $this->bypassesWriter(__FUNCTION__);
    }

    public function delete($id): never
    {
        throw $this->bypassesWriter(__FUNCTION__);
    }

    public function deleteWhere(array $where): never
    {
        throw $this->bypassesWriter(__FUNCTION__);
    }

    public function sync($id, $relation, $attributes, $detaching = true): never
    {
        throw $this->bypassesWriter(__FUNCTION__);
    }

    public function syncWithoutDetaching($id, $relation, $attributes): never
    {
        throw $this->bypassesWriter(__FUNCTION__);
    }

    /**
     * Refuse model calls proxied through the repository, which would bypass the record scope.
     *
     * @param  string  $method
     * @param  array<int, mixed>  $arguments
     *
     * @throws LogicException
     */
    public function __call($method, $arguments): never
    {
        throw $this->unscopedRead($method);
    }

    /**
     * The index column, operator and operand of a field filter, or null when the operand matches nothing.
     *
     * @return array{0: string, 1: string, 2: string}|null
     *
     * @throws InvalidArgumentException
     */
    protected function indexComparison(Field $field, string $operator, mixed $value): ?array
    {
        if (! in_array($operator, ['=', 'like', '<', '<=', '>', '>='], true)) {
            throw new InvalidArgumentException(sprintf('Unsupported master data filter operator [%s].', $operator));
        }

        if ($operator === 'like') {
            $text = is_scalar($value) ? $this->normalizedOperand((string) $value) : null;

            return $text === null ? null : ['value_normalized', 'like', $text.'%'];
        }

        $index = resolve(FieldTypeRegistry::class)->get($field->type)->indexValue($value, $field);

        if ($index->numeric !== null) {
            return ['value_numeric', $operator, $index->numeric];
        }

        if ($index->date !== null) {
            return ['value_date', $operator, $index->date];
        }

        if ($index->text !== null) {
            throw_if($operator !== '=', InvalidArgumentException::class, sprintf('Operator [%s] does not apply to text field [%s].', $operator, $field->code));

            $text = $this->normalizedOperand($index->text);

            return $text === null ? null : ['value_normalized', '=', $text];
        }

        return is_numeric($value) ? ['value_numeric', $operator, (string) $value] : null;
    }

    /**
     * The operand in its indexed text form, or null when nothing searchable is left.
     */
    protected function normalizedOperand(string $value): ?string
    {
        $text = mb_substr($this->normalizer->normalize($value), 0, 255);

        return $text === '' ? null : $text;
    }

    /**
     * Build the error for a read that would bypass the record scope.
     */
    protected function unscopedRead(string $method): LogicException
    {
        return new LogicException(sprintf('%s::%s() bypasses the record scope; use scopedQuery() or a scoped finder.', static::class, $method));
    }

    /**
     * Build the error for a write that would bypass RecordWriter.
     */
    protected function bypassesWriter(string $method): LogicException
    {
        return new LogicException(sprintf('%s::%s() bypasses the change gate and revision lock; write through RecordWriter.', static::class, $method));
    }

    /**
     * Start a query spanning entity types, limited to the types the admin may view and the cross-type row scope.
     *
     * Without an explicit admin it falls back to the authenticated admin; queue and console callers read unscoped.
     */
    protected function crossTypeQuery(?Admin $admin): Builder
    {
        $query = $this->model->newQuery();

        $admin ??= $this->currentAdmin();

        if (! $admin instanceof Admin) {
            return $query;
        }

        $visible = $this->entityTypePermissions->visibleEntityTypeIds($admin);

        if ($visible !== null) {
            $query->whereIn('entity_type_id', $visible);
        }

        return $this->recordScope->applyAcrossTypes($query, $admin);
    }

    /**
     * Resolve the admin authenticated on the request's default guard (api under auth:api, admin in the panel).
     */
    protected function currentAdmin(): ?Admin
    {
        $user = auth()->user();

        return $user instanceof Admin ? $user : null;
    }

    /**
     * The ids of the record and of every record merged into it, directly or along a merge chain.
     *
     * @return list<int>
     */
    public function mergedFamilyIds(int $id): array
    {
        $ids = [$id];
        $frontier = [$id];

        while ($frontier !== []) {
            $frontier = array_values(array_diff(
                $this->model->newQuery()->whereIn('merged_into_id', $frontier)->pluck('id')->map(intval(...))->all(),
                $ids,
            ));

            array_push($ids, ...$frontier);
        }

        return $ids;
    }
}
