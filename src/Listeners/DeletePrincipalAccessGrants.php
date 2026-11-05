<?php

namespace Webkul\MasterDataManagement\Listeners;

use Webkul\MasterDataManagement\Enums\GrantPrincipal;
use Webkul\MasterDataManagement\Repositories\AccessGrantRepository;
use Webkul\MasterDataManagement\Security\AccessGrantResolver;

/**
 * Drops the data access grants of a role or admin once core has deleted it, so a later principal reusing the id
 * inherits nothing.
 */
class DeletePrincipalAccessGrants
{
    public function __construct(
        protected AccessGrantRepository $accessGrantRepository,
        protected AccessGrantResolver $accessGrantResolver,
    ) {}

    /**
     * Delete the grants of the deleted role.
     */
    public function role(mixed $roleId): void
    {
        $this->delete(GrantPrincipal::Role, $roleId);
    }

    /**
     * Delete the grants of the deleted admin.
     */
    public function admin(mixed $adminId): void
    {
        $this->delete(GrantPrincipal::User, $adminId);
    }

    protected function delete(GrantPrincipal $type, mixed $principalId): void
    {
        if (! is_numeric($principalId)) {
            return;
        }

        $this->accessGrantRepository->deleteForPrincipal($type, (int) $principalId);

        $this->accessGrantResolver->flush();
    }
}
