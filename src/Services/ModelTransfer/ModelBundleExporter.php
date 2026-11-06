<?php

namespace Webkul\MasterDataManagement\Services\ModelTransfer;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Date;
use Webkul\MasterDataManagement\Models\Code;
use Webkul\MasterDataManagement\Models\CodeList;
use Webkul\MasterDataManagement\Models\CodeListVersion;
use Webkul\MasterDataManagement\Models\EntityType;
use Webkul\MasterDataManagement\Models\Field;
use Webkul\MasterDataManagement\Models\MatchRule;
use Webkul\MasterDataManagement\Models\RelationType;
use Webkul\MasterDataManagement\Models\SurvivorshipRule;
use Webkul\MasterDataManagement\Repositories\CodeListRepository;
use Webkul\MasterDataManagement\Repositories\CodeListVersionRepository;
use Webkul\MasterDataManagement\Repositories\EntityTypeRepository;
use Webkul\MasterDataManagement\Repositories\RelationTypeRepository;
use Webkul\MasterDataManagement\Repositories\SourceSystemRepository;
use Webkul\MasterDataManagement\Services\SchemaPublisher;

/**
 * Builds the model bundle: the definitions of entity types (fields, match and survivorship rules), the code lists
 * their fields use with the codes of their current version, and the relation types between them. Every reference
 * is written by code, never by id, so a bundle moves between installs.
 *
 * The importer reads existing definitions through the same row builders, so a definition that already matches its
 * bundle entry is recognised as unchanged.
 */
class ModelBundleExporter
{
    public const int FORMAT_VERSION = 1;

    public const string SETTING_REQUIRED_SOURCE_SYSTEMS = 'required_source_systems';

    public const string SETTING_REQUIRED_SOURCE_SYSTEM_IDS = 'required_source_system_ids';

    /**
     * @var array<int, string>|null
     */
    protected ?array $systemCodes = null;

    public function __construct(
        protected EntityTypeRepository $entityTypeRepository,
        protected CodeListRepository $codeListRepository,
        protected CodeListVersionRepository $codeListVersionRepository,
        protected RelationTypeRepository $relationTypeRepository,
        protected SourceSystemRepository $sourceSystemRepository,
    ) {}

    /**
     * The bundle of the entity types with the given codes, or of every entity type and code list when none are given.
     *
     * @param  list<string>  $typeCodes
     * @return array{format_version: int, exported_at: string, code_lists: list<array<string, mixed>>, entity_types: list<array<string, mixed>>, relation_types: list<array<string, mixed>>}
     */
    public function export(array $typeCodes = []): array
    {
        $this->systemCodes = null;

        $types = $this->entityTypes($typeCodes);

        $typeIds = array_map(intval(...), $types->modelKeys());

        $codeLists = $typeCodes === []
            ? $this->codeListRepository->getModel()->newQuery()->with('translations')->orderBy('code')->get()
            : $this->codeListRepository->getModel()->newQuery()
                ->with('translations')
                ->whereIn('id', $types->flatMap(fn (EntityType $type) => $type->fields->pluck('code_list_id'))->filter()->unique()->values()->all())
                ->orderBy('code')
                ->get();

        $relationTypes = $this->relationTypeRepository->getModel()->newQuery()
            ->with(['translations', 'fromEntityType:id,code', 'toEntityType:id,code'])
            ->where('is_system', false)
            ->whereIn('from_entity_type_id', $typeIds)
            ->orderBy('code')
            ->get();

        return [
            'format_version' => self::FORMAT_VERSION,
            'exported_at'    => Date::now()->toIso8601String(),
            'code_lists'     => $codeLists->map(fn (CodeList $list): array => $this->codeListRow($list))->values()->all(),
            'entity_types'   => $types->map(fn (EntityType $type): array => $this->entityTypeRow($type))->values()->all(),
            'relation_types' => $relationTypes->map(fn (RelationType $type): array => $this->relationTypeRow($type))->values()->all(),
        ];
    }

    /**
     * The entity types with the given codes, or every entity type, with what their bundle rows read, in display order.
     *
     * @param  list<string>  $codes
     * @return Collection<int, EntityType>
     */
    public function entityTypes(array $codes = []): Collection
    {
        return $this->entityTypeRepository->getModel()->newQuery()
            ->with([
                'translations',
                'fields' => fn ($query) => $query->orderBy('position')->orderBy('id'),
                'fields.translations',
                'fields.codeList:id,code',
                'fields.referenceEntityType:id,code',
                'matchRules',
                'survivorshipRules',
            ])
            ->when($codes !== [], fn ($query) => $query->whereIn('code', $codes))
            ->ordered()
            ->get();
    }

