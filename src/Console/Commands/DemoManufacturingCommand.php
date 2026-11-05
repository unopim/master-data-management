<?php

namespace Webkul\MasterDataManagement\Console\Commands;

use Illuminate\Console\Command;
use InvalidArgumentException;
use Webkul\Core\Platform\Module;
use Webkul\Core\Platform\ModuleGate;
use Webkul\MasterDataManagement\Services\ManufacturingDemo;

class DemoManufacturingCommand extends Command
{
    protected $signature = 'masterdata:demo:manufacturing';

    protected $description = 'Seed the manufacturing master data sample: site hierarchy, source systems, crosswalk, code lists and aliases';

    /**
     * Seed the sample; safe to run repeatedly.
     *
     * Seeds even while MDM is switched off, only hinting how to enable it, so the
     * command never changes the platform mode on its own.
     */
    public function handle(ManufacturingDemo $demo, ModuleGate $moduleGate): int
    {
        if (! $moduleGate->enabled(Module::Mdm)) {
            $this->components->warn(trans('masterdata::app.demo.module-disabled'));
        }

        try {
            $totals = $demo->run();
        } catch (InvalidArgumentException $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->components->info(trans('masterdata::app.demo.seeded', [
            'types'   => $totals['entity_types'],
            'systems' => $totals['source_systems'],
            'records' => $totals['records'],
            'xrefs'   => $totals['xrefs'],
            'lists'   => $totals['code_lists'],
            'fields'  => $totals['fields'],
            'aliases' => $totals['aliases'],
        ]));

        return self::SUCCESS;
    }
}
