<?php

namespace Webkul\MasterDataManagement\Services;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Webkul\MasterDataManagement\Enums\ChangeOperation;
use Webkul\MasterDataManagement\Enums\RecordStatus;
use Webkul\MasterDataManagement\Events\RecordUpdated;
use Webkul\MasterDataManagement\Exceptions\StaleRecordException;
use Webkul\MasterDataManagement\Jobs\BackfillPrimaryHierarchy;
use Webkul\MasterDataManagement\Jobs\RewriteDescendantPaths;
use Webkul\MasterDataManagement\Models\EntityType;
use Webkul\MasterDataManagement\Models\Hierarchy;
use Webkul\MasterDataManagement\Models\HierarchyNode;
use Webkul\MasterDataManagement\Models\Record;
use Webkul\MasterDataManagement\Repositories\HierarchyNodeRepository;
use Webkul\MasterDataManagement\Repositories\HierarchyRepository;
use Webkul\MasterDataManagement\Repositories\RecordRepository;
use Webkul\MasterDataManagement\ValueObjects\ChangeSet;
use Webkul\User\Models\Admin;

/**
 * The only writer of a record's parent_id, path and depth. A path is the chain of ids from the root down to the
 * record, `/{root}/.../{id}/`, so a subtree is a prefix scan; moving a node rewrites its subtree on the queue.
 *
 * parent_id is authoritative and paths are derived: cycle checks and new paths walk the parent_id chain, since
 * stored paths below a moved record stay stale until its queued rewrite has run. The primary hierarchy's nodes mirror
 * these columns and follow every placement and rewrite.
 *
 * Alternate hierarchies keep their placement in nodes only. Writes to one alternate hierarchy run one at a time under
 * a lock of its row, and rewrite the moved subtree in batches inside the same transaction, so their stored paths are
 * always current and a cycle check reads them directly.
 */
class HierarchyService
{
    /**
     * The longest path the column holds.
     */
    public const int MAX_PATH_LENGTH = 255;

    /**
     * The deepest chain a path of that length can hold (`/1` per level), which also bounds the parent_id walk.
     */
    protected const int MAX_DEPTH = 127;

    public function __construct(
        protected RecordRepository $recordRepository,
        protected RecordIndexer $recordIndexer,
        protected HierarchyRepository $hierarchyRepository,
        protected HierarchyNodeRepository $hierarchyNodeRepository,
    ) {}

    /**
     * Place a newly inserted record under its parent, or at the root; call inside the create transaction.
     */
    public function placeNew(Record $record, ?Record $parent): void
    {
        $placement = $this->placement($record, $parent instanceof Record ? $this->chainFrom($parent->id, $record, true) : []);

        if (strlen($placement['path']) > self::MAX_PATH_LENGTH) {
            $this->fail('path-too-long');
        }

        $record->forceFill($placement)->saveQuietly();

        $this->syncPrimaryNode($record);
    }

    /**
     * Move a record under a new parent, or to the root, guarded by the revision the caller read.
     *
     * The record row changes in one transaction; its descendants' paths are rewritten after commit.
     *
     * @throws ValidationException
     * @throws StaleRecordException
     */
    public function move(Record $record, ?Record $newParent, int $expectedRevision, ?Admin $by = null): Record
    {
        $this->ensureMovable($record);

        $this->ensureValidParent($record->entityType, $newParent, $record);

        $before = $this->snapshot($record);

        $record = DB::transaction(function () use ($record, $newParent, $expectedRevision, $by): Record {
            throw_unless($this->recordRepository->claimRevision($record, $expectedRevision), StaleRecordException::class, $record, $expectedRevision);

            $record->refresh();

            $this->reparent($record, $newParent);

            $record->forceFill(['updated_by' => $by?->id])->save();

            return $record;
        });

        event(new RecordUpdated($record, new ChangeSet(
            ChangeOperation::Update,
            $record->entityType,
            $record,
            $before,
            array_replace($before, $this->snapshot($record)),
            $by,
            null,
            $expectedRevision,
        )));

        return $record;
    }

