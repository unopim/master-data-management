<?php

namespace Webkul\MasterDataManagement\Repositories;

use Illuminate\Support\Collection;
use Webkul\Core\Eloquent\Repository;
use Webkul\MasterDataManagement\Contracts\Icon;
use Webkul\MasterDataManagement\Models\EntityTypeProxy;

class IconRepository extends Repository
{
    /**
     * Get model class name.
     */
    public function model(): string
    {
        return Icon::class;
    }

    /**
     * Count the entity types that show the icon.
     */
    public function usageCount(int $id): int
    {
        return EntityTypeProxy::modelClass()::query()->where('icon_id', $id)->count();
    }

    /**
     * Get every icon ordered by name, for the entity type icon select.
     *
     * @return Collection<int, \Webkul\MasterDataManagement\Models\Icon>
     */
    public function allByName(): Collection
    {
        return $this->model->newQuery()->orderBy('name')->get(['id', 'code', 'name', 'path']);
    }
}
