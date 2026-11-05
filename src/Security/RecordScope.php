<?php

namespace Webkul\MasterDataManagement\Security;

use Illuminate\Contracts\Database\Query\Builder as BuilderContract;
use Illuminate\Database\Eloquent\Builder;
use Webkul\MasterDataManagement\Enums\GrantAction;
use Webkul\User\Models\Admin;

/**
 * Restricts record reads to the subtrees granted to the admin on scoped entity types, through AccessGrantResolver.
 */
class RecordScope
{
    public function __construct(protected AccessGrantResolver $accessGrantResolver) {}

    /**
     * Restrict a record query to the rows the admin may see for the entity type.
     */
    public function apply(Builder $query, Admin $admin, int $entityTypeId): Builder
    {
        if ($this->accessGrantResolver->bypasses($admin) || ! $this->accessGrantResolver->isScoped($entityTypeId)) {
            return $query;
        }

        return $this->wherePathWithin($query, $query->qualifyColumn('path'), $this->accessGrantResolver->paths($admin, GrantAction::View, $entityTypeId));
    }

    /**
     * Restrict a record query that spans entity types, such as a hierarchy read, to the rows the admin may see.
     */
    public function applyAcrossTypes(Builder $query, Admin $admin): Builder
    {
        $this->applyToRows($query, $admin, $query->qualifyColumn('entity_type_id'), $query->qualifyColumn('path'));

        return $query;
    }

    /**
     * Restrict rows that carry or join a record, such as inbox rows, to those whose record lies where the admin may
     * perform the action. When the record id column is given, rows without a record stay visible.
     *
     * @template TBuilder of BuilderContract
     *
     * @param  TBuilder  $query
     * @return TBuilder
     */
    public function applyToRows(BuilderContract $query, Admin $admin, string $typeColumn, string $pathColumn, ?string $recordIdColumn = null, GrantAction $action = GrantAction::View): BuilderContract
    {
        $scoped = $this->accessGrantResolver->scopedEntityTypeIds();

        if ($scoped === [] || $this->accessGrantResolver->bypasses($admin)) {
            return $query;
        }

        $byType = $this->accessGrantResolver->pathsByType($admin, $action);

        return $query->where(function (BuilderContract $rows) use ($scoped, $byType, $typeColumn, $pathColumn, $recordIdColumn): void {
            $rows->whereNotIn($typeColumn, $scoped);

            if ($recordIdColumn !== null) {
                $rows->orWhereNull($recordIdColumn);
            }

            foreach ($byType as $type => $paths) {
                if ($type === AccessGrantResolver::ANY_TYPE) {
                    $rows->orWhere(fn (BuilderContract $within): BuilderContract => $this->wherePathWithin($within, $pathColumn, $paths));
                } elseif (in_array($type, $scoped, true)) {
                    $rows->orWhere(fn (BuilderContract $within): BuilderContract => $this->wherePathWithin($within->where($typeColumn, $type), $pathColumn, $paths));
                }
            }
        });
    }

    /**
     * Constrain the path column to the subtrees; no subtree matches nothing.
     *
     * Stored paths hold only digits and slashes, so they need no LIKE escaping.
     *
     * @template TBuilder of BuilderContract
     *
     * @param  TBuilder  $query
     * @param  list<string>  $paths
     * @return TBuilder
     */
    protected function wherePathWithin(BuilderContract $query, string $pathColumn, array $paths): BuilderContract
    {
        if ($paths === []) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (BuilderContract $within) use ($paths, $pathColumn): void {
            foreach ($paths as $path) {
                $within->orWhere($pathColumn, 'like', $path.'%');
            }
        });
    }
}
