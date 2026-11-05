<?php

namespace Webkul\MasterDataManagement\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\MasterDataManagement\Contracts\AccessGrant as AccessGrantContract;
use Webkul\MasterDataManagement\Enums\GrantPrincipal;

/**
 * Allows a role, team or user the listed actions on the records of a scoped entity type (or of every scoped type)
 * in the subtree below a record, the record itself included.
 */
#[Fillable([
    'principal_type',
    'principal_id',
    'entity_type_id',
    'record_id',
    'actions',
])]
#[Table(name: 'mdm_access_grants')]
class AccessGrant extends Model implements AccessGrantContract
{
    /**
     * The record at the root of the granted subtree.
     */
    public function record(): BelongsTo
    {
        return $this->belongsTo(RecordProxy::modelClass());
    }

    /**
     * The entity type the grant is limited to; null for every scoped type.
     */
    public function entityType(): BelongsTo
    {
        return $this->belongsTo(EntityTypeProxy::modelClass());
    }

    /**
     * Typecasts the defined columns into given types.
     */
    protected function casts(): array
    {
        return [
            'principal_type' => GrantPrincipal::class,
            'principal_id'   => 'integer',
            'entity_type_id' => 'integer',
            'record_id'      => 'integer',
            'actions'        => 'array',
        ];
    }
}
