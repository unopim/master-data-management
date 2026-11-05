<?php

namespace Webkul\MasterDataManagement\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\MasterDataManagement\Contracts\Relation as RelationContract;

#[Fillable([
    'relation_type_id',
    'from_record_id',
    'to_record_id',
    'attributes',
    'valid_from',
    'valid_to',
])]
#[Table(name: 'mdm_relations')]
class Relation extends Model implements RelationContract
{
    /**
     * Get the type of the relation.
     */
    public function relationType(): BelongsTo
    {
        return $this->belongsTo(RelationTypeProxy::modelClass());
    }

    /**
     * Get the record the relation starts from.
     */
    public function fromRecord(): BelongsTo
    {
        return $this->belongsTo(RecordProxy::modelClass(), 'from_record_id');
    }

    /**
     * Get the record the relation points to.
     */
    public function toRecord(): BelongsTo
    {
        return $this->belongsTo(RecordProxy::modelClass(), 'to_record_id');
    }

    /**
     * Typecasts the defined columns into given types.
     */
    protected function casts(): array
    {
        return [
            'relation_type_id' => 'integer',
            'from_record_id'   => 'integer',
            'to_record_id'     => 'integer',
            'attributes'       => 'array',
            'valid_from'       => 'date',
            'valid_to'         => 'date',
        ];
    }
}