    /**
     * Assign the record's new placement without saving it, and queue the rewrite of its descendants for after commit.
     *
     * Call inside the transaction that holds the record's revision claim, after re-reading the record. The rows of
     * both parent_id chains are locked, and a move is refused while an earlier rewrite around the record is pending.
     *
     * @throws ValidationException
     */
    public function reparent(Record $record, ?Record $newParent): void
    {
        $newParent?->refresh();

        $this->ensureValidParent($record->entityType, $newParent, $record);

        $oldPath = (string) $record->path;

        $current = $this->placement($record, $record->parent_id === null ? [] : $this->chainFrom($record->parent_id, $record, true));

        if ($current['path'] !== $oldPath || $this->recordRepository->hasStaleDescendants($oldPath, $record->id)) {
            $this->fail('rewrite-pending');
        }

        $placement = $this->placement($record, $newParent instanceof Record ? $this->chainFrom($newParent->id, $record, true) : []);

        $longest = max(strlen($oldPath), $this->recordRepository->maxPathLength($oldPath, $record->id));

        if (strlen($placement['path']) + $longest - strlen($oldPath) > self::MAX_PATH_LENGTH) {
            $this->fail('path-too-long');
        }

        $oldDepth = $record->depth;

        $record->forceFill($placement);

        $this->syncPrimaryNode($record);

        if ($oldPath !== $record->path) {
            dispatch(new RewriteDescendantPaths($record->id, $oldPath, $record->path, $record->depth - $oldDepth))->afterCommit();
        }
    }

    /**
     * Validate that the parent may hold records of the entity type; a null parent places the record at the root.
     *
     * @throws ValidationException
     */
    public function ensureValidParent(EntityType $type, ?Record $parent, ?Record $record = null): void
    {
        if (! $type->is_hierarchical) {
            $this->fail('not-hierarchical');
        }

        if (! $parent instanceof Record) {
            return;
        }

        if (! in_array((int) $parent->entity_type_id, $this->allowedParentTypeIds($type), true)) {
            $this->fail('parent-type-not-allowed');
        }

        if ($record instanceof Record) {
            $this->chainFrom($parent->id, $record);
        }

        if (! in_array($parent->status, [RecordStatus::Draft, RecordStatus::Active], true)) {
            $this->fail('parent-not-active');
        }
    }

    /**
     * The entity type ids whose records may parent records of the type; an empty list allows the same type only.
     *
     * @return list<int>
     */
    public function allowedParentTypeIds(EntityType $type): array
    {
        $ids = array_values(array_unique(array_map(intval(...), (array) ($type->allowed_parent_type_ids ?? []))));

        return $ids === [] ? [(int) $type->id] : $ids;
    }

    /**
     * Rewrite the path prefix and shift the depth of every record below the old path, in batches.
     *
     * One UPDATE per batch replaces the leading old path with the new one, so the subtree keeps its shape, in the
     * records and in the primary hierarchy nodes that mirror them. The
     * target is the moved record's placement when the job runs, so two queued moves of one record land the same
     * way in either order; the given new path and delta only apply when the record is gone.
     */
    public function rewriteDescendantPaths(int $recordId, string $oldPath, string $newPath, int $depthDelta): void
    {
        $moved = $this->recordRepository->hierarchyRow($recordId);

        if ($moved instanceof Record && $moved->path !== null) {
            $newPath = $moved->path;

            $depthDelta = $moved->depth - (substr_count($oldPath, '/') - 2);
        }

        if ($newPath === $oldPath) {
            return;
        }

        $this->rewritePrefix(fn (): Builder => $this->recordRepository->pathPrefixQuery($oldPath, $recordId), $oldPath, $newPath, $depthDelta);

        $this->rewritePrefix(fn (): Builder => $this->hierarchyNodeRepository->primarySubtreeQuery($oldPath, $recordId), $oldPath, $newPath, $depthDelta);
    }

