<?php

namespace Webkul\MasterDataManagement\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\MasterDataManagement\Contracts\FieldValidationRule as FieldValidationRuleContract;
use Webkul\MasterDataManagement\Enums\ValidationSeverity;

/**
 * A configurable check of one field's values: a registered rule type with its params, a severity deciding whether a
 * failure refuses the write or only raises a quality warning, and optional failure messages keyed by locale.
 */
#[Fillable([
    'field_id',
    'type',
    'params',
    'severity',
    'messages',
    'sort_order',
])]
#[Table(name: 'mdm_validation_rules')]
class FieldValidationRule extends Model implements FieldValidationRuleContract
{
    protected $attributes = [
        'severity'   => 'block',
        'sort_order' => 0,
    ];

    /**
     * Get the field whose values the rule checks.
     */
    public function field(): BelongsTo
    {
        return $this->belongsTo(FieldProxy::modelClass());
    }

    /**
     * Whether a failure of the rule refuses the write.
     */
    public function blocks(): bool
    {
        return $this->severity === ValidationSeverity::Block;
    }

    /**
     * Typecasts the defined columns into given types.
     */
    protected function casts(): array
    {
        return [
            'field_id'   => 'integer',
            'params'     => 'array',
            'severity'   => ValidationSeverity::class,
            'messages'   => 'array',
            'sort_order' => 'integer',
        ];
    }
}
