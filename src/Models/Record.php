<?php

namespace Webkul\MasterDataManagement\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\HistoryControl\Contracts\HistoryAuditable;
use Webkul\HistoryControl\Interfaces\PresentableHistoryInterface;
use Webkul\HistoryControl\Presenters\JsonDataPresenter;
use Webkul\HistoryControl\Traits\HistoryTrait;
use Webkul\MasterDataManagement\Contracts\Record as RecordContract;
use Webkul\MasterDataManagement\Enums\RecordStatus;

#[Fillable([
    'entity_type_id',
    'code',
    'label',
    'values',
    'status',
    'schema_version',
    'completeness',
    'created_by',
    'updated_by',
])]
#[Table(name: 'mdm_records')]
class Record extends Model implements HistoryAuditable, PresentableHistoryInterface, RecordContract
{
    use HasUuids, HistoryTrait;

    protected $historyTags = ['masterdata_record'];

    protected $auditExclude = [
        'revision',
        'path',
        'depth',
        'completeness',
        'created_by',
        'updated_by',
    ];

    protected $attributes = [
        'status'   => 'active',
        'depth'    => 0,
        'revision' => 1,
    ];

    /**
     * Columns that receive a generated UUID on create.
     *
     * @return array<int, string>
     */
    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    /**
     * {@inheritdoc}
     */
    public static function getPresenters(): array
    {
        return [
            'values' => JsonDataPresenter::class,
        ];
    }

    /**
     * Get the entity type the record belongs to.
     */
    public function entityType(): BelongsTo
    {
        return $this->belongsTo(EntityTypeProxy::modelClass());
    }

    /**
     * Typecasts the defined columns into given types.
     */
    protected function casts(): array
    {
        return [
            'values'         => 'array',
            'status'         => RecordStatus::class,
            'parent_id'      => 'integer',
            'depth'          => 'integer',
            'revision'       => 'integer',
            'schema_version' => 'integer',
            'dq_score'       => 'decimal:2',
            'completeness'   => 'integer',
        ];
    }
}
