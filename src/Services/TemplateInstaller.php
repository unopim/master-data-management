<?php

namespace Webkul\MasterDataManagement\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Webkul\MasterDataManagement\Enums\CodeStrategy;
use Webkul\MasterDataManagement\Enums\EntityTypeStatus;
use Webkul\MasterDataManagement\Models\CodeList;
use Webkul\MasterDataManagement\Models\EntityType;
use Webkul\MasterDataManagement\Models\Field;
use Webkul\MasterDataManagement\Repositories\EntityTypeRepository;
use Webkul\MasterDataManagement\Repositories\FieldRepository;
use Webkul\MasterDataManagement\Repositories\SourceSystemRepository;

/**
 * Installs a domain template: its shared code lists, source systems, entity types with their hierarchy and fields,
 * and optionally its sample records. Installing is additive: what already exists is kept and only what is missing is
 * created, so installing a template again, or several templates sharing code lists, never duplicates anything.
 */
class TemplateInstaller
{
    protected const ENTITY_TYPE_ATTRIBUTES = ['code', 'icon', 'label_field_code', 'is_hierarchical', 'code_strategy', 'code_pattern', 'status', 'sort_order'];

    protected const FIELD_FLAGS = ['is_required', 'is_unique', 'is_identifier', 'is_searchable', 'value_per_locale'];

    public function __construct(
        protected TemplateReader $templateReader,
        protected TemplateValidator $templateValidator,
        protected CodeListInstaller $codeListInstaller,
        protected TemplateSampleInstaller $sampleInstaller,
        protected EntityTypeRepository $entityTypeRepository,
        protected FieldRepository $fieldRepository,
        protected FieldService $fieldService,
        protected SchemaPublisher $schemaPublisher,
        protected SourceSystemRepository $sourceSystemRepository,
    ) {}

    /**
     * The installable templates as select options, labelled in the locale, falling back to the source locale.
     *
     * @return list<array{id: string, label: string}>
     */
    public function options(string $locale): array
    {
        $options = [];

        foreach ($this->templateReader->available() as $name) {
            try {
                $names = $this->templateReader->read($name)['names'] ?? [];
            } catch (InvalidArgumentException) {
                continue;
            }

            $options[] = ['id' => $name, 'label' => (string) ($names[$locale] ?? $names[TemplateReader::SOURCE_LOCALE] ?? $name)];
        }

        return $options;
    }

    /**
     * Install the template in one transaction, so a failing step leaves nothing half installed.
     *
     * @return array{entity_types: int, fields: int, code_lists: int, source_systems: int, records: int} What was created.
     *
     * @throws InvalidArgumentException
     * @throws ValidationException
     */
    public function install(string $name, bool $withSamples = false): array
    {
        $template = $this->templateReader->read($name);

        $this->templateValidator->validateTemplate($template);

        return DB::transaction(fn (): array => $this->apply($template, $withSamples));
    }

    /**
     * Get the entity type with the definition's code, creating it named in every given locale when absent.
     *
     * A type created here reports `wasRecentlyCreated`; an existing type is returned unchanged.
     *
     * @param  array<string, mixed>  $definition
     * @param  list<string>|null  $locales  The locales to name it in; the active locales when null.
     */
    public function ensureEntityType(array $definition, ?array $locales = null): EntityType
    {
        $existing = $this->entityTypeRepository->findOneByField('code', $definition['code']);

        if ($existing instanceof EntityType) {
            return $existing;
        }

        $attributes = array_intersect_key($definition, array_flip(self::ENTITY_TYPE_ATTRIBUTES)) + [
            'code_strategy' => CodeStrategy::IdentifierFields->value,
            'status'        => EntityTypeStatus::Active->value,
        ];

        return $this->entityTypeRepository->create($attributes + $this->templateReader->localized('name', $definition['names'] ?? [], $locales ?? $this->activeLocales()));
    }

