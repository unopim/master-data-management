<?php

namespace Webkul\MasterDataManagement\Services;

use Illuminate\Support\Collection;
use Webkul\MasterDataManagement\Models\EntityType;
use Webkul\MasterDataManagement\Repositories\EntityTypeRepository;

/**
 * Describes where each entity type sits in the hierarchy, for the record cards.
 */
class EntityTypePlacement
{
    public function __construct(protected EntityTypeRepository $entityTypeRepository) {}

    /**
     * The placement label of every given type keyed by id: the parent types it nests inside, top level for a
     * hierarchical type without other parents, or standalone. Parents missing from the list are named in one query.
     *
     * @param  Collection<int, EntityType>  $types
     * @return array<int, string>
     */
    public function labels(Collection $types): array
    {
        $names = $types->mapWithKeys(fn (EntityType $type): array => [(int) $type->id => (string) ($type->name ?: $type->code)])->all();

        $parentIds = $types
            ->map(fn (EntityType $type): array => $this->parentIds($type))
            ->flatten()
            ->unique()
            ->reject(fn (int $id): bool => isset($names[$id]))
            ->values()
            ->all();

        $names += $this->entityTypeRepository->namesByIds($parentIds);

        return $types->mapWithKeys(function (EntityType $type) use ($names): array {
            $parents = array_values(array_filter(array_map(fn (int $id): ?string => $names[$id] ?? null, $this->parentIds($type))));

            $label = match (true) {
                $parents !== []        => trans('masterdata::app.dashboard.inside', ['types' => implode(', ', $parents)]),
                $type->is_hierarchical => trans('masterdata::app.dashboard.top-level'),
                default                => trans('masterdata::app.dashboard.standalone'),
            };

            return [(int) $type->id => $label];
        })->all();
    }

    /**
     * The type's allowed parent type ids, without the type itself.
     *
     * @return list<int>
     */
    protected function parentIds(EntityType $type): array
    {
        return array_values(array_unique(array_diff(
            array_map(intval(...), array_filter((array) $type->allowed_parent_type_ids, is_numeric(...))),
            [(int) $type->id],
        )));
    }
}
