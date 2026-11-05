<?php

namespace Webkul\MasterDataManagement\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Webkul\Core\Eloquent\TranslatableModel;
use Webkul\HistoryControl\Contracts\HistoryAuditable;
use Webkul\HistoryControl\Interfaces\PresentableHistoryInterface;
use Webkul\HistoryControl\Presenters\JsonDataPresenter;
use Webkul\HistoryControl\Traits\HistoryTrait;
use Webkul\MasterDataManagement\Contracts\EntityType as EntityTypeContract;
use Webkul\MasterDataManagement\Enums\CodeStrategy;
use Webkul\MasterDataManagement\Enums\EntityTypeStatus;
use Webkul\MasterDataManagement\Enums\MasteringStyle;
use Webkul\User\Models\AdminProxy;

#[Fillable([
    'code',
    'icon',
    'icon_id',
    'label_field_code',
    'is_hierarchical',
    'is_scoped',
    'allowed_parent_type_ids',
    'approval_required',
    'data_owner_id',
    'mastering_style',
    'code_strategy',
    'code_pattern',
    'schema_version',
    'status',
    'sort_order',
    'settings',
])]
#[Table(name: 'mdm_entity_types')]
class EntityType extends TranslatableModel implements EntityTypeContract, HistoryAuditable, PresentableHistoryInterface
{
    use HistoryTrait;

    public $translatedAttributes = ['name', 'description'];

    protected $historyTags = ['masterdata_entity_type'];

    protected $auditExclude = [
        'quality_scanned_at',
    ];

    protected $attributes = [
        'mastering_style' => 'consolidation',
        'code_strategy'   => 'pattern',
        'code_pattern'    => '{TYPE}-{seq:6}',
        'schema_version'  => 0,
        'status'          => 'draft',
        'sort_order'      => 0,
    ];

    /**
     * {@inheritdoc}
     */
    public static function getPresenters(): array
    {
        return [
            'allowed_parent_type_ids' => JsonDataPresenter::class,
            'settings'                => JsonDataPresenter::class,
        ];
    }

    /**
     * Get the icon library entry the type shows, which wins over its admin font icon.
     */
    public function libraryIcon(): BelongsTo
    {
        return $this->belongsTo(IconProxy::modelClass(), 'icon_id');
    }

    /**
     * Get the fields of the entity type, archived ones included.
     */
    public function fields(): HasMany
    {
        return $this->hasMany(FieldProxy::modelClass());
    }

    /**
     * Get the admin who owns the type's data and approves its changes.
     */
    public function dataOwner(): BelongsTo
    {
        return $this->belongsTo(AdminProxy::modelClass(), 'data_owner_id');
    }

    /**
     * The settings key that turns the queued duplicate check after each record write on or off.
     */
    public const string MATCH_ON_WRITE_SETTING = 'match_on_write';

    /**
     * Get the match rules the suggester scores the type's records with.
     */
    public function matchRules(): HasMany
    {
        return $this->hasMany(MatchRuleProxy::modelClass())->orderBy('sort_order')->orderBy('id');
    }

    /**
     * Get the rules deciding which values survive a merge of the type's records.
     */
    public function survivorshipRules(): HasMany
    {
        return $this->hasMany(SurvivorshipRuleProxy::modelClass())->orderBy('sort_order')->orderBy('id');
    }

    /**
     * Whether each record write queues a duplicate check of the record; on unless the type turns it off.
     */
    public function matchesOnWrite(): bool
    {
        return (bool) ($this->settings[self::MATCH_ON_WRITE_SETTING] ?? true);
    }

    /**
     * Order the types for display: by their sort order, then by code.
     *
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function ordered(Builder $query): void
    {
        $query->orderBy($query->qualifyColumn('sort_order'))->orderBy($query->qualifyColumn('code'));
    }

    /**
     * Typecasts the defined columns into given types.
     */
    protected function casts(): array
    {
        return [
            'is_hierarchical'         => 'boolean',
            'is_scoped'               => 'boolean',
            'approval_required'       => 'boolean',
            'data_owner_id'           => 'integer',
            'icon_id'                 => 'integer',
            'allowed_parent_type_ids' => 'array',
            'settings'                => 'array',
            'schema_version'          => 'integer',
            'sort_order'              => 'integer',
            'mastering_style'         => MasteringStyle::class,
            'code_strategy'           => CodeStrategy::class,
            'status'                  => EntityTypeStatus::class,
            'quality_scanned_at'      => 'datetime',
        ];
    }
}