    /**
     * The bundle row of an entity type, its fields and its rules.
     *
     * @return array<string, mixed>
     */
    public function entityTypeRow(EntityType $type): array
    {
        $parentIds = array_map(intval(...), (array) ($type->allowed_parent_type_ids ?? []));

        $parentCodes = array_values($this->entityTypeRepository->codesByIds($parentIds));

        sort($parentCodes);

        return [
            'code'                 => $type->code,
            'translations'         => $this->translations($type, ['name', 'description']),
            'icon'                 => $type->icon,
            'label_field_code'     => $type->label_field_code,
            'is_hierarchical'      => (bool) $type->is_hierarchical,
            'is_scoped'            => (bool) $type->is_scoped,
            'allowed_parent_types' => $parentCodes,
            'approval_required'    => (bool) $type->approval_required,
            'mastering_style'      => $type->mastering_style->value,
            'code_strategy'        => $type->code_strategy->value,
            'code_pattern'         => $type->code_pattern,
            'status'               => $type->status->value,
            'sort_order'           => (int) $type->sort_order,
            'settings'             => $this->settings($type->settings),
            'fields'               => $type->fields->map(fn (Field $field): array => $this->fieldRow($field))->values()->all(),
            'match_rules'          => $type->matchRules->sortBy([['sort_order', 'asc'], ['id', 'asc']])
                ->map(fn (MatchRule $rule): array => $this->matchRuleRow($rule))
                ->values()
                ->all(),
            'survivorship_rules' => $type->survivorshipRules->sortBy([['sort_order', 'asc'], ['id', 'asc']])
                ->map(fn (SurvivorshipRule $rule): array => $this->survivorshipRuleRow($rule))
                ->values()
                ->all(),
        ];
    }

    /**
     * The bundle row of a field.
     *
     * @return array<string, mixed>
     */
    public function fieldRow(Field $field): array
    {
        $row = [
            'code'     => $field->code,
            'type'     => $field->type,
            'group'    => $field->group,
            'position' => (int) $field->position,
        ];

        foreach (SchemaPublisher::FLAGS as $flag) {
            $row[$flag] = (bool) $field->getAttribute($flag);
        }

        return $row + [
            'validation'            => $field->validation ?: null,
            'code_list'             => $field->codeList?->code,
            'reference_entity_type' => $field->referenceEntityType?->code,
            'labels'                => $this->translatedValues($field, 'label'),
            'help'                  => $this->translatedValues($field, 'help'),
        ];
    }

    /**
     * The bundle row of a match rule.
     *
     * @return array<string, mixed>
     */
    public function matchRuleRow(MatchRule $rule): array
    {
        return [
            'target'      => $rule->target,
            'comparator'  => $rule->comparator,
            'weight'      => (float) $rule->weight,
            'is_blocking' => (bool) $rule->is_blocking,
            'options'     => $rule->options ?: null,
            'sort_order'  => (int) $rule->sort_order,
        ];
    }

    /**
     * The bundle row of a survivorship rule; the source order names source systems by code.
     *
     * @return array<string, mixed>
     */
    public function survivorshipRuleRow(SurvivorshipRule $rule): array
    {
        $options = $rule->options ?: null;

        if (isset($options[SurvivorshipRule::OPTION_SOURCE_ORDER])) {
            $options[SurvivorshipRule::OPTION_SOURCE_ORDER] = $this->systemCodesFor((array) $options[SurvivorshipRule::OPTION_SOURCE_ORDER]);
        }

        return [
            'target'     => $rule->target,
            'strategy'   => $rule->strategy,
            'options'    => $options,
            'sort_order' => (int) $rule->sort_order,
        ];
    }

    /**
     * The bundle row of a relation type.
     *
     * @return array<string, mixed>
     */
    public function relationTypeRow(RelationType $type): array
    {
        return [
            'code'             => $type->code,
            'from_entity_type' => $type->fromEntityType?->code,
            'to_entity_type'   => $type->toEntityType?->code,
            'cardinality'      => $type->cardinality->value,
            'labels'           => $this->translatedValues($type, 'label'),
        ];
    }

