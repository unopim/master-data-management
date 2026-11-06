<?php

namespace Webkul\MasterDataManagement\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Webkul\MasterDataManagement\Console\Commands\DemoManufacturingCommand;
use Webkul\MasterDataManagement\Console\Commands\ImportCommand;
use Webkul\MasterDataManagement\Console\Commands\InstallCommand;
use Webkul\MasterDataManagement\Console\Commands\PruneIdempotencyKeys;
use Webkul\MasterDataManagement\Console\Commands\QualityScanCommand;
use Webkul\MasterDataManagement\Contracts\ChangeGate;
use Webkul\MasterDataManagement\Contracts\CodeLookup;
use Webkul\MasterDataManagement\Contracts\ExportDestination;
use Webkul\MasterDataManagement\Demo\Providers\MasterDataDemoServiceProvider;
use Webkul\MasterDataManagement\Events\ChangeRequestDecided;
use Webkul\MasterDataManagement\Events\ChangeRequestSubmitted;
use Webkul\MasterDataManagement\Events\RecordCreated;
use Webkul\MasterDataManagement\Events\RecordRetired;
use Webkul\MasterDataManagement\Events\RecordUpdated;
use Webkul\MasterDataManagement\Events\RecordXrefsChanged;
use Webkul\MasterDataManagement\Events\SchemaPublished;
use Webkul\MasterDataManagement\Export\FilesystemDiskDestination;
use Webkul\MasterDataManagement\FieldTypes\FieldTypeRegistry;
use Webkul\MasterDataManagement\FieldTypes\MeasurementType;
use Webkul\MasterDataManagement\Http\Middleware\IdempotentRequest;
use Webkul\MasterDataManagement\Http\ViewComposers\RoleAccessCardComposer;
use Webkul\MasterDataManagement\Listeners\AddImportReviewCounts;
use Webkul\MasterDataManagement\Listeners\DeletePrincipalAccessGrants;
use Webkul\MasterDataManagement\Listeners\EvaluateRecordQuality;
use Webkul\MasterDataManagement\Listeners\LinkApprovedImportXref;
use Webkul\MasterDataManagement\Listeners\LinkApprovedMatchCandidate;
use Webkul\MasterDataManagement\Listeners\NotifyChangeRequestApprovers;
use Webkul\MasterDataManagement\Listeners\QueueQualityScan;
use Webkul\MasterDataManagement\Listeners\QueueRecordMatch;
use Webkul\MasterDataManagement\Listeners\SyncRoleAccessGrants;
use Webkul\MasterDataManagement\Models\FieldProxy;
use Webkul\MasterDataManagement\Models\Record;
use Webkul\MasterDataManagement\Observers\FieldObserver;
use Webkul\MasterDataManagement\Security\AccessGrantResolver;
use Webkul\MasterDataManagement\Security\EntityTypePermissions;
use Webkul\MasterDataManagement\Security\RecordPolicy;
use Webkul\MasterDataManagement\Services\DatabaseCodeLookup;
use Webkul\MasterDataManagement\Services\NativeChangeGate;
use Webkul\Measurement\Providers\MeasurementServiceProvider;
use Webkul\Theme\ViewRenderEventManager;

class MasterDataServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->app['router']->aliasMiddleware('masterdata.idempotent', IdempotentRequest::class);

        Route::middleware('web')->group(__DIR__.'/../Routes/admin.php');

        Route::prefix('api')
            ->middleware('api')
            ->group(__DIR__.'/../Routes/api.php');

        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'masterdata');

        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'masterdata');

        $this->registerAuthorization();

        $this->registerOptionalFieldTypes();

        $this->registerListeners();

        $this->registerRoleAccessCard();

        $this->registerImportSamples();

        FieldProxy::modelClass()::observe(FieldObserver::class);

        if ($this->app->runningInConsole()) {
            $this->commands([InstallCommand::class, DemoManufacturingCommand::class, ImportCommand::class, QualityScanCommand::class, PruneIdempotencyKeys::class]);
        }

        $this->callAfterResolving(Schedule::class, function (Schedule $schedule): void {
            $schedule->command(PruneIdempotencyKeys::class)->daily()->withoutOverlapping();

            $this->scheduleImports($schedule);
        });
    }

    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->register(ModuleServiceProvider::class);

        $this->app->register(MasterDataDemoServiceProvider::class);

        $this->registerConfig();

        $this->app->bind(ChangeGate::class, fn ($app) => $app->make($app['config']->get('masterdata.change_gate', NativeChangeGate::class)));

        $this->app->bind(ExportDestination::class, FilesystemDiskDestination::class);

        $this->app->scoped(EntityTypePermissions::class);

        $this->app->scoped(AccessGrantResolver::class);

        $this->app->scoped(CodeLookup::class, DatabaseCodeLookup::class);

        $this->app->scoped(FieldTypeRegistry::class);
    }

    /**
     * Register the record policy and the abilities whose subject is an entity type.
     */
    protected function registerAuthorization(): void
    {
        Gate::policy(Record::class, RecordPolicy::class);

        Gate::define('masterdata.records.viewAny', [RecordPolicy::class, 'viewAny']);

        Gate::define('masterdata.records.create', [RecordPolicy::class, 'create']);
    }

    /**
     * Register the listeners: record writes re-check quality, a schema publish queues a scan, an approved create from
     * the match inbox links its inbox row, an approved imported create links its external id, and a finished import
     * reports the changes it held for approval and the rows it refused; a held change notifies its approvers.
     */
    protected function registerListeners(): void
    {
        Event::listen([RecordCreated::class, RecordUpdated::class, RecordRetired::class, RecordXrefsChanged::class], EvaluateRecordQuality::class);

        Event::listen([RecordCreated::class, RecordUpdated::class], QueueRecordMatch::class);

        Event::listen(SchemaPublished::class, QueueQualityScan::class);

        Event::listen(ChangeRequestSubmitted::class, NotifyChangeRequestApprovers::class);

        Event::listen(ChangeRequestDecided::class, LinkApprovedMatchCandidate::class);

        Event::listen(ChangeRequestDecided::class, LinkApprovedImportXref::class);

        Event::listen('data_transfer.imports.completed', AddImportReviewCounts::class);

        Event::listen('unopim.admin.settings.data_transfer.tracker.job.state.completed.after', function (ViewRenderEventManager $viewRenderEventManager): void {
            if (AddImportReviewCounts::isMasterDataImport($viewRenderEventManager->getParam('meta')['entity_type'] ?? null)) {
                $viewRenderEventManager->addTemplate('masterdata::imports.tracker-summary');
            }
        });
    }

    /**
     * Add the "Master data access" card to the core role create and edit forms, save it with the role, and drop the
     * grants of a deleted role or admin.
     */
    protected function registerRoleAccessCard(): void
    {
        View::composer('masterdata::access.role-card', RoleAccessCardComposer::class);

        Event::listen(['user.role.create.before', 'user.role.update.before'], [SyncRoleAccessGrants::class, 'validate']);

        Event::listen(['user.role.create.after', 'user.role.update.after'], [SyncRoleAccessGrants::class, 'handle']);

        Event::listen('user.role.delete.after', [DeletePrincipalAccessGrants::class, 'role']);

        Event::listen('user.admin.delete.after', [DeletePrincipalAccessGrants::class, 'admin']);

        Event::listen(
            ['unopim.admin.settings.roles.create.card.access_control.after', 'unopim.admin.settings.roles.edit.card.access-control.after'],
            function (ViewRenderEventManager $viewRenderEventManager): void {
                if (bouncer()->hasPermission('masterdata.access')) {
                    $viewRenderEventManager->addTemplate('masterdata::access.role-card');
                }
            },
        );
    }

    /**
     * Run `masterdata:import` for each job of `masterdata.scheduled_imports.jobs` on its cron expression.
     */
    protected function scheduleImports(Schedule $schedule): void
    {
        foreach ((array) config('masterdata.scheduled_imports.jobs', []) as $code => $expression) {
            $schedule->command(ImportCommand::class, [(string) $code])
                ->cron((string) $expression)
                ->withoutOverlapping()
                ->onOneServer();
        }
    }

    /**
     * Offer the importer sample files for publishing to the public disk, where the sample downloads look first.
     */
    protected function registerImportSamples(): void
    {
        $this->publishes([
            dirname(__DIR__).'/Resources/samples' => storage_path('app/public/data-transfer/samples/import/masterdata'),
        ], 'masterdata-samples');
    }

    /**
     * Register the field types backed by optional packages, only when those packages are loaded.
     */
    protected function registerOptionalFieldTypes(): void
    {
        if (
            $this->app->providerIsLoaded(MeasurementServiceProvider::class)
            && ! $this->app['config']->has('masterdata.field_types.measurement')
        ) {
            $this->app['config']->set('masterdata.field_types.measurement', MeasurementType::class);
        }
    }

    /**
     * Register package config.
     */
    protected function registerConfig(): void
    {
        $this->mergeConfigFrom(dirname(__DIR__).'/Config/masterdata.php', 'masterdata');

        $this->mergeConfigFrom(dirname(__DIR__).'/Config/menu.php', 'menu.admin');

        $this->mergeConfigFrom(dirname(__DIR__).'/Config/acl.php', 'acl');

        $this->mergeConfigFrom(dirname(__DIR__).'/Config/api-acl.php', 'api-acl');

        $this->mergeConfigFrom(dirname(__DIR__).'/Config/platform_modules.php', 'platform_modules');

        $this->mergeConfigFrom(dirname(__DIR__).'/Config/exporters.php', 'exporters');

        $this->mergeConfigFrom(dirname(__DIR__).'/Config/importers.php', 'importers');
    }
}
