<?php

namespace Webkul\MasterDataManagement\Listeners;

use Illuminate\Validation\ValidationException;
use Webkul\MasterDataManagement\Services\RoleAccessGrants;
use Webkul\User\Models\Role;

/**
 * Saves the "Master data access" card of the core role form: validated before the role is stored, applied after.
 */
class SyncRoleAccessGrants
{
    public function __construct(protected RoleAccessGrants $roleAccessGrants) {}

    /**
     * Refuse the role save when the card's new grant is invalid.
     *
     * @throws ValidationException
     */
    public function validate(): void
    {
        $this->roleAccessGrants->validate(request());
    }

    /**
     * Apply the card to the saved role.
     */
    public function handle(mixed $role): void
    {
        if ($role instanceof Role) {
            $this->roleAccessGrants->sync($role, request());
        }
    }
}
