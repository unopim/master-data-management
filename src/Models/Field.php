<?php

namespace Webkul\MasterDataManagement\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Core\Eloquent\TranslatableModel;
use Webkul\HistoryControl\Contracts\HistoryAuditable;
use Webkul\HistoryControl\Interfaces\PresentableHistoryInterface;
use Webkul\HistoryControl\Presenters\JsonDataPresenter;
use Webkul\HistoryControl\Traits\HistoryTrait;
use Webkul\MasterDataManagement\Contracts\Field as FieldContract;

#[Fillable([
    'entity_type_id',
    'code',
    'type',
    'group',
    'position',
    'is_required',
    'is_unique',
    'is_identifier',
    'value_per_locale',
    'is_effective_dated',
    'is_searchable',
    'multiple',
    'is_archived',
    'validation',
    'code_list_id',
    'reference_entity_type_id',
])]
#[Table(name: 'mdm_fields')]
class Field extends TranslatableModel implements FieldContract, HistoryAuditable, PresentableHistoryInterface
{
    use HistoryTrait;

    public $translatedAttributes = ['label', 'help'];

    protected $historyTags = ['masterdata_field'];

    /**
     * {@inheritdoc}
     */
    public static function getPresenters(): array
    {
        return [
            'validation' => JsonDataPresenter::class,
        ];
    }

    /**
     * Get the entity type the field belongs to.
     */
    public function entityType(): BelongsTo
    {
        return $this->belongsTo(EntityTypeProxy::modelClass());
    }

    /**
     * Get the code list a select or multiselect field draws its codes from.
     */
    public function codeList(): BelongsTo
    {
        return $this->belongsTo(CodeListProxy::modelClass());
    }

    /**
     * Get the entity type a reference field points to.
     */
    public function referenceEntityType(): BelongsTo
    {
        return $this->belongsTo(EntityTypeProxy::modelClass(), 'reference_entity_type_id');
    }

    /**
     * Typecasts the defined columns into given types.
     */
    protected function casts(): array
    {
        return [
            'entity_type_id'           => 'integer',
            'position'                 => 'integer',
            'is_required'              => 'boolean',
            'is_unique'                => 'boolean',
            'is_identifier'            => 'boolean',
            'value_per_locale'         => 'boolean',
            'is_effective_dated'       => 'boolean',
            'is_searchable'            => 'boolean',
            'multiple'                 => 'boolean',
            'is_archived'              => 'boolean',
            'validation'               => 'array',
            'code_list_id'             => 'integer',
            'reference_entity_type_id' => 'integer',
        ];
    }
}
