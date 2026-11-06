<?php

namespace Webkul\MasterDataManagement\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Webkul\Core\Platform\ModuleGate;
use Webkul\Installer\Helpers\PlatformModules;
use Webkul\MasterDataManagement\Support\Platform;

#[Description('Install master data management on this UnoPim: run its migrations and switch the module on')]
#[Signature('masterdata:install
        {--standalone : Run master data management without PIM}
        {--demo : Also install the manufacturing demo data}')]
class InstallCommand extends Command
{
    /**
     * Migrate, add MDM to the platform modules (or make it the only one), and optionally seed the demo.
     */
    public function handle(ModuleGate $moduleGate, PlatformModules $platformModules): int
    {
        $this->components->info(trans('masterdata::app.install.migrating'));

        $this->call('migrate', ['--force' => true]);

        $modules = $this->option('standalone')
            ? [Platform::MODULE]
            : array_values(array_unique([...$moduleGate->modules(), Platform::MODULE]));

        $errors = $platformModules->errors($modules);

        if ($errors !== []) {
            foreach ($errors as $error) {
                $this->components->error($error);
            }

            return self::FAILURE;
        }

        $platformModules->store($modules);

        if ($this->option('demo')) {
            $this->components->info(trans('masterdata::app.install.demo'));

            $this->call('masterdata-demo:install');
        }

        $this->call('optimize:clear');

        $this->components->info(trans('masterdata::app.install.done', ['modules' => implode(', ', $modules)]));

        return self::SUCCESS;
    }
}
