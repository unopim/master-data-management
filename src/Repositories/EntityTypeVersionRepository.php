<?php

namespace Webkul\MasterDataManagement\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Webkul\Core\Eloquent\Repository;
use Webkul\MasterDataManagement\Contracts\EntityTypeVersion;
use Webkul\MasterDataManagement\Models\EntityTypeVersion as EntityTypeVersionModel;

class EntityTypeVersionRepository extends Repository
{
    /**
     * Get model class name.
     */
    public function model(): string
    {
        return EntityTypeVersion::class;
    }

    /**
     * Get the given published versions of an entity type keyed by version, each with its publisher's name.
     *
     * @param  list<int>  $versions
     * @return Collection<int, EntityTypeVersionModel>
     */
    public function publications(int $entityTypeId, array $versions): Collection
    {
        if ($versions === []) {
            return $this->model->newCollection();
        }

        return $this->model->newQuery()
            ->leftJoin('admins', 'admins.id', '=', 'mdm_entity_type_versions.published_by')
            ->where('mdm_entity_type_versions.entity_type_id', $entityTypeId)
            ->whereIn('mdm_entity_type_versions.version', $versions)
            ->get([
                'mdm_entity_type_versions.version',
                'mdm_entity_type_versions.published_at',
                'admins.name as publisher',
            ])
            ->keyBy('version');
    }
}
