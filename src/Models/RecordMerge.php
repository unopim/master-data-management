<?php

namespace Webkul\MasterDataManagement\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\MasterDataManagement\Contracts\RecordMerge as RecordMergeContract;

#[Fillable([
    'entity_type_id',
    'survivor_id',
    'merged_id',
    'survivor_before',
    'merged_before',
    'result',
    'moved',
    'merged_by',
    'merged_at',
    'unmerged_by',
    'unmerged_at',
])]
#[Table(name: 'mdm_record_merges', timestamps: false)]
class RecordMerge extends Model implements RecordMergeContract
{
    /**
     * Get the record that kept its identity.
     */
    public function survivor(): BelongsTo
    {
        return $this->belongsTo(RecordProxy::modelClass(), 'survivor_id');
    }

    /**
     * Get the record folded into the survivor.
     */
    public function merged(): BelongsTo
    {
        return $this->belongsTo(RecordProxy::modelClass(), 'merged_id');
    }

    /**
     * Whether the merge still stands.
     */
    public function isActive(): bool
    {
        return $this->unmerged_at === null;
    }

    /**
     * Typecasts the defined columns into given types.
     */
    protected function casts(): array
    {
        return [
            'survivor_before' => 'array',
            'merged_before'   => 'array',
            'result'          => 'array',
            'moved'           => 'array',
            'merged_at'       => 'datetime',
            'unmerged_at'     => 'datetime',
        ];
    }
}
