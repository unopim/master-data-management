<?php

namespace Webkul\MasterDataManagement\Services\ModelTransfer;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use JsonException;
use Webkul\MasterDataManagement\Enums\EntityTypeStatus;
use Webkul\MasterDataManagement\Http\Requests\FieldRequest;
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
use Webkul\MasterDataManagement\Repositories\CodeRepository;
use Webkul\MasterDataManagement\Repositories\EntityTypeRepository;
use Webkul\MasterDataManagement\Repositories\MatchRuleRepository;
use Webkul\MasterDataManagement\Repositories\RelationTypeRepository;
use Webkul\MasterDataManagement\Repositories\SourceSystemRepository;
use Webkul\MasterDataManagement\Repositories\SurvivorshipRuleRepository;
use Webkul\MasterDataManagement\Services\CodeListPublisher;
use Webkul\MasterDataManagement\Services\FieldService;
use Webkul\MasterDataManagement\Services\SchemaPublisher;
use Webkul\MasterDataManagement\Survivorship\Strategies\SourcePriorityStrategy;
use Webkul\MasterDataManagement\ValueObjects\ModelImportResult;
use Webkul\User\Models\Admin;

/**
 * Imports a model bundle in one transaction, after the whole bundle validated. Definitions are matched by code and
 * written only where they differ from the bundle, so importing the same bundle again changes nothing; definitions
 * the bundle leaves out are kept. Fields land in the type's working schema and codes in its list's draft version;
 * with publish, the entity types whose fields changed (or that were never published) get their next schema version,
 * take the bundle's status, and the changed code list drafts are published.
 */
class ModelBundleImporter
{
    public const int MAX_JSON_DEPTH = 64;

    public function __construct(
        protected ModelBundleValidator $validator,
        protected ModelBundleExporter $exporter,
        protected EntityTypeRepository $entityTypeRepository,
        protected CodeListRepository $codeListRepository,
        protected CodeListVersionRepository $codeListVersionRepository,
        protected CodeRepository $codeRepository,
        protected RelationTypeRepository $relationTypeRepository,
        protected MatchRuleRepository $matchRuleRepository,
        protected SurvivorshipRuleRepository $survivorshipRuleRepository,
        protected SourceSystemRepository $sourceSystemRepository,
        protected FieldService $fieldService,
        protected SchemaPublisher $schemaPublisher,
        protected CodeListPublisher $codeListPublisher,
    ) {}

