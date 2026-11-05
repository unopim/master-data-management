<?php

namespace Webkul\MasterDataManagement\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Model;
use Webkul\HistoryControl\Contracts\HistoryAuditable;
use Webkul\HistoryControl\Traits\HistoryTrait;
use Webkul\MasterDataManagement\Contracts\CodeListTranslation as CodeListTranslationContract;

#[Fillable(['name', 'description'])]
#[Table(name: 'mdm_code_list_translations')]
#[WithoutTimestamps]
class CodeListTranslation extends Model implements CodeListTranslationContract, HistoryAuditable
{
    use HistoryTrait;

    protected $auditExclude = [
        'id',
        'locale',
        'code_list_id',
    ];

    protected $historyTags = ['masterdata_code_list'];

    protected $historyTranslatableFields = [
        'name'        => 'Name',
        'description' => 'Description',
    ];

    /**
     * Group history versions under the owning code list.
     *
     * {@inheritdoc}
     */
    public function getPrimaryModelIdForHistory(): int
    {
        return (int) $this->code_list_id;
    }
}
