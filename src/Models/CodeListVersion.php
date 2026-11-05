<?php

namespace Webkul\MasterDataManagement\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Webkul\HistoryControl\Contracts\HistoryAuditable;
use Webkul\HistoryControl\Interfaces\PresentableHistoryInterface;
use Webkul\HistoryControl\Traits\HistoryTrait;
use Webkul\MasterDataManagement\Contracts\CodeListVersion as CodeListVersionContract;
use Webkul\MasterDataManagement\Enums\CodeListVersionStatus;

#[Fillable([
    'code_list_id',
    'version',
    'status',
    'effective_from',
    'effective_to',
    'published_by',
    'published_at',
])]
#[Table(name: 'mdm_code_list_versions')]
class CodeListVersion extends Model implements CodeListVersionContract, HistoryAuditable, PresentableHistoryInterface
{
    use HistoryTrait;

    protected $historyTags = ['masterdata_code_list'];

    protected $auditExclude = [
        'published_by',
    ];

    protected $attributes = [
        'status' => 'draft',
    ];

    /**
     * {@inheritdoc}
     */
    public static function getPresenters(): array
    {
        return [];
    }

    /**
     * Group history versions under the owning code list.
     *
     * {@inheritdoc}
     */
    public function getPrimaryModelIdForHistory(): int
    {
        return (int) $this->code_list_id;
    }

    /**
     * Get the code list the version belongs to.
     */
    public function codeList(): BelongsTo
    {
        return $this->belongsTo(CodeListProxy::modelClass());
    }

    /**
     * Get the codes of the version.
     */
    public function codes(): HasMany
    {
        return $this->hasMany(CodeProxy::modelClass(), 'version_id');
    }

    /**
     * Typecasts the defined columns into given types.
     */
    protected function casts(): array
    {
        return [
            'code_list_id'   => 'integer',
            'version'        => 'integer',
            'status'         => CodeListVersionStatus::class,
            'effective_from' => 'date',
            'effective_to'   => 'date',
            'published_by'   => 'integer',
            'published_at'   => 'datetime',
        ];
    }
}
