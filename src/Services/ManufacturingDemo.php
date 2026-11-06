<?php

namespace Webkul\MasterDataManagement\Services;

use Webkul\MasterDataManagement\Models\EntityType;
use Webkul\MasterDataManagement\Models\Record;
use Webkul\MasterDataManagement\Models\SourceSystem;
use Webkul\MasterDataManagement\Repositories\EntityTypeRepository;
use Webkul\MasterDataManagement\Repositories\RecordRepository;
use Webkul\MasterDataManagement\Repositories\SourceSystemRepository;
use Webkul\MasterDataManagement\ValueObjects\SourceContext;

class ManufacturingDemo
{
    protected const TEMPLATES = ['location', 'asset'];

    protected const SITE_CODE = 'SITE-01';

    protected const LINES = 4;

    protected const AREAS_PER_LINE = 2;

    protected const EQUIPMENT_PER_AREA = 2;

    protected const ALLOWED_PARENTS = [
        'site'       => [],
        'department' => ['site'],
        'line'       => ['department', 'site'],
        'area'       => ['line'],
        'equipment'  => ['area'],
    ];

    protected const DEPARTMENTS = [
        'DEPT-MAKING'    => 'Making',
        'DEPT-PACKING'   => 'Packing',
        'DEPT-UTILITIES' => 'Utilities',
        'DEPT-WAREHOUSE' => 'Warehouse',
    ];

    protected const LINE_DEPARTMENTS = ['DEPT-PACKING', 'DEPT-PACKING', 'DEPT-MAKING', 'DEPT-MAKING'];

    protected const PLATFORMS = [
        'PLAT-LIQUID' => 'Liquid Detergent',
        'PLAT-POWDER' => 'Powder',
        'PLAT-PODS'   => 'Pods',
    ];

    protected const LINE_PLATFORMS = ['PLAT-POWDER', 'PLAT-PODS', 'PLAT-LIQUID', 'PLAT-LIQUID'];

    protected const LINE_TYPE_ORDER = ['assembly', 'packing', 'filling', 'mixing'];

    protected const EQUIPMENT_STATUS_ORDER = ['running', 'running', 'idle', 'maintenance'];

    protected const DECOMMISSIONED_EQUIPMENT = 16;

    public function __construct(
        protected TemplateReader $templateReader,
        protected EntityTypeRepository $entityTypeRepository,
        protected SourceSystemRepository $sourceSystemRepository,
        protected RecordRepository $recordRepository,
        protected RecordWriter $recordWriter,
        protected XrefService $xrefService,
        protected ManufacturingReferenceData $referenceData,
        protected TemplateInstaller $templateInstaller,
    ) {}

    /**
     * Seed the manufacturing model and sample data; running it again changes nothing.
     *
     * Existing entity types, source systems, records, code lists, fields and aliases are kept; records that are not
     * yet in the tree are moved under their parent and gain the demo field values. The parent code stays as
     * values.common.parent_code for readers of the flat value.
     *
     * @return array{entity_types: int, source_systems: int, records: int, xrefs: int, code_lists: int, fields: int, aliases: int}
     */
    public function run(): array
    {
        $totals = ['entity_types' => 0, 'source_systems' => 0, 'records' => 0, 'xrefs' => 0, 'code_lists' => 0, 'fields' => 0, 'aliases' => 0];

        $types = [];
        $systems = [];

        foreach (self::TEMPLATES as $template) {
            $data = $this->templateReader->read($template);

            foreach ($data['entity_types'] ?? [] as $definition) {
                $types[$definition['code']] = $this->templateInstaller->ensureEntityType($definition);
                $totals['entity_types']++;
            }

            foreach ($data['source_systems'] ?? [] as $definition) {
                $systems[$definition['code']] = $this->ensureSourceSystem($definition);
                $totals['source_systems']++;
            }
        }

        $types = $this->ensureHierarchy($types);

        $source = new SourceContext('seeder');

        $records = [];

        $sample = $this->sample();

        foreach ($sample as $item) {
            $record = $records[$item['code']] = $this->ensureRecord($types[$item['type']], $item, $records[$item['parent_code']] ?? null, $source);
            $totals['records']++;

            foreach ($item['xrefs'] as $systemCode => $externalId) {
                $this->xrefService->attach($record, $systems[$systemCode], $externalId);
                $totals['xrefs']++;
            }
        }

        $totals = array_replace($totals, $this->referenceData->run($types, $systems));

        foreach ($sample as $item) {
            $this->ensureValues($records[$item['code']], $item['values'], $source);
        }

        return $totals;
    }

