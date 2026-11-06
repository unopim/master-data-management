<?php

namespace Webkul\MasterDataManagement\Demo\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;
use Webkul\Core\Platform\ModuleGate;
use Webkul\MasterDataManagement\Demo\Services\DemoInstaller;
use Webkul\MasterDataManagement\Support\Platform;

#[Description('Install the manufacturing site reference data demo (sites, lines, areas, equipment, crosswalk, stewardship)')]
#[Signature('masterdata-demo:install
    {--force : Run the installer again even when the demo is already present}
    {--password=admin123 : Password of the demo stewardship users}')]
class InstallCommand extends Command
{
    /**
     * Install the demo unless it is already present.
     */
    public function handle(DemoInstaller $installer, ModuleGate $moduleGate): int
    {
        if (! $moduleGate->enabled(Platform::MODULE)) {
            $this->components->warn(trans('masterdata::app.demo.module-disabled'));
        }

        if ($installer->isInstalled() && ! $this->option('force')) {
            $this->components->info(trans('masterdata-demo::app.install.already-installed'));

            return self::SUCCESS;
        }

        try {
            $totals = $installer->install((string) $this->option('password'));
        } catch (ValidationException $exception) {
            $this->components->error(implode(' ', $exception->validator->errors()->all()));

            return self::FAILURE;
        }

        $this->components->info(trans('masterdata-demo::app.install.installed', [
            'records' => $totals['records'],
            'xrefs'   => $totals['xrefs'],
            'systems' => $totals['source_systems'],
            'lists'   => $totals['code_lists'],
        ]));

        $this->components->info(trans('masterdata-demo::app.install.users', [
            'count'  => $totals['users'],
            'emails' => implode(', ', array_column($installer->dataset()['personas'], 'email')),
        ]));

        return self::SUCCESS;
    }
}
