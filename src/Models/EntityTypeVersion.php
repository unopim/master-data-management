<?php

namespace Webkul\MasterDataManagement\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\MasterDataManagement\Contracts\EntityTypeVersion as EntityTypeVersionContract;

#[Fillable([
    'entity_type_id',
    'version',
    'fields',
    'published_by',
    'published_at',
])]
#[Table(name: 'mdm_entity_type_versions')]
#[WithoutTimestamps]
class EntityTypeVersion extends Model implements EntityTypeVersionContract
{
    /**
     * Get the entity type the published version belongs to.
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
            'entity_type_id' => 'integer',
            'version'        => 'integer',
            'fields'         => 'array',
            'published_by'   => 'integer',
            'published_at'   => 'datetime',
        ];
    }
}
