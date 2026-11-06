<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Webkul\Core\Models\CoreConfig;
use Webkul\Core\Platform\ModuleGate;

beforeEach(fn () => $this->loginAsAdmin());

it('lists the platform modules setting in the system settings hub', function () {
    $this->get(route('admin.settings.system.index'))
        ->assertOk()
        ->assertSee(trans('admin::app.configuration.index.general.platform.title'))
        ->assertSee(route('admin.settings.system.edit', 'system.platform'));
});

it('edits the platform modules setting from the system settings hub', function () {
    $this->get(route('admin.settings.system.edit', 'system.platform'))
        ->assertOk()
        ->assertSee(trans('admin::app.configuration.index.general.platform.modules.title'));
});

it('shows a back button on the configuration edit page', function () {
    $this->get(route('admin.configuration.edit', ['slug' => 'general', 'slug2' => 'platform']))
        ->assertOk()
        ->assertSee('href="'.route('admin.configuration.edit').'"
                class="transparent-button"', false);
});

it('saves several platform modules from the system settings hub', function () {
    $this->put(route('admin.settings.system.update', 'system.platform'), [
        'general' => ['platform' => ['settings' => ['modules' => ['pim', 'mdm'], 'hidden_sections' => ['dashboard']]]],
    ])->assertSessionHasNoErrors()->assertRedirect(route('admin.settings.system.edit', 'system.platform'));

    expect(DB::table('core_config')->where('code', ModuleGate::CONFIG_KEY)->value('value'))->toBe('pim,mdm');
});

it('saves a single independent platform module', function () {
    $this->put(route('admin.settings.system.update', 'system.platform'), [
        'general' => ['platform' => ['settings' => ['modules' => ['mdm']]]],
    ])->assertSessionHasNoErrors();

    expect(DB::table('core_config')->where('code', ModuleGate::CONFIG_KEY)->value('value'))->toBe('mdm');
});

it('rejects a dependent platform module saved without its requirement', function () {
    config(['platform_modules.shopify' => ['name' => 'shopify', 'requires' => ['pim']]]);

    $this->put(route('admin.settings.system.update', 'system.platform'), [
        'general' => ['platform' => ['settings' => ['modules' => ['mdm', 'shopify']]]],
    ])->assertSessionHasErrors('general.platform.settings.modules');

    expect(DB::table('core_config')->where('code', ModuleGate::CONFIG_KEY)->value('value'))->not->toBe('mdm,shopify');
});

it('rejects an empty platform module selection', function () {
    $this->put(route('admin.settings.system.update', 'system.platform'), [
        'general' => ['platform' => ['settings' => ['modules' => []]]],
    ])->assertSessionHasErrors('general.platform.settings.modules');
});

it('hides every shareable catalog section by default in mdm mode so the catalog menu disappears', function () {
    $this->setPlatformModules('mdm');

    CoreConfig::query()->where('code', ModuleGate::HIDDEN_SECTIONS_KEY)->delete();

    app()->forgetScopedInstances();

    $gate = app(ModuleGate::class);

    $visibleCatalog = collect(config('menu.admin'))
        ->filter(fn (array $item): bool => str_starts_with($item['key'], 'catalog.') && $gate->allowsKey($item['key']))
        ->filter(fn (array $item): bool => $gate->filter([$item]) !== []);

    expect($gate->allowsKey('catalog.measurements'))->toBeFalse()
        ->and($visibleCatalog->pluck('key')->all())->toBe([])
        ->and($gate->allowsKey('data_transfer.imports'))->toBeTrue()
        ->and($gate->allowsKey('configuration.webhook'))->toBeFalse()
        ->and($gate->allowsKey('ai-agent'))->toBeFalse()
        ->and($gate->allowsKey('configuration.integrations'))->toBeTrue();
});

it('drops a menu parent from the sidebar once every child is hidden', function () {
    $this->setPlatformModules('mdm');

    CoreConfig::query()->where('code', ModuleGate::HIDDEN_SECTIONS_KEY)->delete();

    foreach (['general.magic_ai.agentic_pim.enabled', 'general.magic_ai.agentic_pim.open_by_default'] as $code) {
        CoreConfig::query()->updateOrCreate(['code' => $code], ['value' => '1']);
    }

    app()->forgetScopedInstances();

    $this->loginAsAdmin();

    $sidebar = Str::between(
        $this->get(route('admin.masterdata.icons.index'))->assertOk()->getContent(),
        'id="unopim-sidebar"',
        '<v-sidebar-collapse',
    );

    expect($sidebar)->toContain(trans('masterdata::app.menu.master-data'))
        ->not->toContain(trans('admin::app.components.layouts.sidebar.catalog'))
        ->not->toContain(trans('ai-agent::app.menu.ai-agent'));
});