    /**
     * Build the sample records in parent-first order, each with its intended parent code and xrefs.
     *
     * @return list<array{type: string, code: string, name: string, parent_code: ?string, xrefs: array<string, string>, values: array<string, string>}>
     */
    protected function sample(): array
    {
        $items = [[
            'type'        => 'site',
            'code'        => self::SITE_CODE,
            'name'        => 'Main Plant',
            'parent_code' => null,
            'xrefs'       => [],
            'values'      => [],
        ]];

        foreach (self::DEPARTMENTS as $code => $name) {
            $items[] = [
                'type'        => 'department',
                'code'        => $code,
                'name'        => $name,
                'parent_code' => self::SITE_CODE,
                'xrefs'       => [],
                'values'      => [],
            ];
        }

        $platformNumber = 0;

        foreach (self::PLATFORMS as $code => $name) {
            $platformNumber++;

            $items[] = [
                'type'        => 'platform',
                'code'        => $code,
                'name'        => $name,
                'parent_code' => null,
                'xrefs'       => [
                    'erp'  => sprintf('4711-P%02d', $platformNumber),
                    'mes'  => sprintf('PLAT_%02d', $platformNumber),
                    'cmms' => sprintf('CM-P%d', $platformNumber),
                ],
                'values' => [],
            ];
        }

        $equipmentNumber = 0;

        for ($line = 1; $line <= self::LINES; $line++) {
            $lineCode = sprintf('LINE-%02d', $line);

            $items[] = [
                'type'        => 'line',
                'code'        => $lineCode,
                'name'        => sprintf('Line %02d', $line),
                'parent_code' => self::LINE_DEPARTMENTS[($line - 1) % count(self::LINE_DEPARTMENTS)],
                'xrefs'       => [
                    'erp'  => sprintf('4711-L%02d', $line),
                    'mes'  => sprintf('LINE_%02d', $line),
                    'cmms' => sprintf('CM-L%d', $line),
                ],
                'values' => [
                    'line_type'     => self::LINE_TYPE_ORDER[($line - 1) % count(self::LINE_TYPE_ORDER)],
                    'platform_code' => self::LINE_PLATFORMS[($line - 1) % count(self::LINE_PLATFORMS)],
                ],
            ];

            for ($area = 1; $area <= self::AREAS_PER_LINE; $area++) {
                $areaCode = sprintf('%s-A%d', $lineCode, $area);

                $items[] = [
                    'type'        => 'area',
                    'code'        => $areaCode,
                    'name'        => sprintf('Area %02d-A%d', $line, $area),
                    'parent_code' => $lineCode,
                    'xrefs'       => [],
                    'values'      => [],
                ];

                for ($equipment = 1; $equipment <= self::EQUIPMENT_PER_AREA; $equipment++) {
                    $equipmentNumber++;

                    $items[] = [
                        'type'        => 'equipment',
                        'code'        => sprintf('%s-E%d', $areaCode, $equipment),
                        'name'        => sprintf('Equipment %02d-A%d-E%d', $line, $area, $equipment),
                        'parent_code' => $areaCode,
                        'xrefs'       => [
                            'erp'  => sprintf('4711-E%02d', $equipmentNumber),
                            'mes'  => sprintf('EQ_%02d', $equipmentNumber),
                            'cmms' => sprintf('CM-EQ%d', $equipmentNumber),
                        ],
                        'values' => ['status' => $equipmentNumber === self::DECOMMISSIONED_EQUIPMENT
                            ? 'decommissioned'
                            : self::EQUIPMENT_STATUS_ORDER[$equipmentNumber % count(self::EQUIPMENT_STATUS_ORDER)]],
                    ];
                }
            }
        }

        return $items;
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    protected function ensureSourceSystem(array $definition): SourceSystem
    {
        return $this->sourceSystemRepository->findOneByField('code', $definition['code'])
            ?? $this->sourceSystemRepository->create($definition);
    }

    /**
     * Make the hierarchical entity types hierarchical and allow each to sit under its parent types.
     *
     * @param  array<string, EntityType>  $types
     * @return array<string, EntityType> The types with their current hierarchy settings.
     */
    protected function ensureHierarchy(array $types): array
    {
        foreach ($types as $code => $type) {
            $hierarchical = array_key_exists($code, self::ALLOWED_PARENTS);

            $allowed = array_values(array_map(
                fn (string $parent): int => $types[$parent]->id,
                self::ALLOWED_PARENTS[$code] ?? [],
            ));

            $current = array_map(intval(...), (array) $type->allowed_parent_type_ids);

            sort($allowed);
            sort($current);

            if ((bool) $type->is_hierarchical === $hierarchical && $current === $allowed) {
                continue;
            }

            $types[$code] = $this->entityTypeRepository->update(['is_hierarchical' => $hierarchical, 'allowed_parent_type_ids' => $allowed], $type->id);
        }

        return $types;
    }

    /**
     * Create the record under its parent, or move an existing one into its place in the tree.
     *
     * Field values come later, once the entity types have their fields: a type with fields rejects other keys.
     *
     * @param  array{code: string, name: string}  $item
     */
    protected function ensureRecord(EntityType $type, array $item, ?Record $parent, SourceContext $source): Record
    {
        $existing = $this->recordRepository->findByCode($type->id, $item['code']);

        if (! $existing instanceof Record) {
            return $this->recordWriter->create($type, [
                'code'   => $item['code'],
                'label'  => $item['name'],
                'parent' => $parent?->uuid,
                'values' => ['common' => ['name' => $item['name']]],
            ], null, $source);
        }

        if ($existing->parent_id === $parent?->id) {
            return $existing;
        }

        return $this->recordWriter->update($existing, ['parent' => $parent?->uuid], (int) $existing->revision, null, $source);
    }

    /**
     * Write the demo field values the record does not hold yet.
     *
     * @param  array<string, string>  $values
     */
    protected function ensureValues(Record $record, array $values, SourceContext $source): void
    {
        $record->refresh();

        if ($values === [] || array_diff_assoc($values, $record->values['common'] ?? []) === []) {
            return;
        }

        $this->recordWriter->update($record, ['values' => ['common' => $values]], (int) $record->revision, null, $source);
    }
}
