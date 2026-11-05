<?php

namespace Webkul\MasterDataManagement\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Webkul\Core\Eloquent\TranslatableModel;
use Webkul\HistoryControl\Contracts\HistoryAuditable;
use Webkul\HistoryControl\Interfaces\PresentableHistoryInterface;
use Webkul\HistoryControl\Traits\HistoryTrait;
use Webkul\MasterDataManagement\Contracts\CodeList as CodeListContract;

#[Fillable(['code', 'owner', 'is_versioned'])]
#[Table(name: 'mdm_code_lists')]
class CodeList extends TranslatableModel implements CodeListContract, HistoryAuditable, PresentableHistoryInterface
{
    use HistoryTrait;

    public $translatedAttributes = ['name', 'description'];

    protected $historyTags = ['masterdata_code_list'];

    protected $attributes = [
        'is_versioned' => true,
    ];

    /**
     * {@inheritdoc}
     */
    public static function getPresenters(): array
    {
        return [];
    }

    /**
     * Get the versions of the code list.
     */
    public function versions(): HasMany
    {
        return $this->hasMany(CodeListVersionProxy::modelClass());
    }

    /**
     * Typecasts the defined columns into given types.
     */
    protected function casts(): array
    {
        return [
            'is_versioned' => 'boolean',
        ];
    }
}
