<?php

namespace Webkul\MasterDataManagement\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\MasterDataManagement\Contracts\SurvivorshipRule as SurvivorshipRuleContract;

/**
 * How a merge of an entity type's records picks the surviving value of a field, of the record label, or, without a
 * target, of every field no rule of its own covers: a registered survivorship strategy and its options.
 */
#[Fillable([
    'entity_type_id',
    'target',
    'strategy',
    'options',
    'sort_order',
])]
#[Table(name: 'mdm_survivorship_rules')]
class SurvivorshipRule extends Model implements SurvivorshipRuleContract
{
    /**
     * The form value standing for the default rule, which is stored without a target.
     */
    public const string TARGET_DEFAULT = '*';

    /**
     * The target picking the record label.
     */
    public const string TARGET_LABEL = 'label';

    /**
     * The option listing source system ids in the order their values win.
     */
    public const string OPTION_SOURCE_ORDER = 'source_order';

    protected $attributes = [
        'sort_order' => 0,
    ];

    /**
     * Get the entity type whose merges the rule decides.
     */
    public function entityType(): BelongsTo
    {
        return $this->belongsTo(EntityTypeProxy::modelClass());
    }

    /**
     * Whether the rule covers every field without a rule of its own.
     */
    public function isDefault(): bool
    {
        return $this->target === null;
    }

    /**
     * Typecasts the defined columns into given types.
     */
    protected function casts(): array
    {
        return [
            'entity_type_id' => 'integer',
            'options'        => 'array',
            'sort_order'     => 'integer',
        ];
    }
}
