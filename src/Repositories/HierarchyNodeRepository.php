<?php

namespace Webkul\MasterDataManagement\Repositories;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Query\Expression;
use Webkul\Core\Eloquent\Repository;
use Webkul\MasterDataManagement\Contracts\HierarchyNode;
use Webkul\MasterDataManagement\Models\HierarchyNode as HierarchyNodeModel;

/**
 * Node reads ignore the record scope: callers filter the records they show through RecordRepository.
 */
class HierarchyNodeRepository extends Repository
{
    /**
     * Get model class name.
     */
    public function model(): string
    {
        return HierarchyNode::class;
    }

    /**
     * The node of a record in a hierarchy, optionally locking the row.
     */
    public function nodeFor(int $hierarchyId, int $recordId, bool $lock = false): ?HierarchyNodeModel
    {
        return $this->model->newQuery()
            ->where('hierarchy_id', $hierarchyId)
            ->where('record_id', $recordId)
            ->when($lock, fn (Builder $query) => $query->lockForUpdate())
            ->first();
    }

    /**
     * Insert or update the node of a record in a hierarchy with the given placement.
     */
    public function put(int $hierarchyId, int $recordId, ?int $parentId, string $path, int $depth): void
    {
        $now = now();

        $this->model->newQuery()->upsert(
            [[
                'hierarchy_id' => $hierarchyId,
                'record_id'    => $recordId,
                'parent_id'    => $parentId,
                'path'         => $path,
                'depth'        => $depth,
                'created_at'   => $now,
                'updated_at'   => $now,
            ]],
            ['hierarchy_id', 'record_id'],
            ['parent_id', 'path', 'depth', 'updated_at'],
        );
    }

    /**
     * Insert the given nodes, leaving out the records a hierarchy already places.
     *
     * @param  list<array{hierarchy_id: int, record_id: int, parent_id: ?int, path: string, depth: int}>  $rows
     */
    public function insertMissing(array $rows): void
    {
        if ($rows === []) {
            return;
        }

        $now = now();

        $this->model->newQuery()->insertOrIgnore(array_map(fn (array $row): array => $row + ['created_at' => $now, 'updated_at' => $now], $rows));
    }

    /**
     * Start a query over the nodes of the hierarchy below the path prefix, except the given record's node.
     */
    public function subtreeQuery(int $hierarchyId, string $pathPrefix, ?int $exceptRecordId = null): Builder
    {
        return $this->model->newQuery()
            ->where('hierarchy_id', $hierarchyId)
            ->where('path', 'like', $pathPrefix.'%')
            ->when($exceptRecordId !== null, fn (Builder $query) => $query->where('record_id', '<>', $exceptRecordId));
    }

    /**
     * Start a query over the nodes of every primary hierarchy below the path prefix, except the given record's nodes.
     *
     * Primary nodes mirror the records' paths, which cross entity types, so a moved subtree spans hierarchies.
     */
    public function primarySubtreeQuery(string $pathPrefix, int $exceptRecordId): Builder
    {
        return $this->model->newQuery()
            ->whereIn('hierarchy_id', fn (QueryBuilder $primary) => $primary
                ->from('mdm_hierarchies')
                ->where('is_primary', true)
                ->select('id'))
            ->where('path', 'like', $pathPrefix.'%')
            ->where('record_id', '<>', $exceptRecordId);
    }

    /**
     * Count the nodes below the path prefix of the hierarchy, the node at the prefix excluded, up to the cap plus one.
     */
    public function subtreeSize(int $hierarchyId, string $pathPrefix, int $exceptRecordId, int $cap): int
    {
        return $this->model->getConnection()->query()
            ->fromSub($this->subtreeQuery($hierarchyId, $pathPrefix, $exceptRecordId)->select('id')->limit($cap + 1)->toBase(), 'capped')
            ->count();
    }

    /**
     * The length of the longest path below the prefix of the hierarchy; 0 without descendants.
     */
    public function maxPathLength(int $hierarchyId, string $pathPrefix, int $exceptRecordId): int
    {
        $path = $this->model->getConnection()->getQueryGrammar()->wrap('path');

        return (int) $this->subtreeQuery($hierarchyId, $pathPrefix, $exceptRecordId)->max(new Expression('LENGTH('.$path.')'));
    }

