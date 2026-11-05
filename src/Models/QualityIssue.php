<?php

namespace Webkul\MasterDataManagement\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\MasterDataManagement\Contracts\QualityIssue as QualityIssueContract;
use Webkul\MasterDataManagement\Enums\QualityIssueStatus;
use Webkul\MasterDataManagement\Enums\QualitySeverity;

/**
 * A data quality finding on a record: one row per record, rule and rule key, reopened when detected again.
 */
#[Fillable([
    'entity_type_id',
    'record_id',
    'related_record_id',
    'rule',
    'rule_key',
    'severity',
    'status',
    'details',
    'detected_at',
    'resolved_at',
    'resolved_by',
    'resolution',
])]
#[Table(name: 'mdm_dq_issues')]
class QualityIssue extends Model implements QualityIssueContract
{
    protected $attributes = [
        'status' => 'open',
    ];

    /**
     * Get the record the issue was found on.
     */
    public function record(): BelongsTo
    {
        return $this->belongsTo(RecordProxy::modelClass());
    }

    /**
     * Get the entity type of the record.
     */
    public function entityType(): BelongsTo
    {
        return $this->belongsTo(EntityTypeProxy::modelClass());
    }

    /**
     * Whether the issue still waits for a fix or a decision.
     */
    public function isOpen(): bool
    {
        return $this->status === QualityIssueStatus::Open;
    }

    /**
     * Typecasts the defined columns into given types.
     */
    protected function casts(): array
    {
        return [
            'details'     => 'array',
            'status'      => QualityIssueStatus::class,
            'severity'    => QualitySeverity::class,
            'detected_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }
}