    /**
     * @param  array<string, mixed>  $template
     * @return array{entity_types: int, fields: int, code_lists: int, source_systems: int, records: int}
     */
    protected function apply(array $template, bool $withSamples): array
    {
        $locales = $this->activeLocales();

        $totals = ['entity_types' => 0, 'fields' => 0, 'code_lists' => 0, 'source_systems' => 0, 'records' => 0];

        $lists = [];

        foreach ($this->codeListCodes($template) as $code) {
            $lists[$code] = $this->codeListInstaller->ensure($code, $locales);

            $totals['code_lists'] += (int) $lists[$code]->wasRecentlyCreated;
        }

        foreach ($template['source_systems'] ?? [] as $definition) {
            if ($this->sourceSystemRepository->findOneByField('code', $definition['code']) === null) {
                $this->sourceSystemRepository->create($definition);

                $totals['source_systems']++;
            }
        }

        $types = [];
        $definitions = [];

        foreach ($template['entity_types'] as $definition) {
            $types[$definition['code']] = $this->ensureEntityType($definition, $locales);
            $definitions[$definition['code']] = $definition;

            $totals['entity_types'] += (int) $types[$definition['code']]->wasRecentlyCreated;
        }

        foreach ($definitions as $code => $definition) {
            $types[$code] = $this->ensureHierarchy($types[$code], $definition, $types);

            $added = $this->ensureFields($types[$code], $definition['fields'] ?? [], $lists, $types, $locales);

            if ($added > 0 || (int) $types[$code]->schema_version < 1) {
                $this->schemaPublisher->publish($types[$code]);
            }

            $totals['fields'] += $added;
        }

        if ($withSamples) {
            $totals['records'] = $this->sampleInstaller->install($template['samples'] ?? [], $types, $definitions);
        }

        return $totals;
    }

    /**
     * Allow the type under the template's parent types, keeping any parents it already allows.
     *
     * @param  array<string, mixed>  $definition
     * @param  array<string, EntityType>  $types
     */
    protected function ensureHierarchy(EntityType $type, array $definition, array $types): EntityType
    {
        $current = array_map(intval(...), (array) $type->allowed_parent_type_ids);

        $parents = array_values(array_unique(array_merge($current, array_map(
            fn (string $parent): int => (int) $types[$parent]->id,
            $definition['parents'] ?? [],
        ))));

        $hierarchical = (bool) $type->is_hierarchical || (bool) ($definition['is_hierarchical'] ?? false);

        if ($parents === $current && $hierarchical === (bool) $type->is_hierarchical) {
            return $type;
        }

        return $this->entityTypeRepository->update(['is_hierarchical' => $hierarchical, 'allowed_parent_type_ids' => $parents], $type->id);
    }

    /**
     * Create the template fields the type lacks, in template order after its existing fields.
     *
     * @param  list<array<string, mixed>>  $fields
     * @param  array<string, CodeList>  $lists
     * @param  array<string, EntityType>  $types
     * @param  list<string>  $locales
     * @return int The number of fields created.
     */
    protected function ensureFields(EntityType $type, array $fields, array $lists, array $types, array $locales): int
    {
        $added = 0;

        foreach ($fields as $index => $definition) {
            if ($this->fieldRepository->findByCode((int) $type->id, $definition['code']) instanceof Field) {
                continue;
            }

            $flags = [];

            foreach (self::FIELD_FLAGS as $flag) {
                $flags[$flag] = (bool) ($definition[$flag] ?? false);
            }

            $this->fieldService->create($type, [
                'code'                     => $definition['code'],
                'type'                     => $definition['type'],
                'group'                    => $definition['group'] ?? null,
                'position'                 => ($index + 1) * 10,
                'validation'               => $definition['validation'] ?? null,
                'code_list_id'             => isset($definition['code_list']) ? $lists[$definition['code_list']]->id : null,
                'reference_entity_type_id' => isset($definition['reference']) ? $types[$definition['reference']]->id : null,
            ] + $flags + $this->templateReader->localized('label', $definition['labels'], $locales));

            $added++;
        }

        return $added;
    }

    /**
     * The codes of the shared code lists the template's fields draw from, each once.
     *
     * @param  array<string, mixed>  $template
     * @return list<string>
     */
    protected function codeListCodes(array $template): array
    {
        $codes = [];

        foreach ($template['entity_types'] as $definition) {
            foreach ($definition['fields'] ?? [] as $field) {
                if (isset($field['code_list'])) {
                    $codes[$field['code_list']] = true;
                }
            }
        }

        return array_keys($codes);
    }

    /**
     * @return list<string>
     */
    protected function activeLocales(): array
    {
        return core()->getAllActiveLocales()->pluck('code')->all();
    }
}