    /**
     * The primary hierarchy of the entity type, created on first use; a new one is filled from the records' own
     * placement on the queue.
     */
    public function ensurePrimary(EntityType $type): Hierarchy
    {
        [$hierarchy, $created] = $this->hierarchyRepository->firstOrCreatePrimary((int) $type->id);

        if ($created) {
            dispatch(new BackfillPrimaryHierarchy((int) $hierarchy->id))->afterCommit();
        }

        return $hierarchy;
    }

    /**
     * Copy the records' own parent_id, path and depth into the primary hierarchy's nodes in batches; a record the
     * hierarchy already places keeps its node.
     */
    public function backfillPrimary(Hierarchy $hierarchy): void
    {
        $this->recordRepository->chunkPlacementsForEntityType((int) $hierarchy->entity_type_id, $this->recordIndexer->batchSize(), function (Collection $records) use ($hierarchy): void {
            $this->hierarchyNodeRepository->insertMissing($records->map(fn (Record $record): array => [
                'hierarchy_id' => (int) $hierarchy->id,
                'record_id'    => (int) $record->id,
                'parent_id'    => $record->parent_id,
                'path'         => (string) $record->path,
                'depth'        => (int) $record->depth,
            ])->values()->all());
        });
    }

    /**
     * Place a record under a parent in an alternate hierarchy, or at its root, moving its subtree along.
     *
     * Both records belong to the hierarchy's entity type; a parent the hierarchy does not place yet goes to its root.
     *
     * @throws ValidationException
     */
    public function place(Hierarchy $hierarchy, Record $record, ?Record $parent): HierarchyNode
    {
        $this->ensureAlternate($hierarchy);

        $this->ensurePlaceable($hierarchy, $record, $parent);

        return DB::transaction(function () use ($hierarchy, $record, $parent): HierarchyNode {
            $this->hierarchyRepository->lockForWrite((int) $hierarchy->id);

            $node = $this->hierarchyNodeRepository->nodeFor((int) $hierarchy->id, (int) $record->id);

            $parentNode = $parent instanceof Record ? $this->nodeOrRoot($hierarchy, $parent) : null;

            if ($node instanceof HierarchyNode && $parentNode instanceof HierarchyNode && str_starts_with((string) $parentNode->path, (string) $node->path)) {
                $this->fail('cycle');
            }

            $path = ($parentNode?->path ?? '/').$record->id.'/';

            $depth = $parentNode instanceof HierarchyNode ? $parentNode->depth + 1 : 0;

            if (! $node instanceof HierarchyNode) {
                if (strlen($path) > self::MAX_PATH_LENGTH) {
                    $this->fail('path-too-long');
                }

                $this->hierarchyNodeRepository->put((int) $hierarchy->id, (int) $record->id, $parent?->id, $path, $depth);

                return $this->hierarchyNodeRepository->nodeFor((int) $hierarchy->id, (int) $record->id);
            }

            if ($node->path === $path) {
                return $node;
            }

            $oldPath = (string) $node->path;

            $limit = (int) config('masterdata.hierarchies.max_subtree', 10000);

            if ($this->hierarchyNodeRepository->subtreeSize((int) $hierarchy->id, $oldPath, (int) $record->id, $limit) > $limit) {
                $this->failAlternate('subtree-too-large', ['count' => $limit]);
            }

            $longest = max(strlen($oldPath), $this->hierarchyNodeRepository->maxPathLength((int) $hierarchy->id, $oldPath, (int) $record->id));

            if (strlen($path) + $longest - strlen($oldPath) > self::MAX_PATH_LENGTH) {
                $this->fail('path-too-long');
            }

            $this->moveNode($node, $parent?->id, $path, $depth);

            return $node;
        });
    }

