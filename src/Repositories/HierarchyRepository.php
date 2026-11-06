<?php

namespace Webkul\MasterDataManagement\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Webkul\Core\Eloquent\Repository;
use Webkul\Core\RequestMemo;
use Webkul\MasterDataManagement\Contracts\Hierarchy;
use Webkul\MasterDataManagement\Models\Hierarchy as HierarchyModel;

class HierarchyRepository extends Repository
{
    /**
     * The most hierarchies one entity type holds, the primary included.
     */
    public const int MAX_HIERARCHIES = 20;

    /**
     * Get model class name.
     */
    public function model(): string
    {
        return Hierarchy::class;
    }

    /**
     * The hierarchies of the entity type with their labels, the primary first, then by code.
     *
     * @return Collection<int, HierarchyModel>
     */
    public function forEntityType(int $entityTypeId): Collection
    {
        return $this->model->newQuery()
            ->with('translations')
            ->where('entity_type_id', $entityTypeId)
            ->orderByDesc('is_primary')
            ->orderBy('code')
            ->limit(self::MAX_HIERARCHIES)
            ->get();
    }

    /**
     * The alternate hierarchies of the entity type with their labels, by code.
     *
     * @return Collection<int, HierarchyModel>
     */
    public function alternatesFor(int $entityTypeId): Collection
    {
        return $this->forEntityType($entityTypeId)->reject(fn (HierarchyModel $hierarchy): bool => $hierarchy->is_primary)->values();
    }

    /**
     * Find a hierarchy of the entity type.
     */
    public function findFor(int $entityTypeId, int $id): ?HierarchyModel
    {
        return $this->model->newQuery()
            ->with('translations')
            ->where('entity_type_id', $entityTypeId)
            ->find($id);
    }

    /**
     * Find a hierarchy of the entity type by its code.
     */
    public function findByCode(int $entityTypeId, string $code): ?HierarchyModel
    {
        return $this->model->newQuery()
            ->with('translations')
            ->where('entity_type_id', $entityTypeId)
            ->where('code', $code)
            ->first();
    }

    /**
     * The primary hierarchy of the entity type, memoised for the request; null until it is created.
     */
    public function primaryFor(int $entityTypeId): ?HierarchyModel
    {
        $key = 'masterdata.primary_hierarchy.'.$entityTypeId;

        $memo = resolve(RequestMemo::class);

        if ($memo->has($key)) {
            return $memo->get($key);
        }

        $hierarchy = $this->model->newQuery()->where('primary_entity_type_id', $entityTypeId)->first();

        $memo->set($key, $hierarchy);

        return $hierarchy;
    }

    /**
     * Read the primary hierarchy of the entity type, creating it on first use; the flag tells whether it was created.
     *
     * Idempotent: the primary slot is unique, so a concurrent first call reads the row the other call created. The
     * insert runs in its own savepoint so a lost race does not abort an enclosing PostgreSQL transaction.
     *
     * @return array{0: HierarchyModel, 1: bool}
     */
    public function firstOrCreatePrimary(int $entityTypeId): array
    {
        $existing = $this->primaryFor($entityTypeId);

        if ($existing instanceof HierarchyModel) {
            return [$existing, false];
        }

        try {
            $hierarchy = DB::transaction(fn (): HierarchyModel => $this->model->newQuery()->forceCreate([
                'entity_type_id'         => $entityTypeId,
                'code'                   => HierarchyModel::PRIMARY_CODE,
                'is_primary'             => true,
                'primary_entity_type_id' => $entityTypeId,
            ]));
        } catch (UniqueConstraintViolationException) {
            $hierarchy = $this->model->newQuery()->useWritePdo()->where('primary_entity_type_id', $entityTypeId)->firstOrFail();
        }

        resolve(RequestMemo::class)->set('masterdata.primary_hierarchy.'.$entityTypeId, $hierarchy);

        return [$hierarchy, $hierarchy->wasRecentlyCreated];
    }

    /**
     * Whether another hierarchy of the entity type already uses the code.
     */
    public function codeTaken(int $entityTypeId, string $code, ?int $ignoreId = null): bool
    {
        return $this->model->newQuery()
            ->where('entity_type_id', $entityTypeId)
            ->where('code', $code)
            ->when($ignoreId !== null, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists();
    }

    /**
     * Count the hierarchies of the entity type, the primary included.
     */
    public function countFor(int $entityTypeId): int
    {
        return $this->model->newQuery()->where('entity_type_id', $entityTypeId)->count();
    }

    /**
     * Lock the hierarchy row so writes to one alternate hierarchy run one at a time; call inside a transaction.
     */
    public function lockForWrite(int $id): void
    {
        $this->model->newQuery()->whereKey($id)->lockForUpdate()->value('id');
    }
}