    /**
     * Point the direct children of one record in the hierarchy at another parent, or at the root for null.
     */
    public function repointChildren(int $hierarchyId, int $fromParentId, ?int $toParentId): void
    {
        $this->model->newQuery()
            ->where('hierarchy_id', $hierarchyId)
            ->where('parent_id', $fromParentId)
            ->toBase()
            ->update(['parent_id' => $toParentId, 'updated_at' => now()]);
    }

    /**
     * The direct children of a record in the hierarchy, in record id order.
     *
     * @return Collection<int, HierarchyNodeModel>
     */
    public function childrenOf(int $hierarchyId, int $parentId, int $limit): Collection
    {
        return $this->model->newQuery()
            ->where('hierarchy_id', $hierarchyId)
            ->where('parent_id', $parentId)
            ->orderBy('record_id')
            ->limit($limit)
            ->get(['id', 'hierarchy_id', 'record_id', 'parent_id', 'path', 'depth']);
    }

    /**
     * The top nodes of the hierarchy, in record id order: nodes without a parent, and primary nodes whose parent is a
     * record of another entity type.
     *
     * @return Collection<int, HierarchyNodeModel>
     */
    public function rootsOf(int $hierarchyId, int $limit): Collection
    {
        $table = $this->model->getTable();

        return $this->model->newQuery()
            ->where($table.'.hierarchy_id', $hierarchyId)
            ->where(fn (Builder $roots) => $roots
                ->whereNull($table.'.parent_id')
                ->orWhereNotExists(fn (QueryBuilder $parent) => $parent
                    ->from($table.' as parent_node')
                    ->whereColumn('parent_node.hierarchy_id', $table.'.hierarchy_id')
                    ->whereColumn('parent_node.record_id', $table.'.parent_id')))
            ->orderBy($table.'.record_id')
            ->limit($limit)
            ->get([$table.'.id', $table.'.hierarchy_id', $table.'.record_id', $table.'.parent_id', $table.'.path', $table.'.depth']);
    }

    /**
     * Constrain a record query to records outside the subtree below the path prefix of the hierarchy.
     *
     * @return Closure(Builder): Builder
     */
    public function outsideSubtree(int $hierarchyId, string $pathPrefix): Closure
    {
        $table = $this->model->getTable();

        return fn (Builder $records): Builder => $records->whereNotExists(fn (QueryBuilder $node) => $node
            ->from($table)
            ->whereColumn($table.'.record_id', $records->getModel()->getQualifiedKeyName())
            ->where($table.'.hierarchy_id', $hierarchyId)
            ->where($table.'.path', 'like', $pathPrefix.'%'));
    }

    /**
     * Count the direct children of each given record in the hierarchy, in one query.
     *
     * @param  list<int>  $recordIds
     * @return array<int, int> Child counts keyed by parent record id.
     */
    public function childCounts(int $hierarchyId, array $recordIds): array
    {
        if ($recordIds === []) {
            return [];
        }

        return $this->model->newQuery()
            ->where('hierarchy_id', $hierarchyId)
            ->whereIn('parent_id', $recordIds)
            ->toBase()
            ->select('parent_id')
            ->selectRaw('count(*) as aggregate')
            ->groupBy('parent_id')
            ->pluck('aggregate', 'parent_id')
            ->mapWithKeys(fn (mixed $count, mixed $id): array => [(int) $id => (int) $count])
            ->all();
    }

    /**
     * The nodes placing the given records in alternate hierarchies, or in every hierarchy, with their hierarchy.
     *
     * @param  list<int>  $recordIds
     * @return Collection<int, HierarchyNodeModel>
     */
    public function forRecords(array $recordIds, bool $alternatesOnly = true): Collection
    {
        if ($recordIds === []) {
            return $this->model->newCollection();
        }

        return $this->model->newQuery()
            ->with('hierarchy.translations')
            ->whereIn('record_id', $recordIds)
            ->when($alternatesOnly, fn (Builder $query) => $query->whereHas('hierarchy', fn (Builder $hierarchy) => $hierarchy->where('is_primary', false)))
            ->orderBy('hierarchy_id')
            ->orderBy('id')
            ->get();
    }

    /**
     * Delete the node of a record in a hierarchy.
     */
    public function deleteNode(int $id): void
    {
        $this->model->newQuery()->whereKey($id)->delete();
    }
}
