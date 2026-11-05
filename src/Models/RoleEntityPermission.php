<?php

namespace Webkul\MasterDataManagement\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Webkul\MasterDataManagement\Contracts\RoleEntityPermission as RoleEntityPermissionContract;

#[Fillable([
    'role_id',
    'entity_type_id',
    'actions',
])]
#[Table(name: 'mdm_role_entity_permissions')]
class RoleEntityPermission extends Model implements RoleEntityPermissionContract
{
    /**
     * Typecasts the defined columns into given types.
     */
    protected function casts(): array
    {
        return [
            'role_id'        => 'integer',
            'entity_type_id' => 'integer',
            'actions'        => 'array',
        ];
    }
}
