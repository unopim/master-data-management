<?php

namespace Webkul\MasterDataManagement\Demo\Services;

use Illuminate\Support\Facades\DB;
use Webkul\MasterDataManagement\Models\EntityType;
use Webkul\MasterDataManagement\Models\Record;
use Webkul\MasterDataManagement\Repositories\EntityTypeRepository;
use Webkul\MasterDataManagement\Repositories\RecordRepository;

/**
 * Installs the manufacturing site reference data demo: model, golden records with their crosswalk, and the
 * stewardship, quality and publishing scenario on top, with ready-to-run import and export jobs.
 */
class DemoInstaller
{
    public const MARKER_TYPE = 'site';

    public const MARKER_RECORD = 'LKV';

    public function __construct(
        protected SchemaInstaller $schemaInstaller,
        protected SiteRecordInstaller $siteRecordInstaller,
        protected StewardshipInstaller $stewardshipInstaller,
        protected PersonaInstaller $personaInstaller,
        protected JobInstaller $jobInstaller,
        protected EntityTypeRepository $entityTypeRepository,
        protected RecordRepository $recordRepository,
    ) {}

    /**
     * Whether the demo's first site already exists.
     */
    public function isInstalled(): bool
    {
        $type = $this->entityTypeRepository->findOneByField('code', self::MARKER_TYPE);

        return $type instanceof EntityType
            && $this->recordRepository->findByCode((int) $type->id, self::MARKER_RECORD) instanceof Record;
    }

    /**
     * Install the demo dataset in one transaction, so a failed step leaves nothing half seeded.
     *
     * @return array<string, int>
     */
    public function install(string $password): array
    {
        return DB::transaction(fn (): array => $this->seed($password));
    }

    /**
     * @return array<string, int>
     */
    protected function seed(string $password): array
    {
        $dataset = $this->dataset();

        $users = $this->personaInstaller->install($dataset['personas'], $password);

        $schema = $this->schemaInstaller->install($dataset, (int) $users['owner']->id);

        $this->personaInstaller->grant($dataset['personas'], $users, $schema['types']);

        $records = $this->siteRecordInstaller->install($dataset, $schema['types'], $schema['systems']);

        $stewardship = $this->stewardshipInstaller->install($dataset, $schema['types'], $schema['systems'], $users['owner'], $users['steward']);

        $jobs = $this->jobInstaller->install($dataset);

        return [
            'users'          => count($users),
            'entity_types'   => count($schema['types']),
            'source_systems' => count($schema['systems']),
            'code_lists'     => count($schema['lists']),
            'aliases'        => $schema['aliases'],
        ] + $records + $stewardship + $jobs;
    }

    /**
     * @return array<string, mixed>
     */
    public function dataset(): array
    {
        return require __DIR__.'/../Resources/data/manufacturing-sites.php';
    }
}
