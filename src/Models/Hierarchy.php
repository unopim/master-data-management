<?php

namespace Webkul\MasterDataManagement\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Webkul\Core\Eloquent\TranslatableModel;
use Webkul\MasterDataManagement\Contracts\Hierarchy as HierarchyContract;

/**
 * A tree over the records of an entity type. The primary hierarchy mirrors the records' own parent_id, path and
 * depth; alternate hierarchies keep their placement in mdm_hierarchy_nodes only.
 */
#[Fillable([
    'entity_type_id',
    'code',
])]
#[Table(name: 'mdm_hierarchies')]
class Hierarchy extends TranslatableModel implements HierarchyContract
{
    /**
     * The code of the primary hierarchy every hierarchical entity type has.
     */
    public const string PRIMARY_CODE = 'primary';

    public $translatedAttributes = ['label'];

    protected $attributes = [
        'is_primary' => false,
    ];

    /**
     * Get the entity type whose records the hierarchy arranges.
     */
    public function entityType(): BelongsTo
    {
        return $this->belongsTo(EntityTypeProxy::modelClass());
    }

    /**
     * Get the nodes of the hierarchy.
     */
    public function nodes(): HasMany
    {
        return $this->hasMany(HierarchyNodeProxy::modelClass());
    }

    /**
     * The label in the current locale, else the primary hierarchy's name or the code.
     */
    public function displayLabel(): string
    {
        $label = (string) $this->label;

        if ($label !== '') {
            return $label;
        }

        return $this->is_primary ? trans('masterdata::app.hierarchies.primary-label') : (string) $this->code;
    }

    /**
     * Typecasts the defined columns into given types.
     */
    protected function casts(): array
    {
        return [
            'entity_type_id'         => 'integer',
            'is_primary'             => 'boolean',
            'primary_entity_type_id' => 'integer',
        ];
    }
}
