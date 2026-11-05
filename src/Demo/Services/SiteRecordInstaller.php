<?php

namespace Webkul\MasterDataManagement\Demo\Services;

use Webkul\MasterDataManagement\Demo\Services\Concerns\TranslatesDemoLabels;
use Webkul\MasterDataManagement\Enums\RelationCardinality;
use Webkul\MasterDataManagement\Models\EntityType;
use Webkul\MasterDataManagement\Models\Record;
use Webkul\MasterDataManagement\Models\RelationType;
use Webkul\MasterDataManagement\Models\SourceSystem;
use Webkul\MasterDataManagement\Repositories\RecordRepository;
use Webkul\MasterDataManagement\Repositories\RelationRepository;
use Webkul\MasterDataManagement\Repositories\RelationTypeRepository;
use Webkul\MasterDataManagement\Services\RecordWriter;
use Webkul\MasterDataManagement\Services\RelationWriter;
use Webkul\MasterDataManagement\Services\XrefService;
use Webkul\MasterDataManagement\ValueObjects\SourceContext;

/**
 * Seeds the golden records of the demo: platforms and, per site, departments, lines, areas and equipment, each
 * linked to the differently formatted ids the source systems know it by, plus the process flow between equipment.
 *
 * A few deliberate gaps (a missing CMMS id, an unset criticality, a near-duplicate asset) give the quality and
 * stewardship screens something to show.
 */
class SiteRecordInstaller
{
    use TranslatesDemoLabels;

    public const FLOW_RELATION = 'feeds';

    /**
     * @var array{records: int, xrefs: int, relations: int}
     */
    protected array $totals = ['records' => 0, 'xrefs' => 0, 'relations' => 0];

    public function __construct(
        protected RecordRepository $recordRepository,
        protected RecordWriter $recordWriter,
        protected XrefService $xrefService,
        protected RelationTypeRepository $relationTypeRepository,
        protected RelationWriter $relationWriter,
        protected RelationRepository $relationRepository,
    ) {}

