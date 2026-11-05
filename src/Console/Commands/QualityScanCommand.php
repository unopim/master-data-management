<?php

namespace Webkul\MasterDataManagement\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Webkul\MasterDataManagement\Jobs\RunQualityScan;
use Webkul\MasterDataManagement\Models\EntityType;
use Webkul\MasterDataManagement\Quality\QualityScanner;
use Webkul\MasterDataManagement\Repositories\EntityTypeRepository;

#[Description('Recompute the completeness and data quality issues of master data records')]
#[Signature('masterdata:quality:scan
        {entity_type? : The entity type code; every active entity type when left out}
        {--now : Scan in this process instead of queueing the scan}')]
class QualityScanCommand extends Command
{
    /**
     * Queue, or run, a scan per entity type.
     */
    public function handle(EntityTypeRepository $entityTypeRepository, QualityScanner $qualityScanner): int
    {
        $code = $this->argument('entity_type');

        if (is_string($code) && $code !== '') {
            $type = $entityTypeRepository->findOneByField('code', $code);

            if (! $type instanceof EntityType) {
                $this->components->error(trans('masterdata::app.quality.scan.unknown-type', ['code' => $code]));

                return self::FAILURE;
            }

            $types = [$type];
        } else {
            $types = $entityTypeRepository->listActive()->all();
        }

        foreach ($types as $type) {
            if ($this->option('now')) {
                $checked = $qualityScanner->scan((int) $type->id);

                $this->components->info(trans('masterdata::app.quality.scan.done', ['type' => $type->code, 'count' => $checked]));

                continue;
            }

            dispatch(new RunQualityScan((int) $type->id));

            $this->components->info(trans('masterdata::app.quality.scan.queued', ['type' => $type->code]));
        }

        return self::SUCCESS;
    }
}
