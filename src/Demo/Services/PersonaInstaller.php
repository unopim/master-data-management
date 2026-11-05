<?php

namespace Webkul\MasterDataManagement\Demo\Services;

use Illuminate\Support\Facades\Hash;
use Webkul\MasterDataManagement\Models\EntityType;
use Webkul\MasterDataManagement\Repositories\RoleEntityPermissionRepository;
use Webkul\User\Models\Admin;
use Webkul\User\Models\Role;
use Webkul\User\Repositories\AdminRepository;
use Webkul\User\Repositories\RoleRepository;

/**
 * Seeds the stewardship personas of the demo, one custom role and one user each: the data owner who approves, the
 * steward whose edits wait for approval, the reference data manager, the quality analyst and the analytics publisher.
 */
class PersonaInstaller
{
    public function __construct(
        protected AdminRepository $adminRepository,
        protected RoleRepository $roleRepository,
        protected RoleEntityPermissionRepository $roleEntityPermissionRepository,
    ) {}

    /**
     * Create or refresh each persona's role and user, signing every user in with the given password.
     *
     * @param  array<string, array<string, mixed>>  $personas
     * @return array<string, Admin>
     */
    public function install(array $personas, string $password): array
    {
        $users = [];

        foreach ($personas as $key => $persona) {
            $role = $this->ensureRole($persona);

            $user = $this->adminRepository->findOneByField('email', $persona['email']);

            $attributes = [
                'name'     => $persona['name'],
                'password' => Hash::make($password),
                'role_id'  => $role->id,
                'status'   => true,
            ];

            $users[$key] = $user instanceof Admin
                ? $this->adminRepository->update($attributes, $user->id)
                : $this->adminRepository->create($attributes + [
                    'email'        => $persona['email'],
                    'ui_locale_id' => core()->getAllActiveLocales()->first()?->id,
                ]);
        }

        return $users;
    }

    /**
     * Grant each persona's role its record actions on every demo entity type.
     *
     * @param  array<string, array<string, mixed>>  $personas
     * @param  array<string, Admin>  $users
     * @param  array<string, EntityType>  $types
     */
    public function grant(array $personas, array $users, array $types): void
    {
        foreach ($types as $type) {
            $actions = [];

            foreach ($personas as $key => $persona) {
                $actions[(int) $users[$key]->role_id] = $persona['actions'];
            }

            $this->roleEntityPermissionRepository->syncForEntityType((int) $type->id, $actions);
        }
    }

    /**
     * @param  array<string, mixed>  $persona
     */
    protected function ensureRole(array $persona): Role
    {
        $attributes = [
            'description'     => $persona['description'],
            'permission_type' => 'custom',
            'permissions'     => $persona['permissions'],
        ];

        $role = $this->roleRepository->findOneByField('name', $persona['role']);

        return $role instanceof Role
            ? $this->roleRepository->update($attributes, $role->id)
            : $this->roleRepository->create(['name' => $persona['role']] + $attributes);
    }
}