    /**
     * Take a record out of an alternate hierarchy; its children move up to its parent, or to the root.
     *
     * @throws ValidationException
     */
    public function remove(Hierarchy $hierarchy, Record $record): void
    {
        $this->ensureAlternate($hierarchy);

        DB::transaction(function () use ($hierarchy, $record): void {
            $this->hierarchyRepository->lockForWrite((int) $hierarchy->id);

            $node = $this->hierarchyNodeRepository->nodeFor((int) $hierarchy->id, (int) $record->id);

            if ($node instanceof HierarchyNode) {
                $this->detachNode($node);
            }
        });
    }

    /**
     * Hand the merged record's alternate placements to the survivor; call inside the merge transaction.
     *
     * Where the survivor is not placed, it takes the merged record's node. Where it is, the merged record's children
     * move below the survivor, which first leaves the merged record's subtree when it sits inside it.
     */
    public function foldInto(Record $merged, Record $survivor): void
    {
        foreach ($this->hierarchyNodeRepository->forRecords([(int) $merged->id]) as $placed) {
            $hierarchyId = (int) $placed->hierarchy_id;

            $this->hierarchyRepository->lockForWrite($hierarchyId);

            $node = $this->hierarchyNodeRepository->nodeFor($hierarchyId, (int) $merged->id);

            if (! $node instanceof HierarchyNode) {
                continue;
            }

            $oldPath = (string) $node->path;

            $target = $this->hierarchyNodeRepository->nodeFor($hierarchyId, (int) $survivor->id);

            if (! $target instanceof HierarchyNode) {
                $path = $this->parentPath($node).$survivor->id.'/';

                $this->rewritePrefix(fn (): Builder => $this->hierarchyNodeRepository->subtreeQuery($hierarchyId, $oldPath, (int) $merged->id), $oldPath, $path, 0);

                $this->hierarchyNodeRepository->repointChildren($hierarchyId, (int) $merged->id, (int) $survivor->id);

                $node->forceFill(['record_id' => $survivor->id, 'path' => $path])->save();

                continue;
            }

            if (str_starts_with((string) $target->path, $oldPath)) {
                $this->moveNode($target, $node->parent_id, $this->parentPath($node).$survivor->id.'/', (int) $node->depth);
            }

            $this->rewritePrefix(fn (): Builder => $this->hierarchyNodeRepository->subtreeQuery($hierarchyId, $oldPath, (int) $merged->id), $oldPath, (string) $target->path, $target->depth - $node->depth);

            $this->hierarchyNodeRepository->repointChildren($hierarchyId, (int) $merged->id, (int) $survivor->id);

            $this->hierarchyNodeRepository->deleteNode((int) $node->id);
        }
    }

    /**
     * Take the records out of every alternate hierarchy, lifting their children to their parents; call inside the
     * erase transaction. The primary hierarchy keeps an erased tombstone where it was.
     *
     * @param  list<int>  $recordIds
     */
    public function detachRecords(array $recordIds): void
    {
        foreach ($this->hierarchyNodeRepository->forRecords($recordIds) as $placed) {
            $this->hierarchyRepository->lockForWrite((int) $placed->hierarchy_id);

            $node = $this->hierarchyNodeRepository->nodeFor((int) $placed->hierarchy_id, (int) $placed->record_id);

            if ($node instanceof HierarchyNode) {
                $this->detachNode($node);
            }
        }
    }

    /**
     * Mirror the record's placement into its entity type's primary hierarchy, creating it for a hierarchical type.
     */
    protected function syncPrimaryNode(Record $record): void
    {
        $hierarchy = $this->hierarchyRepository->primaryFor((int) $record->entity_type_id);

        if (! $hierarchy instanceof Hierarchy) {
            $type = $record->entityType;

            if (! $type instanceof EntityType || ! $type->is_hierarchical) {
                return;
            }

            $hierarchy = $this->ensurePrimary($type);
        }

        $this->hierarchyNodeRepository->put((int) $hierarchy->id, (int) $record->id, $record->parent_id, (string) $record->path, (int) $record->depth);
    }

