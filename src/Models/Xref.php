<?php

namespace Webkul\MasterDataManagement\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\HistoryControl\Contracts\HistoryAuditable;
use Webkul\HistoryControl\Interfaces\PresentableHistoryInterface;
use Webkul\HistoryControl\Traits\HistoryTrait;
use Webkul\MasterDataManagement\Contracts\Xref as XrefContract;
use Webkul\MasterDataManagement\Enums\XrefStatus;

#[Fillable([
    'record_id',
    'entity_type_id',
    'source_system_id',
    'external_id',
    'status',
    'last_pushed_hash',
    'first_seen_at',
    'last_seen_at',
])]
#[Table(name: 'mdm_xrefs', timestamps: false)]
class Xref extends Model implements HistoryAuditable, PresentableHistoryInterface, XrefContract
{
    use HistoryTrait {
        transformAudit as protected transformHistoryAudit;
    }

    /**
     * Crosswalk changes are listed in the history of the record they point at.
     */
    protected $historyTags = ['masterdata_record'];

    protected $auditExclude = [
        'record_id',
        'entity_type_id',
        'last_pushed_hash',
        'first_seen_at',
        'last_seen_at',
    ];

    protected $attributes = [
        'status' => 'active',
    ];

    /**
     * {@inheritdoc}
     */
    public static function getPresenters(): array
    {
        return [];
    }

    /**
     * Group history versions under the record the external id points at.
     *
     * {@inheritdoc}
     */
    public function getPrimaryModelIdForHistory(): int
    {
        return (int) $this->record_id;
    }

    /**
     * Show a link or unlink as one "Source ID system: external id" row whose value is the entry's status.
     *
     * {@inheritdoc}
     */
    public function transformAudit(array $data): array
    {
        $data = $this->transformHistoryAudit($data);

        $name = trans('masterdata::app.records.xrefs.history-label', [
            'system' => $this->sourceSystem?->code ?? (string) $this->source_system_id,
            'id'     => (string) $this->external_id,
        ]);

        foreach (['old_values', 'new_values'] as $side) {
            $status = $data[$side]['status'] ?? null;

            $status = $status instanceof XrefStatus ? $status->value : $status;

            $data[$side] = is_string($status) && $status !== ''
                ? ['common' => [$name => trans('masterdata::app.records.xref-statuses.'.$status)]]
                : [];
        }

        return $data;
    }

    /**
     * Get the record the external id points at.
     */
    public function record(): BelongsTo
    {
        return $this->belongsTo(RecordProxy::modelClass());
    }

    /**
     * Get the source system that owns the external id.
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
            'status'        => XrefStatus::class,
            'first_seen_at' => 'datetime',
            'last_seen_at'  => 'datetime',
        ];
    }
}
