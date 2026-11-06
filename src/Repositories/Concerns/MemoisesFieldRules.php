<?php

namespace Webkul\MasterDataManagement\Repositories\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Webkul\Core\RequestMemo;
use Webkul\MasterDataManagement\Models\FieldProxy;

/**
 * Lists the rows an entity type's fields own, by `field_id`, memoised per request and dropped on every write.
 *
 * The memo is resolved per call, so a long-lived repository never reads another request's memo.
 */
trait MemoisesFieldRules
{
    /**
     * The memo key prefix of the rows of one entity type.
     */
    abstract protected function memoPrefix(): string;

    /**
     * The rows of the entity type's fields in their sort order.
     *
     * @return Collection<int, Model>
     */
    public function forEntityType(int $entityTypeId): Collection
    {
        $key = $this->memoPrefix().$entityTypeId;

        $memo = resolve(RequestMemo::class);

        if ($memo->has($key)) {
            return $memo->get($key);
        }

        $rows = $this->scopedQuery($entityTypeId)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->limit(self::MAX_ROWS)
            ->get();

        $memo->set($key, $rows);

        return $rows;
    }

    /**
     * Find a row owned by a field of the entity type.
     */
    public function findFor(int $entityTypeId, int $id): ?Model
    {
        return $this->scopedQuery($entityTypeId)->find($id);
    }

    /**
     * Count the rows owned by the fields of the entity type.
     */
    public function countFor(int $entityTypeId): int
    {
        return $this->scopedQuery($entityTypeId)->count();
    }

    /**
     * Create a row and drop the memoised rows of its entity type.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Model
    {
        $row = parent::create($attributes);

        $this->forgetMemo();

        return $row;
    }

    /**
     * Update a row and drop the memoised rows of its entity type.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function update(array $attributes, $id): Model
    {
        $row = parent::update($attributes, $id);

        $this->forgetMemo();

        return $row;
    }

    /**
     * Delete a row and drop the memoised rows of its entity type.
     */
    public function delete($id): bool
    {
        $deleted = (bool) parent::delete($id);

        $this->forgetMemo();

        return $deleted;
    }

    /**
     * Drop the memoised rows of every entity type, as a write may move a row to a field of another type.
     */
    public function forgetMemo(): void
    {
        resolve(RequestMemo::class)->forget($this->memoPrefix());
    }

    /**
     * The rows owned by the active and archived fields of the entity type.
     *
     * @return Builder<Model>
     */
    protected function scopedQuery(int $entityTypeId)
    {
        return $this->model->newQuery()->whereIn(
            'field_id',
            FieldProxy::modelClass()::query()->select('id')->where('entity_type_id', $entityTypeId),
        );
    }
}