    /**
     * Move a node and its subtree to the placement, rewriting the descendants' paths in batches first.
     */
    protected function moveNode(HierarchyNode $node, ?int $parentId, string $path, int $depth): void
    {
        $oldPath = (string) $node->path;

        $hierarchyId = (int) $node->hierarchy_id;

        $recordId = (int) $node->record_id;

        $this->rewritePrefix(fn (): Builder => $this->hierarchyNodeRepository->subtreeQuery($hierarchyId, $oldPath, $recordId), $oldPath, $path, $depth - (int) $node->depth);

        $node->forceFill(['parent_id' => $parentId, 'path' => $path, 'depth' => $depth])->save();
    }

    /**
     * Delete a node after lifting its subtree one level, below the node's parent or to the root.
     */
    protected function detachNode(HierarchyNode $node): void
    {
        $oldPath = (string) $node->path;

        $hierarchyId = (int) $node->hierarchy_id;

        $recordId = (int) $node->record_id;

        $this->rewritePrefix(fn (): Builder => $this->hierarchyNodeRepository->subtreeQuery($hierarchyId, $oldPath, $recordId), $oldPath, $this->parentPath($node), -1);

        $this->hierarchyNodeRepository->repointChildren($hierarchyId, $recordId, $node->parent_id);

        $this->hierarchyNodeRepository->deleteNode((int) $node->id);
    }

    /**
     * The node of the parent record in the hierarchy, placing it at the root when the hierarchy does not hold it.
     */
    protected function nodeOrRoot(Hierarchy $hierarchy, Record $parent): HierarchyNode
    {
        $node = $this->hierarchyNodeRepository->nodeFor((int) $hierarchy->id, (int) $parent->id);

        if ($node instanceof HierarchyNode) {
            return $node;
        }

        $this->hierarchyNodeRepository->put((int) $hierarchy->id, (int) $parent->id, null, '/'.$parent->id.'/', 0);

        return $this->hierarchyNodeRepository->nodeFor((int) $hierarchy->id, (int) $parent->id);
    }

    /**
     * The path of the node's parent: its own path without the last segment, `/` at the root.
     */
    protected function parentPath(HierarchyNode $node): string
    {
        return substr((string) $node->path, 0, -strlen($node->record_id.'/'));
    }

    /**
     * Swap the old path prefix for the new one on every row the query reaches, one UPDATE per batch.
     *
     * @param  Closure(): Builder  $query  Builds the rows below the old path afresh for each batch.
     */
    protected function rewritePrefix(Closure $query, string $oldPath, string $newPath, int $depthDelta): void
    {
        if ($oldPath === $newPath && $depthDelta === 0) {
            return;
        }

        $query()
            ->select('id')
            ->chunkById($this->recordIndexer->batchSize(), function (Collection $rows) use ($query, $oldPath, $newPath, $depthDelta): void {
                $this->updatePaths($query()->whereKey($rows->modelKeys()), $newPath, strlen($oldPath) + 1, $depthDelta);
            });
    }

    /**
     * Refuse writing a primary hierarchy's nodes directly; its placement follows the records' parent.
     *
     * @throws ValidationException
     */
    protected function ensureAlternate(Hierarchy $hierarchy): void
    {
        if ($hierarchy->is_primary) {
            $this->failAlternate('primary');
        }
    }

    /**
     * Refuse placing a record of another entity type, a record out of the active lifecycle, itself, or below a
     * parent of another type or out of the active lifecycle.
     *
     * @throws ValidationException
     */
    protected function ensurePlaceable(Hierarchy $hierarchy, Record $record, ?Record $parent): void
    {
        if ((int) $record->entity_type_id !== (int) $hierarchy->entity_type_id) {
            $this->failAlternate('wrong-type');
        }

        $this->ensureMovable($record);

        if (! $parent instanceof Record) {
            return;
        }

        if ((int) $parent->id === (int) $record->id) {
            $this->fail('cycle');
        }

        if ((int) $parent->entity_type_id !== (int) $hierarchy->entity_type_id) {
            $this->failAlternate('wrong-type');
        }

        if (! in_array($parent->status, [RecordStatus::Draft, RecordStatus::Active], true)) {
            $this->fail('parent-not-active');
        }
    }

