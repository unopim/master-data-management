<?php

namespace Webkul\MasterDataManagement\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\MasterDataManagement\Contracts\MatchRule as MatchRuleContract;

/**
 * One weighted comparison the match suggester runs for an entity type: the record code, its label or a field,
 * compared with a registered comparator; a blocking rule also pulls the records sharing the value as candidates.
 */
#[Fillable([
    'entity_type_id',
    'target',
    'comparator',
    'weight',
    'is_blocking',
    'options',
    'sort_order',
])]
#[Table(name: 'mdm_match_rules')]
class MatchRule extends Model implements MatchRuleContract
{
    /**
     * The target comparing the record code.
     */
    public const string TARGET_CODE = 'code';

    /**
     * The target comparing the record label.
     */
    public const string TARGET_LABEL = 'label';

    protected $attributes = [
        'weight'      => 1,
        'is_blocking' => false,
        'sort_order'  => 0,
    ];

    /**
     * Get the entity type the rule matches records of.
     */
    public function entityType(): BelongsTo
    {
        return $this->belongsTo(EntityTypeProxy::modelClass());
    }

    /**
     * Whether the rule compares a field value instead of the record code or label.
     */
    public function targetsField(): bool
    {
        return ! in_array($this->target, [self::TARGET_CODE, self::TARGET_LABEL], true);
    }

    /**
     * Typecasts the defined columns into given types.
     */
    protected function casts(): array
    {
        return [
            'entity_type_id' => 'integer',
            'weight'         => 'float',
            'is_blocking'    => 'boolean',
            'options'        => 'array',
            'sort_order'     => 'integer',
        ];
    }
}
