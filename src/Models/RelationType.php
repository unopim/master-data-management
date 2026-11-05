<?php

namespace Webkul\MasterDataManagement\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Webkul\Core\Eloquent\TranslatableModel;
use Webkul\MasterDataManagement\Contracts\RelationType as RelationTypeContract;
use Webkul\MasterDataManagement\Enums\RelationCardinality;

#[Fillable([
    'code',
    'from_entity_type_id',
    'to_entity_type_id',
    'cardinality',
    'is_system',
    'field_id',
])]
#[Table(name: 'mdm_relation_types')]
class RelationType extends TranslatableModel implements RelationTypeContract
{
    public $translatedAttributes = ['label'];

    protected $attributes = [
        'is_system' => false,
    ];

    /**
     * Get the entity type the relations start from.
     */
    public function fromEntityType(): BelongsTo
    {
        return $this->belongsTo(EntityTypeProxy::modelClass(), 'from_entity_type_id');
    }

    /**
     * Get the entity type the relations point to.
     */
    public function toEntityType(): BelongsTo
    {
        return $this->belongsTo(EntityTypeProxy::modelClass(), 'to_entity_type_id');
    }

    /**
     * Get the reference field a system relation type stores.
     */
    public function field(): BelongsTo
    {
        return $this->belongsTo(FieldProxy::modelClass());
    }

    /**
     * Get the relations of this type.
     */
    public function relations(): HasMany
    {
        return $this->hasMany(RelationProxy::modelClass());
    }

    /**
     * Typecasts the defined columns into given types.
     */
    protected function casts(): array
    {
        return [
            'from_entity_type_id' => 'integer',
            'to_entity_type_id'   => 'integer',
            'cardinality'         => RelationCardinality::class,
            'is_system'           => 'boolean',
            'field_id'            => 'integer',
        ];
    }
}
