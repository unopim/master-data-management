<?php

namespace Webkul\MasterDataManagement\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\MasterDataManagement\Contracts\ChangeRequest as ChangeRequestContract;
use Webkul\MasterDataManagement\Enums\ChangeOperation;
use Webkul\MasterDataManagement\Enums\ChangeRequestStatus;
use Webkul\User\Models\AdminProxy;

/**
 * A record change held for approval: the writer input to replay, the attributes before and after, and the decision.
 */
#[Fillable([
    'entity_type_id',
    'record_id',
    'parent_id',
    'operation',
    'status',
    'base_revision',
    'before',
    'after',
    'input',
    'origin',
    'context',
    'requested_by',
    'decided_by',
    'decided_at',
    'comment',
    'pending_key',
])]
#[Table(name: 'mdm_change_requests')]
class ChangeRequest extends Model implements ChangeRequestContract
{
    protected $attributes = [
        'status' => 'pending',
    ];

    /**
     * Get the entity type the change belongs to.
     */
    public function entityType(): BelongsTo
    {
        return $this->belongsTo(EntityTypeProxy::modelClass());
    }

    /**
     * Get the record the change applies to; empty for a create until it is approved.
     */
    public function record(): BelongsTo
    {
        return $this->belongsTo(RecordProxy::modelClass());
    }

    /**
     * The parent a held create proposes for the new record.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(RecordProxy::modelClass(), 'parent_id');
    }

    /**
     * Get the admin who asked for the change.
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(AdminProxy::modelClass(), 'requested_by');
    }

    /**
     * Get the admin who approved or rejected the change.
     */
    public function decider(): BelongsTo
    {
        return $this->belongsTo(AdminProxy::modelClass(), 'decided_by');
    }

    /**
     * Whether the change still waits for a decision.
     */
    public function isPending(): bool
    {
        return $this->status === ChangeRequestStatus::Pending;
    }

    /**
     * Typecasts the defined columns into given types.
     */
    protected function casts(): array
    {
        return [
            'operation'     => ChangeOperation::class,
            'status'        => ChangeRequestStatus::class,
            'parent_id'     => 'integer',
            'base_revision' => 'integer',
            'before'        => 'array',
            'after'         => 'array',
            'input'         => 'array',
            'context'       => 'array',
            'requested_by'  => 'integer',
            'decided_by'    => 'integer',
            'decided_at'    => 'datetime',
        ];
    }
}
