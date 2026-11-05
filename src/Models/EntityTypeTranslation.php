<?php

namespace Webkul\MasterDataManagement\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Model;
use Webkul\HistoryControl\Contracts\HistoryAuditable;
use Webkul\HistoryControl\Traits\HistoryTrait;
use Webkul\MasterDataManagement\Contracts\EntityTypeTranslation as EntityTypeTranslationContract;

#[Fillable(['name', 'description'])]
#[Table(name: 'mdm_entity_type_translations')]
#[WithoutTimestamps]
class EntityTypeTranslation extends Model implements EntityTypeTranslationContract, HistoryAuditable
{
    use HistoryTrait;

    protected $auditExclude = [
        'id',
        'locale',
        'entity_type_id',
    ];

    protected $historyTags = ['masterdata_entity_type'];

    protected $historyTranslatableFields = [
        'name'        => 'Name',
        'description' => 'Description',
    ];

    /**
     * Group history versions under the owning entity type.
     *
     * {@inheritdoc}
     */
    public function getPrimaryModelIdForHistory(): int
    {
        return $this->entity_type_id;
    }
}
