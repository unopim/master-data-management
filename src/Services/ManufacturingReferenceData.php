<?php

namespace Webkul\MasterDataManagement\Services;

use Webkul\MasterDataManagement\Enums\AliasScope;
use Webkul\MasterDataManagement\Enums\CodeListVersionStatus;
use Webkul\MasterDataManagement\Models\CodeList;
use Webkul\MasterDataManagement\Models\CodeListVersion;
use Webkul\MasterDataManagement\Models\EntityType;
use Webkul\MasterDataManagement\Models\Field;
use Webkul\MasterDataManagement\Models\SourceSystem;
use Webkul\MasterDataManagement\Repositories\AliasRepository;
use Webkul\MasterDataManagement\Repositories\CodeListRepository;
use Webkul\MasterDataManagement\Repositories\CodeListVersionRepository;
use Webkul\MasterDataManagement\Repositories\CodeRepository;
use Webkul\MasterDataManagement\Repositories\FieldRepository;

/**
 * Seeds the approved values of the manufacturing demo: code lists with versions, select fields bound to them
 * and the aliases that resolve free-text terms to codes. Every step skips what already exists.
 */
class ManufacturingReferenceData
{
    public const LINE_TYPES = [
        'assembly' => 'Assembly',
        'packing'  => 'Packing',
        'filling'  => 'Filling',
        'mixing'   => 'Mixing',
    ];

    public const DRAFT_LINE_TYPES = ['palletizing' => 'Palletizing'];

    public const EQUIPMENT_STATUSES = [
        'running'        => 'Running',
        'idle'           => 'Idle',
        'maintenance'    => 'Maintenance',
        'decommissioned' => 'Decommissioned',
    ];

    public function __construct(
        protected CodeListRepository $codeListRepository,
        protected CodeListVersionRepository $versionRepository,
        protected CodeRepository $codeRepository,
        protected CodeListPublisher $publisher,
        protected AliasRepository $aliasRepository,
        protected FieldRepository $fieldRepository,
        protected FieldService $fieldService,
        protected SchemaPublisher $schemaPublisher,
    ) {}

    /**
     * Seed the code lists, fields and aliases and publish the schema of the types that gained fields.
     *
     * @param  array<string, EntityType>  $types
     * @param  array<string, SourceSystem>  $systems
     * @return array{code_lists: int, fields: int, aliases: int}
     */
    public function run(array $types, array $systems): array
    {
        $lineTypes = $this->ensureCodeList('line_type', 'Line type', self::LINE_TYPES, self::DRAFT_LINE_TYPES);
        $statuses = $this->ensureCodeList('equipment_status', 'Equipment status', self::EQUIPMENT_STATUSES);

        $fields = [
            ['line', $this->ensureField($types['line'], 'line_type', 'Line type', 'select', $lineTypes)],
            ['line', $this->ensureField($types['line'], 'platform_code', 'Platform code', 'text')],
            ['equipment', $this->ensureField($types['equipment'], 'status', 'Status', 'select', $statuses)],
        ];

        $created = array_fill_keys(array_unique(array_column($fields, 0)), false);

        foreach ($fields as [$code, $wasCreated]) {
            $created[$code] = $created[$code] || $wasCreated;
        }

        $aliases = [
            $this->ensureAlias(AliasScope::CodeList, $lineTypes->id, 'PL', 'assembly'),
            $this->ensureAlias(AliasScope::CodeList, $lineTypes->id, 'Line', 'assembly'),
            $this->ensureAlias(AliasScope::CodeList, $lineTypes->id, 'Production Line', 'assembly'),
            $this->ensureAlias(AliasScope::CodeList, $lineTypes->id, 'LN', 'packing', $systems['mes']),
            $this->ensureAlias(AliasScope::Global, null, 'Pkg', 'packing'),
        ];

        foreach ($created as $code => $wasCreated) {
            if ($wasCreated || (int) $types[$code]->schema_version < 1) {
                $this->schemaPublisher->publish($types[$code]);
            }
        }

        return [
            'code_lists' => 2,
            'fields'     => count($fields),
            'aliases'    => count($aliases),
        ];
    }

    /**
     * Create the list with a published v1 holding the codes; an optional second draft version adds more codes.
     *
     * @param  array<string, string>  $codes
     * @param  array<string, string>  $draftCodes
     */
    protected function ensureCodeList(string $code, string $name, array $codes, array $draftCodes = []): CodeList
    {
        $list = $this->codeListRepository->findOneByField('code', $code);

        if (! $list instanceof CodeList) {
            $list = $this->codeListRepository->create(['code' => $code, 'owner' => 'Master data team'] + $this->translated('name', $name));
        }

        $versions = $this->versionRepository->allFor($list->id);

        $first = $versions->first() ?? $this->codeListRepository->createDraftFrom($list);

        if ($versions->isEmpty()) {
            $this->addCodes($first, $codes);

            $first = $this->publisher->publish($first);
        }

        if ($draftCodes !== [] && $versions->count() < 2 && $first->status === CodeListVersionStatus::Published) {
            $this->addCodes($this->codeListRepository->createDraftFrom($list, $first), $draftCodes);
        }

        return $list;
    }

    /**
     * @param  array<string, string>  $codes
     */
    protected function addCodes(CodeListVersion $version, array $codes): void
    {
        $sort = 0;

        foreach ($codes as $code => $label) {
            $this->codeRepository->create([
                'version_id' => $version->id,
                'code'       => $code,
                'sort'       => ++$sort,
            ] + $this->translated('label', $label));
        }
    }

    /**
     * Create a field, bound to the code list when it has one, unless the entity type already has it.
     */
    protected function ensureField(EntityType $type, string $code, string $label, string $fieldType, ?CodeList $list = null): bool
    {
        if ($this->fieldRepository->findByCode($type->id, $code) instanceof Field) {
            return false;
        }

        $this->fieldService->create($type, [
            'code'         => $code,
            'type'         => $fieldType,
            'code_list_id' => $list?->id,
            'position'     => 10,
        ] + $this->translated('label', $label));

        return true;
    }

    protected function ensureAlias(AliasScope $scope, ?int $scopeId, string $term, string $target, ?SourceSystem $system = null): bool
    {
        $normalized = resolve(Normalizer::class)->normalize($term);

        if ($this->aliasRepository->duplicateExists($scope, $scopeId, $normalized, $system?->id)) {
            return false;
        }

        $this->aliasRepository->create([
            'scope_type'       => $scope->value,
            'scope_id'         => $scopeId,
            'term'             => $term,
            'target'           => $target,
            'source_system_id' => $system?->id,
        ]);

        return true;
    }

    /**
     * Locale-keyed attributes for the active en_US locale, or none when it is not active.
     *
     * @return array<string, array<string, string>>
     */
    protected function translated(string $attribute, string $value): array
    {
        $active = core()->getAllActiveLocales()->pluck('code')->all();

        return in_array('en_US', $active, true) ? ['en_US' => [$attribute => $value]] : [];
    }
}
