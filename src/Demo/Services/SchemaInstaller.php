<?php

namespace Webkul\MasterDataManagement\Demo\Services;

use Webkul\MasterDataManagement\Demo\Services\Concerns\TranslatesDemoLabels;
use Webkul\MasterDataManagement\Enums\AliasScope;
use Webkul\MasterDataManagement\Enums\CodeListVersionStatus;
use Webkul\MasterDataManagement\Enums\EntityTypeStatus;
use Webkul\MasterDataManagement\Models\CodeList;
use Webkul\MasterDataManagement\Models\EntityType;
use Webkul\MasterDataManagement\Models\Field;
use Webkul\MasterDataManagement\Models\SourceSystem;
use Webkul\MasterDataManagement\Repositories\AliasRepository;
use Webkul\MasterDataManagement\Repositories\CodeListRepository;
use Webkul\MasterDataManagement\Repositories\CodeListVersionRepository;
use Webkul\MasterDataManagement\Repositories\CodeRepository;
use Webkul\MasterDataManagement\Repositories\EntityTypeRepository;
use Webkul\MasterDataManagement\Repositories\FieldRepository;
use Webkul\MasterDataManagement\Repositories\SourceSystemRepository;
use Webkul\MasterDataManagement\Services\CodeListPublisher;
use Webkul\MasterDataManagement\Services\FieldService;
use Webkul\MasterDataManagement\Services\Normalizer;
use Webkul\MasterDataManagement\Services\SchemaPublisher;

/**
 * Seeds the model of the site reference data demo: source systems, versioned code lists with their aliases, and the
 * governed entity types with their fields and hierarchy. Every step reuses what already exists.
 */
class SchemaInstaller
{
    use TranslatesDemoLabels;

    public function __construct(
        protected SourceSystemRepository $sourceSystemRepository,
        protected CodeListRepository $codeListRepository,
        protected CodeListVersionRepository $versionRepository,
        protected CodeRepository $codeRepository,
        protected CodeListPublisher $codeListPublisher,
        protected AliasRepository $aliasRepository,
        protected EntityTypeRepository $entityTypeRepository,
        protected FieldRepository $fieldRepository,
        protected FieldService $fieldService,
        protected SchemaPublisher $schemaPublisher,
        protected Normalizer $normalizer,
    ) {}

    /**
     * @param  array<string, mixed>  $dataset
     * @return array{systems: array<string, SourceSystem>, lists: array<string, CodeList>, types: array<string, EntityType>, aliases: int}
     */
    public function install(array $dataset, ?int $dataOwnerId): array
    {
        $systems = [];

        foreach ($dataset['source_systems'] as $definition) {
            $systems[$definition['code']] = $this->sourceSystemRepository->findOneByField('code', $definition['code'])
                ?? $this->sourceSystemRepository->create($definition);
        }

        $lists = [];

        foreach ($dataset['code_lists'] as $code => $definition) {
            $lists[$code] = $this->ensureCodeList($code, $definition['name'], $definition['codes'], $definition['draft'] ?? []);
        }

        $aliases = 0;

        foreach ($dataset['aliases'] as $alias) {
            $aliases += (int) $this->ensureAlias($lists[$alias['list']], $alias['term'], $alias['target'], $systems[$alias['system'] ?? ''] ?? null);
        }

        $types = [];

        foreach ($dataset['entity_types'] as $code => $definition) {
            $types[$code] = $this->ensureEntityType($code, $definition, $systems, $dataOwnerId);
        }

        foreach ($dataset['entity_types'] as $code => $definition) {
            $parents = array_map(fn (string $parent): int => (int) $types[$parent]->id, $definition['parents']);

            $types[$code] = $this->entityTypeRepository->update([
                'data_owner_id'           => $dataOwnerId ?? $types[$code]->data_owner_id,
                'is_hierarchical'         => $parents !== [] || $code === 'site',
                'allowed_parent_type_ids' => $parents,
                'sort_order'              => $definition['sort_order'],
                'icon'                    => $definition['icon'],
            ] + $this->translated('description', $definition['description']), $types[$code]->id);

            $position = 0;

            foreach ($definition['fields'] as $field) {
                $this->ensureField($types[$code], $field, ++$position, $lists, $types);
            }

            if ((int) $types[$code]->schema_version < 1) {
                $this->schemaPublisher->publish($types[$code]);
            }

            $types[$code] = $types[$code]->refresh();
        }

        return ['systems' => $systems, 'lists' => $lists, 'types' => $types, 'aliases' => $aliases];
    }