    /**
     * @param  array<string, mixed>  $dataset
     * @param  array<string, EntityType>  $types
     * @param  array<string, SourceSystem>  $systems
     * @return array{records: int, xrefs: int, relations: int}
     */
    public function install(array $dataset, array $types, array $systems): array
    {
        $this->totals = ['records' => 0, 'xrefs' => 0, 'relations' => 0];

        $source = new SourceContext('seeder');

        $platforms = [];

        foreach ($dataset['platforms'] as $platform) {
            $platforms[$platform['code']] = $this->ensureRecord($types['platform'], $platform['code'], null, [
                'name'           => $platform['name'],
                'category_group' => $platform['category_group'],
                'description'    => $platform['description'],
            ], $source);

            $this->link($platforms[$platform['code']], $systems, $platform['xrefs']);
        }

        $flow = $this->flowRelation($types['equipment']);

        $gaps = $dataset['gaps'];

        $departmentSap = array_column($dataset['departments'], 'sap', 'suffix');

        $serial = 0;

        foreach ($dataset['sites'] as $site) {
            $siteName = explode(' ', $site['name'])[0];

            $sapPlant = (int) $site['sap'];

            $siteRecord = $this->ensureRecord($types['site'], $site['code'], null, array_intersect_key($site, array_flip([
                'name', 'site_type', 'region', 'country', 'city', 'timezone', 'gln', 'site_manager_email', 'operational_since',
            ])), $source);

            $this->link($siteRecord, $systems, [
                'sap_s4' => $site['sap'],
                'mes'    => $site['mes'],
                'maximo' => $site['maximo'],
                'pi_af'  => sprintf('\\\\%s\\%s', $site['pi'], $siteName),
                'ehs'    => $site['ehs'],
            ]);

            $departments = [];

            foreach ($dataset['departments'] as $index => $department) {
                $code = $site['code'].'-'.$department['suffix'];

                $departments[$department['suffix']] = $this->ensureRecord($types['department'], $code, $siteRecord, [
                    'name'        => $site['name'].' '.$department['name'],
                    'function'    => $department['function'],
                    'cost_center' => sprintf('CC-%s%d', $site['sap'], $index + 1),
                ], $source);

                $this->link($departments[$department['suffix']], $systems, [
                    'sap_s4' => sprintf('%s-%s', $site['sap'], $department['sap']),
                    'mes'    => sprintf('%s.%s', $site['mes'], $department['mes']),
                    'maximo' => sprintf('%s-%s', $site['code'], $department['maximo']),
                ]);
            }

            foreach ($dataset['lines'] as $lineIndex => $line) {
                $lineCode = sprintf('%s-L%02d', $site['code'], $line['number']);

                $commissionYear = 2010 + $lineIndex * 3;

                $lineRecord = $this->ensureRecord($types['line'], $lineCode, $departments[$line['department']], [
                    'name'             => sprintf('%s Line %d %s', $siteName, $line['number'], $line['label']),
                    'line_type'        => $line['line_type'],
                    'platform'         => $platforms[$site['platform']]->uuid,
                    'rated_speed_ppm'  => $line['speed'],
                    'shifts_per_day'   => $line['shifts'],
                    'operating_status' => 'running',
                    'commissioned_on'  => sprintf('%d-03-01', $commissionYear),
                ], $source);

                $lineXrefs = [
                    'sap_s4' => sprintf('%s-%s-L%02d', $site['sap'], $departmentSap[$line['department']], $line['number']),
                    'mes'    => sprintf('%s.%s.L%d', $site['mes'], strtoupper($line['label']), $line['number']),
                    'maximo' => sprintf('%s-LN%02d', $site['code'], $line['number']),
                    'pi_af'  => sprintf('\\\\%s\\%s\\Line %d', $site['pi'], $siteName, $line['number']),
                ];

                if (in_array($lineCode, $gaps['missing_mes_line'], true)) {
                    unset($lineXrefs['mes']);
                }

                $this->link($lineRecord, $systems, $lineXrefs);

                $previous = null;

                foreach ($line['areas'] as $areaIndex => $area) {
                    $areaCode = sprintf('%s-A%d', $lineCode, $areaIndex + 1);

                    $areaRecord = $this->ensureRecord($types['area'], $areaCode, $lineRecord, [
                        'name'           => sprintf('%s L%d %s', $siteName, $line['number'], $area['name']),
                        'classification' => $area['classification'],
                        'floor_space_m2' => $area['space'],
                    ], $source);

                    foreach ($area['equipment'] as $equipmentIndex => [$name, $class, $manufacturer, $model]) {
                        $serial++;

                        $equipmentCode = sprintf('%s-E%d', $areaCode, $equipmentIndex + 1);

                        $values = [
                            'name'             => sprintf('%s L%d %s', $siteName, $line['number'], $name),
                            'equipment_class'  => $class,
                            'manufacturer'     => $manufacturer,
                            'model'            => $model,
                            'serial_number'    => sprintf('%s-%s-%05d', strtoupper(substr($manufacturer, 0, 3)), $model, 1000 + $serial),
                            'criticality'      => in_array($class, ['dosing_skid', 'mixer', 'filler'], true) ? 'A' : ($class === 'checkweigher' ? 'B' : 'C'),
                            'operating_status' => $serial % 9 === 0 ? 'maintenance' : 'running',
                            'install_date'     => sprintf('%d-%02d-15', $commissionYear + $serial % 4, 3 + $serial % 10),
                        ];

                        if (in_array($equipmentCode, $gaps['missing_criticality'], true)) {
                            unset($values['criticality']);
                        }

                        $equipment = $this->ensureRecord($types['equipment'], $equipmentCode, $areaRecord, $values, $source);

                        $xrefs = [
                            'sap_s4' => sprintf('1%07d', $sapPlant * 1000 + $serial),
                            'mes'    => sprintf('%s_L%d_%s%02d', $site['mes'], $line['number'], strtoupper(substr($class, 0, 4)), $equipmentIndex + 1),
                            'maximo' => sprintf('A-%07d', $sapPlant * 100 + $serial),
                            'pi_af'  => sprintf('\\\\%s\\%s\\Line %d\\%s', $site['pi'], $siteName, $line['number'], $name),
                        ];

                        if (in_array($equipmentCode, $gaps['missing_maximo'], true)) {
                            unset($xrefs['maximo']);
                        }

                        $this->link($equipment, $systems, $xrefs);

                        if ($previous instanceof Record) {
                            $this->flow($flow, $previous, $equipment);
                        }

                        $previous = $equipment;
                    }
                }
            }
        }

        foreach ($gaps['duplicates'] as $duplicate) {
            $parent = $this->recordRepository->findByCode((int) $types['area']->id, $duplicate['parent']);

            $record = $this->ensureRecord($types['equipment'], $duplicate['code'], $parent, [
                'name'            => $duplicate['name'],
                'equipment_class' => $duplicate['class'],
            ], $source);

            $this->link($record, $systems, ['maximo' => $duplicate['maximo']]);
        }

        return $this->totals;
    }

    /**
     * Create the record below its parent unless a record of the type already has the code.
     *
     * @param  array<string, mixed>  $values
     */
    protected function ensureRecord(EntityType $type, string $code, ?Record $parent, array $values, SourceContext $source): Record
    {
        $this->totals['records']++;

        return $this->recordRepository->findByCode((int) $type->id, $code)
            ?? $this->recordWriter->create($type, [
                'code'   => $code,
                'label'  => $values['name'],
                'parent' => $parent?->uuid,
                'values' => ['common' => $values],
            ], null, $source);
    }

    /**
     * @param  array<string, SourceSystem>  $systems
     * @param  array<string, string>  $externalIds
     */
    protected function link(Record $record, array $systems, array $externalIds): void
    {
        foreach ($externalIds as $system => $externalId) {
            $this->xrefService->attach($record, $systems[$system], $externalId);

            $this->totals['xrefs']++;
        }
    }

    protected function flowRelation(EntityType $equipment): RelationType
    {
        return $this->relationTypeRepository->findOneByField('code', self::FLOW_RELATION)
            ?? $this->relationTypeRepository->create([
                'code'                => self::FLOW_RELATION,
                'from_entity_type_id' => $equipment->id,
                'to_entity_type_id'   => $equipment->id,
                'cardinality'         => RelationCardinality::Many->value,
            ] + $this->translated('label', 'Feeds (process flow)'));
    }

    protected function flow(RelationType $type, Record $from, Record $to): void
    {
        $targets = $this->relationRepository->targetsByType((int) $from->id, [(int) $type->id])[(int) $type->id] ?? [];

        if (! in_array((int) $to->id, $targets, true)) {
            $this->relationWriter->link($type, $from, $to);
        }

        $this->totals['relations']++;
    }
}
