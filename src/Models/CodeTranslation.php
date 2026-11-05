<?php

namespace Webkul\MasterDataManagement\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Model;
use Webkul\HistoryControl\Contracts\HistoryAuditable;
use Webkul\HistoryControl\Traits\HistoryTrait;
use Webkul\MasterDataManagement\Contracts\CodeTranslation as CodeTranslationContract;

#[Fillable(['label'])]
#[Table(name: 'mdm_code_translations')]
#[WithoutTimestamps]
class CodeTranslation extends Model implements CodeTranslationContract, HistoryAuditable
{
    use HistoryTrait;

    protected $auditExclude = [
        'id',
        'locale',
        'code_id',
    ];

    protected $historyTags = ['masterdata_code_list'];

    protected $historyTranslatableFields = [
        'label' => 'Label',
    ];

    /**
     * Group history versions under the code list the translated code belongs to.
     *
     * {@inheritdoc}
     */
    public function getPrimaryModelIdForHistory(): int
    {
        return (int) CodeProxy::modelClass()::query()
            ->join('mdm_code_list_versions', 'mdm_code_list_versions.id', '=', 'mdm_codes.version_id')
            ->where('mdm_codes.id', $this->code_id)
            ->value('mdm_code_list_versions.code_list_id');
    }
}
