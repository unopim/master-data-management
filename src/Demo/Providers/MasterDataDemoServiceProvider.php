<?php

namespace Webkul\MasterDataManagement\Demo\Providers;

use Illuminate\Support\ServiceProvider;
use Webkul\MasterDataManagement\Demo\Console\Commands\InstallCommand;

class MasterDataDemoServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'masterdata-demo');

        if ($this->app->runningInConsole()) {
            $this->commands([InstallCommand::class]);
        }
    }
}
