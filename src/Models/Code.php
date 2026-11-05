<?php

namespace Webkul\MasterDataManagement\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Core\Eloquent\TranslatableModel;
use Webkul\HistoryControl\Contracts\HistoryAuditable;
use Webkul\HistoryControl\Interfaces\PresentableHistoryInterface;
use Webkul\HistoryControl\Traits\HistoryTrait;
use Webkul\MasterDataManagement\Contracts\Code as CodeContract;

#[Fillable([
    'version_id',
    'code',
    'parent_code',
    'sort',
    'attributes',
    'effective_from',
    'effective_to',
    'is_active',
])]
#[Table(name: 'mdm_codes')]
#[WithoutTimestamps]
class Code extends TranslatableModel implements CodeContract, HistoryAuditable, PresentableHistoryInterface
{
    use HistoryTrait;

    public $translatedAttributes = ['label'];

    protected $historyTags = ['masterdata_code_list'];

    protected $attributes = [
        'sort'      => 0,
        'is_active' => true,
    ];

    /**
     * {@inheritdoc}
     */
    public static function getPresenters(): array
    {
        return [];
    }

    /**
     * Group history versions under the code list the code's version belongs to.
     *
     * {@inheritdoc}
     */
    public function getPrimaryModelIdForHistory(): int
    {
        return (int) CodeListVersionProxy::modelClass()::query()->whereKey($this->version_id)->value('code_list_id');
    }

    /**
     * Get the code list version the code belongs to.
     */
    public function version(): BelongsTo
    {
        return $this->belongsTo(CodeListVersionProxy::modelClass(), 'version_id');
    }

    /**
     * Typecasts the defined columns into given types.
     */
    protected function casts(): array
    {
        return [
            'version_id'     => 'integer',
            'sort'           => 'integer',
            'attributes'     => 'array',
            'effective_from' => 'date',
            'effective_to'   => 'date',
            'is_active'      => 'boolean',
        ];
    }
}
