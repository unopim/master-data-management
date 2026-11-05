<?php

namespace Webkul\MasterDataManagement\Security;

use Webkul\MasterDataManagement\Enums\GrantAction;
use Webkul\MasterDataManagement\Models\Record;
use Webkul\MasterDataManagement\Repositories\AccessGrantRepository;
use Webkul\MasterDataManagement\Repositories\EntityTypeRepository;
use Webkul\User\Models\Admin;

/**
 * Where an admin may act on records of scoped entity types: the union of the subtrees granted to their role and to
 * them directly, matched by materialised path. A full-access role reaches every
 * record; anyone else without a matching grant reaches no scoped record. API robots resolve as their own admin.
 * Bound scoped, so what is memoised here lives for one request or job only.
 */
class AccessGrantResolver
{
    /**
     * The key under which subtrees granted on every scoped type are kept.
     */
    public const string ANY_TYPE = '*';

    /**
     * @var list<int>|null
     */
    protected ?array $scopedTypeIds = null;

    /**
     * @var array<int, array<string, array<int|string, list<string>>>> Collapsed paths by admin id, action, then type id.
     */
    protected array $paths = [];

    public function __construct(
        protected AccessGrantRepository $accessGrantRepository,
        protected EntityTypeRepository $entityTypeRepository,
    ) {}

    /**
     * Whether the admin reaches every record regardless of grants.
     */
    public function bypasses(Admin $admin): bool
    {
        return $admin->role?->permission_type === 'all';
    }

    /**
     * The ids of the entity types whose records are restricted to granted subtrees.
     *
     * @return list<int>
     */
    public function scopedEntityTypeIds(): array
    {
        return $this->scopedTypeIds ??= $this->entityTypeRepository->scopedIds();
    }

    /**
     * Whether the records of the entity type are restricted to granted subtrees.
     */
    public function isScoped(int $entityTypeId): bool
    {
        return in_array($entityTypeId, $this->scopedEntityTypeIds(), true);
    }

    /**
     * The subtree paths where the admin may perform the action on records of the entity type.
     *
     * @return list<string>
     */
    public function paths(Admin $admin, GrantAction $action, int $entityTypeId): array
    {
        $byType = $this->pathsByType($admin, $action);

        return $this->collapse([...$byType[self::ANY_TYPE] ?? [], ...$byType[$entityTypeId] ?? []]);
    }

    /**
     * The subtree paths where the admin may perform the action, keyed by entity type id or `ANY_TYPE`.
     *
     * @return array<int|string, list<string>>
     */
    public function pathsByType(Admin $admin, GrantAction $action): array
    {
        return $this->pathsOf($admin)[$action->value] ?? [];
    }

    /**
     * Whether the admin may perform the action on the record; an unsaved subject checks the type only.
     *
     * A stored record read without its path cannot be placed, so it is refused.
     */
    public function allows(Admin $admin, GrantAction $action, Record $record): bool
    {
        if (! $record->exists || $this->bypasses($admin) || ! $this->isScoped((int) $record->entity_type_id)) {
            return true;
        }

        $path = $record->getAttributes()['path'] ?? null;

        return is_string($path) && $this->within($path, $this->paths($admin, $action, (int) $record->entity_type_id));
    }

    /**
     * Whether the admin may perform the action on a record of the entity type placed below the parent, or at the root
     * without one; only a bypassing admin may act at the root of a scoped type.
     */
    public function allowsUnder(Admin $admin, GrantAction $action, int $entityTypeId, ?Record $parent): bool
    {
        if ($this->bypasses($admin) || ! $this->isScoped($entityTypeId)) {
            return true;
        }

        return $parent instanceof Record
            && $this->within((string) $parent->path, $this->paths($admin, $action, $entityTypeId));
    }

    /**
     * Drop the memoised types and paths, after grants or scoped types changed within the current request.
     */
    public function flush(): void
    {
        $this->scopedTypeIds = null;

        $this->paths = [];
    }

    /**
     * Whether the path lies at or below one of the subtree paths.
     *
     * @param  list<string>  $paths
     */
    protected function within(string $path, array $paths): bool
    {
        if ($path === '') {
            return false;
        }

        return array_any($paths, fn (string $prefix): bool => str_starts_with($path, $prefix));
    }

    /**
     * Resolve the admin's principals and load their grants once, spreading each grant over the actions it implies.
     *
     * @return array<string, array<int|string, list<string>>>
     */
    protected function pathsOf(Admin $admin): array
    {
        $adminId = (int) $admin->id;

        if (isset($this->paths[$adminId])) {
            return $this->paths[$adminId];
        }

        $grants = $this->accessGrantRepository->subtreesFor(
            $admin->role_id === null ? null : (int) $admin->role_id,
            $adminId,
        );

        $paths = [];

        foreach ($grants as $grant) {
            $type = $grant['entity_type_id'] ?? self::ANY_TYPE;

            foreach ($grant['actions'] as $value) {
                foreach (GrantAction::from($value)->implied() as $action) {
                    $paths[$action->value][$type][] = $grant['path'];
                }
            }
        }

        foreach ($paths as $action => $byType) {
            $paths[$action] = array_map($this->collapse(...), $byType);
        }

        return $this->paths[$adminId] = $paths;
    }

    /**
     * Keep only the outermost paths, once each.
     *
     * @param  list<string>  $paths
     * @return list<string>
     */
    protected function collapse(array $paths): array
    {
        $paths = array_values(array_unique(array_filter($paths, fn (string $path): bool => $path !== '')));

        usort($paths, fn (string $a, string $b): int => strlen($a) <=> strlen($b));

        $kept = [];

        foreach ($paths as $path) {
            if (! $this->within($path, $kept)) {
                $kept[] = $path;
            }
        }

        return $kept;
    }
}