    /**
     * Run one UPDATE that swaps each row's path prefix for the new path and shifts its depth.
     *
     * The statement is compiled by the connection's grammar so the table prefix and identifier quoting apply.
     * PostgreSQL gets explicit casts because an untyped parameter would pick the regex form of SUBSTRING.
     */
    protected function updatePaths(Builder $query, string $newPath, int $offset, int $depthDelta): void
    {
        $base = $query->toBase();

        $grammar = $base->getGrammar();

        $path = $grammar->wrap('path');

        $expression = $base->getConnection()->getDriverName() === 'pgsql'
            ? 'CAST(? AS VARCHAR) || SUBSTRING('.$path.' FROM CAST(? AS INTEGER))'
            : 'CONCAT(?, SUBSTRING('.$path.', ?))';

        $sql = $grammar->compileUpdate($base, [
            'path'  => DB::raw($expression),
            'depth' => DB::raw($grammar->wrap('depth').' + ?'),
        ]);

        $base->getConnection()->update($sql, [$newPath, $offset, $depthDelta, ...$grammar->prepareBindingsForUpdate($base->bindings, [])]);
    }

    /**
     * The parent_id, path and depth of a record placed below the ancestor chain (root first), or at the root.
     *
     * @param  list<int>  $chain
     * @return array{parent_id: ?int, path: string, depth: int}
     */
    protected function placement(Record $record, array $chain): array
    {
        return [
            'parent_id' => $chain === [] ? null : $chain[array_key_last($chain)],
            'path'      => '/'.implode('/', [...$chain, $record->id]).'/',
            'depth'     => count($chain),
        ];
    }

    /**
     * Walk the parent_id chain up from the given record id and return its ids root first.
     *
     * Reaching the record means the move would close a cycle. The walk reads one row per level and stops at
     * the root, a missing row or the deepest level a path can hold.
     *
     * @return list<int>
     *
     * @throws ValidationException
     */
    protected function chainFrom(int $id, Record $record, bool $lock = false): array
    {
        $chain = [];

        for ($next = $id; $next !== null;) {
            if ($next === $record->id) {
                $this->fail('cycle');
            }

            if (count($chain) >= self::MAX_DEPTH) {
                $this->fail('path-too-long');
            }

            $row = $this->recordRepository->hierarchyRow($next, $lock);

            if (! $row instanceof Record) {
                break;
            }

            $chain[] = $next;

            $next = $row->parent_id;
        }

        return array_reverse($chain);
    }

    /**
     * Refuse moving records that left the active lifecycle.
     *
     * @throws ValidationException
     */
    protected function ensureMovable(Record $record): void
    {
        if (in_array($record->status, [RecordStatus::Retired, RecordStatus::Merged, RecordStatus::Erased], true)) {
            throw ValidationException::withMessages(['status' => trans('masterdata::app.records.not-editable')]);
        }
    }

    /**
     * Capture the hierarchy attributes a move change set compares.
     *
     * @return array{parent_id: ?int, path: ?string, depth: int}
     */
    protected function snapshot(Record $record): array
    {
        return [
            'parent_id' => $record->parent_id,
            'path'      => $record->path,
            'depth'     => (int) $record->depth,
        ];
    }

    /**
     * @throws ValidationException
     */
    protected function fail(string $reason): never
    {
        throw ValidationException::withMessages(['parent' => trans('masterdata::app.hierarchy.validation.'.$reason)]);
    }

    /**
     * @param  array<string, int|string>  $replace
     *
     * @throws ValidationException
     */
    protected function failAlternate(string $reason, array $replace = []): never
    {
        throw ValidationException::withMessages(['parent' => trans('masterdata::app.hierarchies.validation.'.$reason, $replace)]);
    }
}
