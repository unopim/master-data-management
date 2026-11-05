<?php

namespace Webkul\MasterDataManagement\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\MasterDataManagement\Contracts\MatchCandidate as MatchCandidateContract;
use Webkul\MasterDataManagement\Enums\MatchCandidateStatus;
use Webkul\User\Models\AdminProxy;

/**
 * An incoming source row no crosswalk entry resolves yet, waiting in the match inbox for a steward decision.
 */
#[Fillable([
    'source_system_id',
    'entity_type_id',
    'external_id',
    'label',
    'payload',
    'status',
    'top_score',
    'auto_matched',
    'matched_record_id',
    'submitted_by',
    'resolved_by',
    'resolved_at',
])]
#[Table(name: 'mdm_match_candidates')]
class MatchCandidate extends Model implements MatchCandidateContract
{
    protected $attributes = [
        'status'       => 'pending',
        'auto_matched' => false,
    ];

    /**
     * Get the source system the row came from.
     */
    public function sourceSystem(): BelongsTo
    {
        return $this->belongsTo(SourceSystemProxy::modelClass());
    }

    /**
     * Get the entity type the row is matched within.
     */
    public function entityType(): BelongsTo
    {
        return $this->belongsTo(EntityTypeProxy::modelClass());
    }

    /**
     * Get the record the row was linked to or created as.
     */
    public function matchedRecord(): BelongsTo
    {
        return $this->belongsTo(RecordProxy::modelClass(), 'matched_record_id');
    }

    /**
     * Get the admin who decided the row.
     */
    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(AdminProxy::modelClass(), 'resolved_by');
    }

    /**
     * Whether the row still waits for a decision.
     */
    public function isPending(): bool
    {
        return $this->status === MatchCandidateStatus::Pending;
    }

    /**
     * Typecasts the defined columns into given types.
     */
    protected function casts(): array
    {
        return [
            'payload'      => 'array',
            'status'       => MatchCandidateStatus::class,
            'top_score'    => 'float',
            'auto_matched' => 'boolean',
            'resolved_at'  => 'datetime',
        ];
    }
}
