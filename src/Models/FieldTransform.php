<?php

namespace Webkul\MasterDataManagement\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\MasterDataManagement\Contracts\FieldTransform as FieldTransformContract;

/**
 * A registered transform standardizing one field's values before they are validated and stored, run in sort order.
 */
#[Fillable([
    'field_id',
    'transform',
    'sort_order',
])]
#[Table(name: 'mdm_field_transforms')]
class FieldTransform extends Model implements FieldTransformContract
{
    protected $attributes = [
        'sort_order' => 0,
    ];

    /**
     * Get the field whose values the transform standardizes.
     */
    public function field(): BelongsTo
    {
        return $this->belongsTo(FieldProxy::modelClass());
    }

    /**
     * Typecasts the defined columns into given types.
     */
    protected function casts(): array
    {
        return [
            'field_id'   => 'integer',
            'sort_order' => 'integer',
        ];
    }
}
