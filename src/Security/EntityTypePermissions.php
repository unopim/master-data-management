<?php

namespace Webkul\MasterDataManagement\Security;

use Webkul\MasterDataManagement\Enums\RecordAction;
use Webkul\MasterDataManagement\Repositories\RoleEntityPermissionRepository;
use Webkul\User\Models\Admin;

/**
 * Per-entity-type record grants of the admin's role, or of the API key for a role-less robot.
 * Bound scoped, so the grants memoised here live for one request or job only.
 */
class EntityTypePermissions
{
    /**
     * @var array<int, array<int, list<string>>> Granted actions by role id, then entity type id.
     */
    protected array $grants = [];

    public function __construct(protected RoleEntityPermissionRepository $roleEntityPermissionRepository) {}

    /**
     * Whether the admin may perform the action on records of the entity type.
     */
    public function allows(Admin $admin, int $entityTypeId, RecordAction $action): bool
    {
        $grants = $this->grantsOf($admin);

        return $grants === null || in_array($action->value, $grants[$entityTypeId] ?? [], true);
    }

    /**
     * The entity type ids whose records the admin may view.
     *
     * @return list<int>|null Null when every entity type is visible.
     */
    public function visibleEntityTypeIds(Admin $admin): ?array
    {
        $grants = $this->grantsOf($admin);

        if ($grants === null) {
            return null;
        }

        return array_keys(array_filter(
            $grants,
            fn (array $actions): bool => in_array(RecordAction::View->value, $actions, true)
        ));
    }

    /**
     * Drop the memoised grants, after they were changed within the current request.
     */
    public function flush(): void
    {
        $this->grants = [];
    }

    /**
     * Resolve the admin's grants by entity type id, or null when every entity type is allowed.
     *
     * A role with every permission needs no grant and an admin without a role has none. Role-less
     * API robots take their access from the API key until per-key grants exist: an `all` key
     * reaches every entity type, a custom key none.
     *
     * @return array<int, list<string>>|null
     */
    protected function grantsOf(Admin $admin): ?array
    {
        $role = $admin->role;

        if (! $role) {
            return $admin->isApiUser() && $admin->apiKey?->permission_type === 'all' ? null : [];
        }

        if ($role->permission_type === 'all') {
            return null;
        }

        return $this->grantsFor((int) $role->id);
    }

    /**
     * Load a role's grants once per instance.
     *
     * @return array<int, list<string>>
     */
    protected function grantsFor(int $roleId): array
    {
        return $this->grants[$roleId] ??= $this->roleEntityPermissionRepository->grantsForRole($roleId);
    }
}
