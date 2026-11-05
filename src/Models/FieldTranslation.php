<?php

namespace Webkul\MasterDataManagement\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Model;
use Webkul\HistoryControl\Contracts\HistoryAuditable;
use Webkul\HistoryControl\Traits\HistoryTrait;
use Webkul\MasterDataManagement\Contracts\FieldTranslation as FieldTranslationContract;

#[Fillable(['label', 'help'])]
#[Table(name: 'mdm_field_translations')]
#[WithoutTimestamps]
class FieldTranslation extends Model implements FieldTranslationContract, HistoryAuditable
{
    use HistoryTrait;

    protected $auditExclude = [
        'id',
        'locale',
        'field_id',
    ];

    protected $historyTags = ['masterdata_field'];

    protected $historyTranslatableFields = [
        'label' => 'Label',
        'help'  => 'Help',
    ];

    /**
     * Group history versions under the owning field.
     *
     * {@inheritdoc}
     */
    public function getPrimaryModelIdForHistory(): int
    {
        return $this->field_id;
    }
}
