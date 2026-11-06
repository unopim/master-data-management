<?php

namespace Webkul\MasterDataManagement\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\MasterDataManagement\Contracts\QualitySnapshot as QualitySnapshotContract;

/**
 * One day's data quality aggregates of an entity type: average completeness, the share of live records without an
 * open validity or duplicate issue, and the open steward tasks.
 *
 * @property int $entity_type_id
 * @property int $record_count
 * @property float|null $completeness_avg
 * @property float|null $validity_rate
 * @property float|null $duplicate_rate
 * @property int $open_tasks
 */
#[Fillable(['entity_type_id', 'snapshot_date', 'record_count', 'completeness_avg', 'validity_rate', 'duplicate_rate', 'open_tasks'])]
#[Table(name: 'mdm_quality_snapshots')]
class QualitySnapshot extends Model implements QualitySnapshotContract
{
    /**
     * Get the entity type the snapshot measures.
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
            'entity_type_id'   => 'integer',
            'snapshot_date'    => 'date',
            'record_count'     => 'integer',
            'completeness_avg' => 'float',
            'validity_rate'    => 'float',
            'duplicate_rate'   => 'float',
            'open_tasks'       => 'integer',
        ];
    }
}