    /**
     * @param  array<string, string>  $codes
     * @param  array<string, string>  $draftCodes
     */
    protected function ensureCodeList(string $code, string $name, array $codes, array $draftCodes): CodeList
    {
        $list = $this->codeListRepository->findOneByField('code', $code)
            ?? $this->codeListRepository->create(['code' => $code, 'owner' => 'Site master data office'] + $this->translated('name', $name));

        $versions = $this->versionRepository->allFor($list->id);

        if ($versions->isNotEmpty()) {
            return $list;
        }

        $first = $this->codeListRepository->createDraftFrom($list);

        $this->addCodes($first->id, $codes);

        $first = $this->codeListPublisher->publish($first);

        if ($draftCodes !== [] && $first->status === CodeListVersionStatus::Published) {
            $this->addCodes($this->codeListRepository->createDraftFrom($list, $first)->id, $draftCodes, count($codes));
        }

        return $list;
    }

    /**
     * @param  array<string, string>  $codes
     */
    protected function addCodes(int $versionId, array $codes, int $sort = 0): void
    {
        foreach ($codes as $code => $label) {
            $this->codeRepository->create([
                'version_id' => $versionId,
                'code'       => (string) $code,
                'sort'       => ++$sort,
            ] + $this->translated('label', $label));
        }
    }

    protected function ensureAlias(CodeList $list, string $term, string $target, ?SourceSystem $system): bool
    {
        if ($this->aliasRepository->duplicateExists(AliasScope::CodeList, (int) $list->id, $this->normalizer->normalize($term), $system?->id)) {
            return false;
        }

        $this->aliasRepository->create([
            'scope_type'       => AliasScope::CodeList->value,
            'scope_id'         => $list->id,
            'term'             => $term,
            'target'           => $target,
            'source_system_id' => $system?->id,
        ]);

        return true;
    }

    /**
     * Create the entity type active, owned by the data owner; governed types hold changes for approval and
     * require external ids in the listed source systems. The install loop keeps the data owner, sort order, icon
     * and description of an existing type in step with the dataset.
     *
     * @param  array<string, mixed>  $definition
     * @param  array<string, SourceSystem>  $systems
     */
    protected function ensureEntityType(string $code, array $definition, array $systems, ?int $dataOwnerId): EntityType
    {
        $existing = $this->entityTypeRepository->findOneByField('code', $code);

        if ($existing instanceof EntityType) {
            return $existing;
        }

        $governed = (bool) ($definition['governed'] ?? false);

        return $this->entityTypeRepository->create([
            'code'              => $code,
            'icon'              => $definition['icon'],
            'sort_order'        => $definition['sort_order'],
            'label_field_code'  => 'name',
            'code_strategy'     => 'identifier_fields',
            'status'            => EntityTypeStatus::Active->value,
            'approval_required' => $governed && $dataOwnerId !== null,
            'data_owner_id'     => $dataOwnerId,
            'settings'          => [
                'completeness_scope'         => 'all',
                'required_source_system_ids' => array_map(
                    fn (string $system): int => (int) $systems[$system]->id,
                    $definition['required_systems'] ?? [],
                ),
            ],
        ] + array_merge_recursive($this->translated('name', $definition['name']), $this->translated('description', $definition['description'])));
    }

    /**
     * @param  array<string, mixed>  $definition
     * @param  array<string, CodeList>  $lists
     * @param  array<string, EntityType>  $types
     */
    protected function ensureField(EntityType $type, array $definition, int $position, array $lists, array $types): void
    {
        if ($this->fieldRepository->findByCode($type->id, $definition['code']) instanceof Field) {
            return;
        }

        $this->fieldService->create($type, [
            'code'                     => $definition['code'],
            'type'                     => $definition['type'],
            'position'                 => $position * 10,
            'is_required'              => (bool) ($definition['is_required'] ?? false),
            'is_unique'                => (bool) ($definition['is_unique'] ?? false),
            'is_searchable'            => (bool) ($definition['is_searchable'] ?? false),
            'validation'               => $definition['validation'] ?? null,
            'code_list_id'             => isset($definition['list']) ? $lists[$definition['list']]->id : null,
            'reference_entity_type_id' => isset($definition['reference']) ? $types[$definition['reference']]->id : null,
        ] + $this->translated('label', $definition['label']));
    }
}
