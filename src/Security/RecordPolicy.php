<?php

namespace Webkul\MasterDataManagement\Security;

use Webkul\MasterDataManagement\Enums\GrantAction;
use Webkul\MasterDataManagement\Enums\RecordAction;
use Webkul\MasterDataManagement\Models\EntityType;
use Webkul\MasterDataManagement\Models\Record;
use Webkul\MasterDataManagement\Models\RecordProxy;
use Webkul\User\Models\Admin;

/**
 * Every record ability needs the static ACL key and the role's grant on the entity type. API
 * robots skip the ACL half because ScopeMiddleware already checked the route's api-acl key.
 * On a scoped entity type a stored record must also lie in a subtree granted to the admin:
 * a view grant to read it, an edit grant to change it.
 */
class RecordPolicy
{
    public function __construct(
        protected EntityTypePermissions $entityTypePermissions,
        protected AccessGrantResolver $accessGrantResolver,
    ) {}

    /**
     * Build an unsaved record of the entity type, for checking a record ability once per type.
     */
    public static function subjectForEntityType(int $entityTypeId): Record
    {
        $recordClass = RecordProxy::modelClass();

        return (new $recordClass)->forceFill(['entity_type_id' => $entityTypeId]);
    }

    /**
     * Whether the admin may list the records of the entity type.
     */
    public function viewAny(Admin $admin, EntityType $type): bool
    {
        return $this->passesAcl($admin, 'masterdata.records')
            && $this->entityTypePermissions->allows($admin, (int) $type->id, RecordAction::View);
    }

    /**
     * Whether the admin may view the record.
     */
    public function view(Admin $admin, Record $record): bool
    {
        return $this->allows($admin, (int) $record->entity_type_id, RecordAction::View)
            && $this->accessGrantResolver->allows($admin, GrantAction::View, $record);
    }

    /**
     * Whether the admin may create records of the entity type.
     */
    public function create(Admin $admin, EntityType $type): bool
    {
        return $this->allows($admin, (int) $type->id, RecordAction::Create);
    }

    /**
     * Whether the admin may edit the record.
     */
    public function update(Admin $admin, Record $record): bool
    {
        return $this->allows($admin, (int) $record->entity_type_id, RecordAction::Edit)
            && $this->accessGrantResolver->allows($admin, GrantAction::Edit, $record);
    }

    /**
     * Whether the admin may delete the record.
     */
    public function delete(Admin $admin, Record $record): bool
    {
        return $this->allows($admin, (int) $record->entity_type_id, RecordAction::Delete)
            && $this->accessGrantResolver->allows($admin, GrantAction::Edit, $record);
    }

    /**
     * Whether the admin may permanently erase the record, which takes its own ACL key on top of the type's delete grant.
     */
    public function erase(Admin $admin, Record $record): bool
    {
        return $this->passesAcl($admin, 'masterdata.records.erase')
            && $this->entityTypePermissions->allows($admin, (int) $record->entity_type_id, RecordAction::Delete)
            && $this->accessGrantResolver->allows($admin, GrantAction::Edit, $record);
    }

    /**
     * Whether the admin may merge records of the record's entity type, or undo a merge.
     */
    public function merge(Admin $admin, Record $record): bool
    {
        return $this->allows($admin, (int) $record->entity_type_id, RecordAction::Merge)
            && $this->accessGrantResolver->allows($admin, GrantAction::Edit, $record);
    }

    /**
     * Combine the action's static ACL key with the entity type grant.
     */
    protected function allows(Admin $admin, int $entityTypeId, RecordAction $action): bool
    {
        return $this->passesAcl($admin, $action->aclKey())
            && $this->entityTypePermissions->allows($admin, $entityTypeId, $action);
    }

    /**
     * Check the static ACL key, which API robots have already passed through ScopeMiddleware.
     */
    protected function passesAcl(Admin $admin, string $key): bool
    {
        if ($admin->isApiUser()) {
            return true;
        }

        return $admin->role?->permission_type === 'all' || $admin->hasPermission($key);
    }
}