    /**
     * Decode a bundle from JSON text.
     *
     * @return array<mixed>
     *
     * @throws ValidationException
     */
    public function decode(string $json, string $key = 'file'): array
    {
        try {
            $bundle = json_decode($json, true, self::MAX_JSON_DEPTH, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            $bundle = null;
        }

        if (! is_array($bundle) || array_is_list($bundle)) {
            throw ValidationException::withMessages([$key => [trans('masterdata::app.model-transfer.errors.invalid-json')]]);
        }

        return $bundle;
    }

    /**
     * Validate the bundle, then write it in one transaction.
     *
     * @param  array<mixed>  $bundle
     *
     * @throws ValidationException
     */
    public function import(array $bundle, bool $publish = false, ?Admin $by = null): ModelImportResult
    {
        $bundle = $this->validator->validate($bundle);

        return DB::transaction(fn (): ModelImportResult => $this->write($bundle, $publish, $by));
    }

    /**
     * Write the validated bundle.
     *
     * @param  array<string, mixed>  $bundle
     */
    protected function write(array $bundle, bool $publish, ?Admin $by): ModelImportResult
    {
        $result = new ModelImportResult;

        $locales = $this->exporter->activeLocales();

        $changedDrafts = [];

        foreach ((array) ($bundle['code_lists'] ?? []) as $list) {
            $draft = $this->writeCodeList($list, $locales, $result);

            if ($draft instanceof CodeListVersion) {
                $changedDrafts[(string) $list['code']] = $draft;
            }
        }

        $listIds = $this->codeListRepository->getModel()->newQuery()->pluck('id', 'code')->map(fn (mixed $id): int => (int) $id)->all();

        $systemIds = $this->sourceSystemRepository->getModel()->newQuery()->pluck('id', 'code')->map(fn (mixed $id): int => (int) $id)->all();

        $types = $this->exporter->entityTypes(array_column((array) ($bundle['entity_types'] ?? []), 'code'))->keyBy('code');

        $created = [];

        foreach ((array) ($bundle['entity_types'] ?? []) as $type) {
            if (! $types->has($type['code'])) {
                $types->put($type['code'], $this->entityTypeRepository->create([
                    'code'   => $type['code'],
                    'status' => EntityTypeStatus::Draft->value,
                ]));

                $created[$type['code']] = true;

                $result->created('entity_types');
            }
        }

        $typeIds = $this->entityTypeRepository->getModel()->newQuery()->pluck('id', 'code')->map(fn (mixed $id): int => (int) $id)->all();

        $schemaChanged = [];

        foreach ((array) ($bundle['entity_types'] ?? []) as $row) {
            $type = $types->get($row['code']);

            if ($this->writeFields($type, (array) ($row['fields'] ?? []), $listIds, $typeIds, $locales, $result)) {
                $schemaChanged[$row['code']] = true;
            }

            $this->writeEntityType($type, $row, $typeIds, $systemIds, $locales, isset($created[$row['code']]), $result);

            $this->writeMatchRules($type, (array) ($row['match_rules'] ?? []), $result);

            $this->writeSurvivorshipRules($type, (array) ($row['survivorship_rules'] ?? []), $systemIds, $result);
        }

        foreach ((array) ($bundle['relation_types'] ?? []) as $row) {
            $this->writeRelationType($row, $typeIds, $locales, $result);
        }

        if (! $publish) {
            return $result;
        }

        foreach ((array) ($bundle['entity_types'] ?? []) as $row) {
            $type = $this->entityTypeRepository->findOrFail($typeIds[$row['code']]);

            if (isset($schemaChanged[$row['code']]) || (int) $type->schema_version === 0) {
                $this->schemaPublisher->publish($type, $by);

                $result->published('entity_types', (string) $type->code);
            }

            $status = (string) ($row['status'] ?? EntityTypeStatus::Active->value);

            if ($type->status->value !== $status) {
                $this->entityTypeRepository->update(['status' => $status], $type->id);
            }
        }

        foreach ($changedDrafts as $code => $draft) {
            $this->codeListPublisher->publish($draft, $by);

            $result->published('code_lists', $code);
        }

        return $result;
    }

    /**
     * Create or update a code list and write the codes that differ into its draft version.
     *
     * @param  array<string, mixed>  $row
     * @param  list<string>  $locales
     * @return CodeListVersion|null The draft the codes were written to, or null when every code already matched.
     */
    protected function writeCodeList(array $row, array $locales, ModelImportResult $result): ?CodeListVersion
    {
        $incoming = [
            'code'         => (string) $row['code'],
            'owner'        => $row['owner'] ?? null,
            'is_versioned' => (bool) ($row['is_versioned'] ?? true),
            'translations' => $this->translationRows((array) ($row['translations'] ?? []), ['name', 'description'], $locales),
        ];

        $attributes = [
            'owner'        => $incoming['owner'],
            'is_versioned' => $incoming['is_versioned'],
            ...$this->translationPayload($incoming['translations'], ['name', 'description'], $locales),
        ];

        $list = $this->codeListRepository->getModel()->newQuery()->with('translations')->where('code', $incoming['code'])->first();

        $isNew = ! $list instanceof CodeList;

        if ($isNew) {
            $list = $this->codeListRepository->createWithDraft(['code' => $incoming['code']] + $attributes);

            $result->created('code_lists');
        } elseif (array_diff_key($this->exporter->codeListRow($list), ['codes' => true]) != $incoming) {
            $list = $this->codeListRepository->update($attributes, $list->id);

            $result->updated('code_lists');
        }

        $base = $this->codeListVersionRepository->findDraft((int) $list->id) ?? $this->codeListVersionRepository->allFor((int) $list->id)->first();

        $existing = $base instanceof CodeListVersion ? $this->exporter->codeRows($base) : [];

        $changed = [];

        foreach ((array) ($row['codes'] ?? []) as $code) {
            $normalized = $this->codeRow($code, $locales);

            if (($existing[$normalized['code']] ?? null) != $normalized) {
                $changed[$normalized['code']] = $normalized;
            }
        }

        if ($changed === [] && ! $isNew) {
            return null;
        }

        $draft = $this->codeListRepository->draftFor($list);

        $draftCodes = $this->codeRepository->getModel()->newQuery()
            ->where('version_id', $draft->id)
            ->whereIn('code', array_keys($changed))
            ->get()
            ->keyBy('code');

        foreach ($changed as $code => $normalized) {
            $attributes = [
                'parent_code'    => $normalized['parent_code'],
                'sort'           => $normalized['sort'],
                'is_active'      => $normalized['is_active'],
                'effective_from' => $normalized['effective_from'],
                'effective_to'   => $normalized['effective_to'],
                'attributes'     => $normalized['attributes'],
                ...$this->translationPayload(array_map(fn (string $label): array => ['label' => $label], $normalized['labels']), ['label'], $locales),
            ];

            $current = $draftCodes->get($code);

            if ($current instanceof Code) {
                $this->codeRepository->update($attributes, $current->id);

                $result->updated('codes');
            } else {
                $this->codeRepository->create(['version_id' => $draft->id, 'code' => $code] + $attributes);

                $result->created('codes');
            }
        }

        return $draft;
    }

    /**
     * Create or update the type's fields that differ from the bundle.
     *
     * @param  list<array<string, mixed>>  $rows
     * @param  array<string, int>  $listIds
     * @param  array<string, int>  $typeIds
     * @param  list<string>  $locales
     * @return bool Whether any field was created or updated.
     */
    protected function writeFields(EntityType $type, array $rows, array $listIds, array $typeIds, array $locales, ModelImportResult $result): bool
    {
        $existing = $type->relationLoaded('fields') ? $type->fields->keyBy('code') : collect();

        $changed = false;

        foreach ($rows as $row) {
            $normalized = $this->fieldRow($row, $locales);

            $field = $existing->get($normalized['code']);

            if ($field instanceof Field && $this->exporter->fieldRow($field) == $normalized) {
                continue;
            }

            $attributes = array_diff_key($normalized, array_flip(['code_list', 'reference_entity_type', 'labels', 'help'])) + [
                'code_list_id'             => $normalized['code_list'] === null ? null : $listIds[$normalized['code_list']],
                'reference_entity_type_id' => $normalized['reference_entity_type'] === null ? null : $typeIds[$normalized['reference_entity_type']],
            ];

            $translations = [];

            foreach ($locales as $locale) {
                $translations[$locale] = ['label' => $normalized['labels'][$locale] ?? null, 'help' => $normalized['help'][$locale] ?? null];
            }

            if ($field instanceof Field) {
                $this->fieldService->update($field, array_diff_key($attributes, ['code' => true, 'type' => true]) + $translations);

                $result->updated('fields');
            } else {
                $this->fieldService->create($type, $attributes + $translations);

                $result->created('fields');
            }

            $changed = true;
        }

        return $changed;
    }

    /**
     * Update the entity type's own settings where they differ from the bundle.
     *
     * @param  array<string, mixed>  $row
     * @param  array<string, int>  $typeIds
     * @param  array<string, int>  $systemIds
     * @param  list<string>  $locales
     */
    protected function writeEntityType(EntityType $type, array $row, array $typeIds, array $systemIds, array $locales, bool $isNew, ModelImportResult $result): void
    {
        $type = $this->entityTypeRepository->with('translations')->find($type->id);

        $parents = array_values(array_unique(array_map(strval(...), (array) ($row['allowed_parent_types'] ?? []))));

        sort($parents);

        $settings = $row['settings'] ?? null;

        if (is_array($settings)) {
            $settings = array_replace($this->exporter->entityTypeRow($type)['settings'] ?? [], $settings);
        }

        $incoming = [
            'translations'         => $this->translationRows((array) ($row['translations'] ?? []), ['name', 'description'], $locales),
            'icon'                 => $row['icon'] ?? null,
            'label_field_code'     => $row['label_field_code'] ?? null,
            'is_hierarchical'      => (bool) ($row['is_hierarchical'] ?? false),
            'is_scoped'            => (bool) ($row['is_scoped'] ?? false),
            'allowed_parent_types' => $parents,
            'approval_required'    => (bool) ($row['approval_required'] ?? false),
            'mastering_style'      => (string) ($row['mastering_style'] ?? $type->mastering_style->value),
            'code_strategy'        => (string) ($row['code_strategy'] ?? $type->code_strategy->value),
            'code_pattern'         => (string) ($row['code_pattern'] ?? $type->code_pattern),
            'sort_order'           => (int) ($row['sort_order'] ?? 0),
            'settings'             => is_array($settings) ? $settings : $this->exporter->entityTypeRow($type)['settings'],
        ];

        $current = array_intersect_key($this->exporter->entityTypeRow($type), $incoming);

        if ($current == $incoming) {
            return;
        }

        $attributes = array_diff_key($incoming, array_flip(['translations', 'allowed_parent_types', 'settings'])) + [
            'allowed_parent_type_ids' => array_map(fn (string $code): int => $typeIds[$code], $parents),
            'settings'                => $this->settingsWithIds($incoming['settings'], $systemIds),
            ...$this->translationPayload($incoming['translations'], ['name', 'description'], $locales),
        ];

        $this->entityTypeRepository->update($attributes, $type->id);

        if (! $isNew) {
            $result->updated('entity_types');
        }
    }

    /**
     * Create or update the type's match rules that differ from the bundle, matched by target and comparator.
     *
     * @param  list<array<string, mixed>>  $rows
     */
    protected function writeMatchRules(EntityType $type, array $rows, ModelImportResult $result): void
    {
        $existing = $this->matchRuleRepository->getModel()->newQuery()
            ->where('entity_type_id', $type->id)
            ->get()
            ->keyBy(fn (MatchRule $rule): string => $rule->target.'|'.$rule->comparator);

        foreach ($rows as $row) {
            $comparator = (string) $row['comparator'];

            $option = match ($comparator) {
                'numeric' => 'tolerance',
                'date'    => 'days',
                default   => null,
            };

            $value = $option === null ? null : ($row['options'][$option] ?? null);

            $normalized = [
                'target'      => (string) $row['target'],
                'comparator'  => $comparator,
                'weight'      => (float) $row['weight'],
                'is_blocking' => (bool) ($row['is_blocking'] ?? false),
                'options'     => $value === null ? null : [$option => (float) $value],
                'sort_order'  => (int) ($row['sort_order'] ?? 0),
            ];

            $rule = $existing->get($normalized['target'].'|'.$comparator);

            if (! $rule instanceof MatchRule) {
                $this->matchRuleRepository->create(['entity_type_id' => $type->id] + $normalized);

                $result->created('match_rules');
            } elseif ($this->exporter->matchRuleRow($rule) != $normalized) {
                $this->matchRuleRepository->update($normalized, $rule->id);

                $result->updated('match_rules');
            }
        }
    }

    /**
     * Create or update the type's survivorship rules that differ from the bundle, matched by target.
     *
     * @param  list<array<string, mixed>>  $rows
     * @param  array<string, int>  $systemIds
     */
    protected function writeSurvivorshipRules(EntityType $type, array $rows, array $systemIds, ModelImportResult $result): void
    {
        $existing = $this->survivorshipRuleRepository->getModel()->newQuery()
            ->where('entity_type_id', $type->id)
            ->get()
            ->keyBy(fn (SurvivorshipRule $rule): string => $rule->target ?? SurvivorshipRule::TARGET_DEFAULT);

        foreach ($rows as $row) {
            $strategy = (string) $row['strategy'];

            $order = array_values(array_map(strval(...), (array) ($row['options'][SurvivorshipRule::OPTION_SOURCE_ORDER] ?? [])));

            $normalized = [
                'target'     => isset($row['target']) ? (string) $row['target'] : null,
                'strategy'   => $strategy,
                'options'    => $strategy === SourcePriorityStrategy::CODE && $order !== [] ? [SurvivorshipRule::OPTION_SOURCE_ORDER => $order] : null,
                'sort_order' => (int) ($row['sort_order'] ?? 0),
            ];

            $attributes = ['options' => $normalized['options'] === null ? null : [
                SurvivorshipRule::OPTION_SOURCE_ORDER => array_map(fn (string $code): int => $systemIds[$code], $order),
            ]] + $normalized;

            $rule = $existing->get($normalized['target'] ?? SurvivorshipRule::TARGET_DEFAULT);

            if (! $rule instanceof SurvivorshipRule) {
                $this->survivorshipRuleRepository->create(['entity_type_id' => $type->id] + $attributes);

                $result->created('survivorship_rules');
            } elseif ($this->exporter->survivorshipRuleRow($rule) != $normalized) {
                $this->survivorshipRuleRepository->update($attributes, $rule->id);

                $result->updated('survivorship_rules');
            }
        }
    }

    /**
     * Create or update a relation type that differs from the bundle.
     *
     * @param  array<string, mixed>  $row
     * @param  array<string, int>  $typeIds
     * @param  list<string>  $locales
     */
    protected function writeRelationType(array $row, array $typeIds, array $locales, ModelImportResult $result): void
    {
        $normalized = [
            'code'             => (string) $row['code'],
            'from_entity_type' => (string) $row['from_entity_type'],
            'to_entity_type'   => (string) $row['to_entity_type'],
            'cardinality'      => (string) $row['cardinality'],
            'labels'           => $this->translatedValues((array) ($row['labels'] ?? []), $locales),
        ];

        $attributes = [
            'from_entity_type_id' => $typeIds[$normalized['from_entity_type']],
            'to_entity_type_id'   => $typeIds[$normalized['to_entity_type']],
            'cardinality'         => $normalized['cardinality'],
            'is_system'           => false,
            ...$this->translationPayload(array_map(fn (string $label): array => ['label' => $label], $normalized['labels']), ['label'], $locales),
        ];

        $type = $this->relationTypeRepository->getModel()->newQuery()
            ->with(['translations', 'fromEntityType:id,code', 'toEntityType:id,code'])
            ->where('code', $normalized['code'])
            ->first();

        if (! $type instanceof RelationType) {
            $this->relationTypeRepository->create(['code' => $normalized['code']] + $attributes);

            $result->created('relation_types');
        } elseif ($this->exporter->relationTypeRow($type) != $normalized) {
            $this->relationTypeRepository->update($attributes, $type->id);

            $result->updated('relation_types');
        }
    }

    /**
     * A bundle field in the exporter's row shape, with the field screen's invariants applied: a unique field is
     * searchable, only select types keep a code list and only references keep a reference type, never per locale.
     *
     * @param  array<string, mixed>  $row
     * @param  list<string>  $locales
     * @return array<string, mixed>
     */
    protected function fieldRow(array $row, array $locales): array
    {
        $type = (string) $row['type'];

        $normalized = [
            'code'     => (string) $row['code'],
            'type'     => $type,
            'group'    => $row['group'] ?? null,
            'position' => (int) ($row['position'] ?? 0),
        ];

        foreach (SchemaPublisher::FLAGS as $flag) {
            $normalized[$flag] = (bool) ($row[$flag] ?? false);
        }

        if ($normalized['is_unique']) {
            $normalized['is_searchable'] = true;
        }

        if ($type === 'reference') {
            $normalized['value_per_locale'] = false;
        }

        return $normalized + [
            'validation'            => ($row['validation'] ?? null) ?: null,
            'code_list'             => in_array($type, FieldRequest::CODE_LIST_TYPES, true) ? ($row['code_list'] ?? null) : null,
            'reference_entity_type' => $type === 'reference' ? ($row['reference_entity_type'] ?? null) : null,
            'labels'                => $this->translatedValues((array) ($row['labels'] ?? []), $locales),
            'help'                  => $this->translatedValues((array) ($row['help'] ?? []), $locales),
        ];
    }

    /**
     * A bundle code in the exporter's row shape.
     *
     * @param  array<string, mixed>  $row
     * @param  list<string>  $locales
     * @return array<string, mixed>
     */
    protected function codeRow(array $row, array $locales): array
    {
        return [
            'code'           => (string) $row['code'],
            'parent_code'    => $row['parent_code'] ?? null,
            'sort'           => (int) ($row['sort'] ?? 0),
            'is_active'      => (bool) ($row['is_active'] ?? true),
            'effective_from' => $row['effective_from'] ?? null,
            'effective_to'   => $row['effective_to'] ?? null,
            'attributes'     => ($row['attributes'] ?? null) ?: null,
            'labels'         => $this->translatedValues((array) ($row['labels'] ?? []), $locales),
        ];
    }

    /**
     * The entity type settings with the required source systems named by id again.
     *
     * @param  array<string, mixed>|null  $settings
     * @param  array<string, int>  $systemIds
     * @return array<string, mixed>|null
     */
    protected function settingsWithIds(?array $settings, array $systemIds): ?array
    {
        if ($settings === null || ! array_key_exists(ModelBundleExporter::SETTING_REQUIRED_SOURCE_SYSTEMS, $settings)) {
            return $settings;
        }

        $settings[ModelBundleExporter::SETTING_REQUIRED_SOURCE_SYSTEM_IDS] = array_values(array_map(
            fn (string $code): int => $systemIds[$code],
            array_map(strval(...), (array) $settings[ModelBundleExporter::SETTING_REQUIRED_SOURCE_SYSTEMS]),
        ));

        unset($settings[ModelBundleExporter::SETTING_REQUIRED_SOURCE_SYSTEMS]);

        return $settings;
    }

    /**
     * Bundle translations in the exporter's shape: filled values of active locales, a locale left out when empty.
     *
     * @param  array<mixed>  $translations
     * @param  list<string>  $attributes
     * @param  list<string>  $locales
     * @return array<string, array<string, ?string>>
     */
    protected function translationRows(array $translations, array $attributes, array $locales): array
    {
        $rows = [];

        foreach ($translations as $locale => $values) {
            if (! in_array($locale, $locales, true) || ! is_array($values)) {
                continue;
            }

            $row = [];

            foreach ($attributes as $attribute) {
                $row[$attribute] = filled($values[$attribute] ?? null) ? (string) $values[$attribute] : null;
            }

            if (array_filter($row, fn (?string $value): bool => $value !== null) !== []) {
                $rows[$locale] = $row;
            }
        }

        ksort($rows);

        return $rows;
    }

    /**
     * The filled values of one translated attribute of the bundle, keyed by active locale.
     *
     * @param  array<mixed>  $values
     * @param  list<string>  $locales
     * @return array<string, string>
     */
    protected function translatedValues(array $values, array $locales): array
    {
        return array_map(
            fn (array $row): string => (string) $row['value'],
            $this->translationRows(array_map(fn (mixed $value): array => ['value' => $value], $values), ['value'], $locales),
        );
    }

    /**
     * The model fill payload of translations: every active locale, so a locale the bundle leaves out is cleared.
     *
     * @param  array<string, array<string, ?string>>  $rows
     * @param  list<string>  $attributes
     * @param  list<string>  $locales
     * @return array<string, array<string, ?string>>
     */
    protected function translationPayload(array $rows, array $attributes, array $locales): array
    {
        $payload = [];

        foreach ($locales as $locale) {
            foreach ($attributes as $attribute) {
                $payload[$locale][$attribute] = $rows[$locale][$attribute] ?? null;
            }
        }

        return $payload;
    }
}
