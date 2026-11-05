<?php

namespace Webkul\MasterDataManagement\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\HistoryControl\Contracts\HistoryAuditable;
use Webkul\HistoryControl\Interfaces\PresentableHistoryInterface;
use Webkul\HistoryControl\Traits\HistoryTrait;
use Webkul\MasterDataManagement\Contracts\Alias as AliasContract;
use Webkul\MasterDataManagement\Enums\AliasScope;

#[Fillable([
    'scope_type',
    'scope_id',
    'term',
    'normalized_term',
    'target',
    'locale',
    'source_system_id',
])]
#[Table(name: 'mdm_aliases')]
class Alias extends Model implements AliasContract, HistoryAuditable, PresentableHistoryInterface
{
    use HistoryTrait;

    protected $historyTags = ['masterdata_alias'];

    protected $auditExclude = [
        'normalized_term',
    ];

    /**
     * {@inheritdoc}
     */
    public static function getPresenters(): array
    {
        return [];
    }

    /**
     * Get the source system the alias is specific to.
     */
    public function sourceSystem(): BelongsTo
    {
        return $this->belongsTo(SourceSystemProxy::modelClass());
    }

    /**
     * Typecasts the defined columns into given types.
     */
    protected function casts(): array
    {
        return [
            'scope_type'       => AliasScope::class,
            'scope_id'         => 'integer',
            'source_system_id' => 'integer',
        ];
    }
}
