<?php

namespace Webkul\MasterDataManagement\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\MasterDataManagement\Contracts\HierarchyNode as HierarchyNodeContract;

/**
 * A record's place in one hierarchy: its parent record and the chain of record ids from the root, `/{root}/.../{id}/`.
 */
#[Fillable([
    'hierarchy_id',
    'record_id',
    'parent_id',
    'path',
    'depth',
])]
#[Table(name: 'mdm_hierarchy_nodes')]
class HierarchyNode extends Model implements HierarchyNodeContract
{
    protected $attributes = [
        'depth' => 0,
    ];

    /**
     * Get the hierarchy the node belongs to.
     */
    public function hierarchy(): BelongsTo
    {
        return $this->belongsTo(HierarchyProxy::modelClass());
    }

    /**
     * Get the record the node places.
     */
    public function record(): BelongsTo
    {
        return $this->belongsTo(RecordProxy::modelClass());
    }

    /**
     * Typecasts the defined columns into given types.
     */
    protected function casts(): array
    {
        return [
            'hierarchy_id' => 'integer',
            'record_id'    => 'integer',
            'parent_id'    => 'integer',
            'depth'        => 'integer',
        ];
    }
}