    /**
     * The bundle row of a code list with the codes of its current version.
     *
     * @return array<string, mixed>
     */
    public function codeListRow(CodeList $list): array
    {
        $version = $this->codeListVersionRepository->effectiveOn((int) $list->id, Date::now())
            ?? $this->codeListVersionRepository->allFor((int) $list->id)->first();

        return [
            'code'         => $list->code,
            'owner'        => $list->owner,
            'is_versioned' => (bool) $list->is_versioned,
            'translations' => $this->translations($list, ['name', 'description']),
            'codes'        => $version instanceof CodeListVersion ? array_values($this->codeRows($version)) : [],
        ];
    }

    /**
     * The bundle rows of the codes of a code list version, keyed by code, in sort order.
     *
     * @return array<string, array<string, mixed>>
     */
    public function codeRows(CodeListVersion $version): array
    {
        return $version->codes()
            ->with('translations')
            ->orderBy('sort')
            ->orderBy('code')
            ->get()
            ->mapWithKeys(fn (Code $code): array => [(string) $code->code => $this->codeRow($code)])
            ->all();
    }

    /**
     * The bundle row of a code.
     *
     * @return array<string, mixed>
     */
    public function codeRow(Code $code): array
    {
        return [
            'code'           => (string) $code->code,
            'parent_code'    => $code->parent_code,
            'sort'           => (int) $code->sort,
            'is_active'      => (bool) $code->is_active,
            'effective_from' => $code->effective_from?->toDateString(),
            'effective_to'   => $code->effective_to?->toDateString(),
            'attributes'     => $code->getAttribute('attributes') ?: null,
            'labels'         => $this->translatedValues($code, 'label'),
        ];
    }

    /**
     * The entity type settings with the required source systems named by code.
     *
     * @param  array<string, mixed>|null  $settings
     * @return array<string, mixed>|null
     */
    protected function settings(?array $settings): ?array
    {
        if (! $settings) {
            return null;
        }

        if (array_key_exists(self::SETTING_REQUIRED_SOURCE_SYSTEM_IDS, $settings)) {
            $settings[self::SETTING_REQUIRED_SOURCE_SYSTEMS] = $this->systemCodesFor((array) $settings[self::SETTING_REQUIRED_SOURCE_SYSTEM_IDS]);

            unset($settings[self::SETTING_REQUIRED_SOURCE_SYSTEM_IDS]);
        }

        return $settings;
    }

    /**
     * The codes of the source systems with the given ids, in the given order; unknown ids are left out.
     *
     * @param  array<int, mixed>  $ids
     * @return list<string>
     */
    protected function systemCodesFor(array $ids): array
    {
        $this->systemCodes ??= $this->sourceSystemRepository->getModel()->newQuery()->toBase()->pluck('code', 'id')
            ->mapWithKeys(fn (mixed $code, mixed $id): array => [(int) $id => (string) $code])
            ->all();

        return array_values(array_filter(array_map(fn (mixed $id): ?string => $this->systemCodes[(int) $id] ?? null, $ids)));
    }

    /**
     * The filled translations of the given attributes, keyed by active locale.
     *
     * @param  list<string>  $attributes
     * @return array<string, array<string, ?string>>
     */
    protected function translations(object $model, array $attributes): array
    {
        $locales = $this->activeLocales();

        $rows = [];

        foreach ($model->translations as $translation) {
            if (! in_array($translation->locale, $locales, true)) {
                continue;
            }

            $values = [];

            foreach ($attributes as $attribute) {
                $values[$attribute] = filled($translation->{$attribute}) ? (string) $translation->{$attribute} : null;
            }

            if (array_filter($values, fn (?string $value): bool => $value !== null) !== []) {
                $rows[(string) $translation->locale] = $values;
            }
        }

        ksort($rows);

        return $rows;
    }

    /**
     * The filled values of one translated attribute, keyed by active locale.
     *
     * @return array<string, string>
     */
    protected function translatedValues(object $model, string $attribute): array
    {
        return array_map(fn (array $values): string => (string) $values[$attribute], $this->translations($model, [$attribute]));
    }

    /**
     * The codes of the active locales.
     *
     * @return list<string>
     */
    public function activeLocales(): array
    {
        return core()->getAllActiveLocales()->pluck('code')->map(strval(...))->values()->all();
    }
}
