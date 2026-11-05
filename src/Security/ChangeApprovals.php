<?php

namespace Webkul\MasterDataManagement\Security;

use Webkul\MasterDataManagement\Enums\GrantAction;
use Webkul\MasterDataManagement\Enums\RecordAction;
use Webkul\MasterDataManagement\Models\ChangeRequest;
use Webkul\MasterDataManagement\Models\EntityType;
use Webkul\MasterDataManagement\Models\Record;
use Webkul\MasterDataManagement\Repositories\RoleEntityPermissionRepository;
use Webkul\User\Models\Admin;
use Webkul\User\Models\Role;
use Webkul\User\Repositories\AdminRepository;
use Webkul\User\Repositories\RoleRepository;

/**
 * Who writes an entity type's records straight away and who decides the changes held for approval: admins with the
 * static approve ACL key (or a full-access role) who are either the type's data owner or hold the role's approve
 * grant on the type. Ownership alone never bypasses, so whoever may set the owner cannot skip approval by naming
 * themselves. Nobody decides their own request. API robots are not exempt from the ACL key, so their writes are
 * held like anyone else's. On a scoped entity type the approver also needs an approve grant on a subtree holding the
 * record, or the proposed parent of a create.
 */
class ChangeApprovals
{
    public function __construct(
        protected EntityTypePermissions $entityTypePermissions,
        protected RoleRepository $roleRepository,
        protected AdminRepository $adminRepository,
        protected RoleEntityPermissionRepository $roleEntityPermissionRepository,
        protected AccessGrantResolver $accessGrantResolver,
    ) {}

    /**
     * Whether the admin's writes to the entity type skip approval and the admin may approve others' changes.
     */
    public function bypasses(Admin $admin, EntityType $type): bool
    {
        $hasKey = $admin->role?->permission_type === 'all' || (bool) $admin->hasPermission(RecordAction::Approve->aclKey());

        if (! $hasKey) {
            return false;
        }

        if ($type->data_owner_id !== null && $type->data_owner_id === (int) $admin->id) {
            return true;
        }

        return $this->entityTypePermissions->allows($admin, (int) $type->id, RecordAction::Approve);
    }

    /**
     * Whether a write by the author to the entity type has to wait for approval.
     *
     * Writes without an author come from trusted server code (console commands, jobs, approval replays) and apply
     * directly; this is the only place that decides it, until API principals carry their own author. An automated
     * change, decided by a rule such as the auto match threshold, waits whenever the type requires approval, whoever
     * its author is. On a scoped type the author's own write skips approval only within their approve grants: on the
     * record, or for a create on the proposed parent; without either, it waits.
     */
    public function requiresApproval(EntityType $type, ?Admin $author, bool $automated = false, ?Record $record = null, ?Record $parent = null): bool
    {
        if ($automated) {
            return (bool) $type->approval_required;
        }

        if (! $author instanceof Admin) {
            return false;
        }

        return $type->approval_required
            && ! ($this->bypasses($author, $type) && $this->approvesAt($author, (int) $type->id, $record, $parent));
    }

    /**
     * Whether the admin may approve or reject the pending change request.
     */
    public function canDecide(Admin $admin, ChangeRequest $request): bool
    {
        return $request->isPending()
            && $request->requested_by !== (int) $admin->id
            && $this->bypasses($admin, $request->entityType)
            && $this->approvesWithin($admin, $request);
    }

    /**
     * Whether the admin's approve grants reach the request's record, or the parent a create proposes.
     */
    public function approvesWithin(Admin $admin, ChangeRequest $request): bool
    {
        if ($request->record_id !== null && $request->record === null) {
            return false;
        }

        return $this->approvesAt($admin, (int) $request->entity_type_id, $request->record, $request->parent);
    }

    /**
     * Whether the admin's approve grants reach the stored record, else a record of the entity type below the parent.
     */
    protected function approvesAt(Admin $admin, int $entityTypeId, ?Record $record, ?Record $parent): bool
    {
        if ($record instanceof Record && $record->exists) {
            return $this->accessGrantResolver->allows($admin, GrantAction::Approve, $record);
        }

        return $this->accessGrantResolver->allowsUnder($admin, GrantAction::Approve, $entityTypeId, $parent);
    }

    /**
     * The active human admins who may decide the pending change request, by the same rule as `canDecide()`.
     *
     * @return list<int>
     */
    public function approverIds(ChangeRequest $request): array
    {
        if (! $request->isPending()) {
            return [];
        }

        $type = $request->entityType;

        $grants = $this->roleEntityPermissionRepository->grantsForEntityType((int) $type->id);

        $keyRoles = $this->roleRepository->all(['id', 'permission_type', 'permissions'])
            ->filter(fn (Role $role): bool => $role->permission_type === 'all'
                || in_array(RecordAction::Approve->aclKey(), (array) $role->permissions, true));

        $approverRoleIds = $keyRoles
            ->filter(fn (Role $role): bool => $role->permission_type === 'all'
                || in_array(RecordAction::Approve->value, $grants[(int) $role->id] ?? [], true))
            ->pluck('id')
            ->all();

        $keyRoleIds = $keyRoles->pluck('id')->all();

        if ($keyRoleIds === []) {
            return [];
        }

        $ids = $this->adminRepository->getModel()->newQuery()
            ->humans()
            ->where('status', true)
            ->when($request->requested_by !== null, fn ($query) => $query->whereKeyNot($request->requested_by))
            ->where(function ($query) use ($approverRoleIds, $keyRoleIds, $type): void {
                $query->whereIn('role_id', $approverRoleIds);

                if ($type->data_owner_id !== null) {
                    $query->orWhere(fn ($owner) => $owner->whereKey($type->data_owner_id)->whereIn('role_id', $keyRoleIds));
                }
            })
            ->orderBy('id')
            ->pluck('id')
            ->map(fn (mixed $id): int => (int) $id)
            ->values()
            ->all();

        if ($ids === [] || ! $this->accessGrantResolver->isScoped((int) $type->id)) {
            return $ids;
        }

        return $this->adminRepository->getModel()->newQuery()
            ->with('role')
            ->whereKey($ids)
            ->orderBy('id')
            ->get()
            ->filter(fn (Admin $admin): bool => $this->approvesWithin($admin, $request))
            ->map(fn (Admin $admin): int => (int) $admin->id)
            ->values()
            ->all();
    }
}
