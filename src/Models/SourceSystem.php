<?php

namespace Webkul\MasterDataManagement\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Webkul\HistoryControl\Contracts\HistoryAuditable;
use Webkul\HistoryControl\Interfaces\PresentableHistoryInterface;
use Webkul\HistoryControl\Traits\HistoryTrait;
use Webkul\MasterDataManagement\Contracts\SourceSystem as SourceSystemContract;
use Webkul\MasterDataManagement\Enums\SourceSystemType;

#[Fillable([
    'code',
    'name',
    'type',
    'priority',
    'is_active',
    'contact_owner',
    'requires_approval',
    'external_id_is_personal',
])]
#[Table(name: 'mdm_source_systems')]
class SourceSystem extends Model implements HistoryAuditable, PresentableHistoryInterface, SourceSystemContract
{
    use HistoryTrait;

    protected $historyTags = ['masterdata_source_system'];

    protected $attributes = [
        'priority'                => 100,
        'is_active'               => true,
        'requires_approval'       => false,
        'external_id_is_personal' => false,
    ];

    /**
     * {@inheritdoc}
     */
    public static function getPresenters(): array
    {
        return [];
    }

    /**
     * Typecasts the defined columns into given types.
     */
    protected function casts(): array
    {
        return [
            'type'                    => SourceSystemType::class,
            'priority'                => 'integer',
            'is_active'               => 'boolean',
            'requires_approval'       => 'boolean',
            'external_id_is_personal' => 'boolean',
        ];
    }
}
