<?php

use Webkul\Core\Models\CoreConfig;
use Webkul\Core\Platform\ModuleGate;

function storeHiddenSections(string $value): void
{
    CoreConfig::query()->updateOrCreate(
        ['code' => ModuleGate::HIDDEN_SECTIONS_KEY, 'channel_code' => null, 'locale_code' => null],
        ['value' => $value],
    );

    app()->forgetScopedInstances();
}

it('hides the default sections in mdm mode when nothing is stored', function () {
    $this->setPlatformModules('mdm');

    $this->loginAsAdmin();

    $gate = app(ModuleGate::class);

    expect($gate->allowsKey('dashboard'))->toBeFalse()
        ->and($gate->allowsKey('catalog.attributes.edit'))->toBeFalse()
        ->and($gate->allowsKey('catalog.attribute_groups'))->toBeFalse()
        ->and($gate->allowsKey('settings.currencies'))->toBeFalse()
        ->and($gate->allowsKey('settings.channels'))->toBeTrue()
        ->and($gate->allowsKey('data_transfer.imports'))->toBeTrue()
        ->and($gate->allowsKey('masterdata.records'))->toBeTrue();

    $this->get(route('admin.dashboard.index'))->assertNotFound();
    $this->get(route('admin.settings.currencies.index'))->assertNotFound();
    $this->get(route('admin.settings.channels.index'))->assertOk();
});

it('shows every section in mdm mode once the list is cleared', function () {
    $this->setPlatformModules('mdm');
    storeHiddenSections('');

    $this->loginAsAdmin();

    expect(app(ModuleGate::class)->allowsKey('dashboard'))->toBeTrue();

    $this->get(route('admin.settings.currencies.index'))->assertOk();
});

it('hides a section chosen by the admin', function () {
    $this->setPlatformModules('mdm');
    storeHiddenSections('settings.channels');

    $this->loginAsAdmin();

    $this->get(route('admin.settings.channels.index'))->assertNotFound();
    $this->get(route('admin.settings.currencies.index'))->assertOk();
});

it('ignores the hidden list outside mdm-only mode', function (string $mode) {
    $this->setPlatformModules($mode);
    storeHiddenSections('dashboard,settings.currencies');

    $this->loginAsAdmin();

    expect(app(ModuleGate::class)->allowsKey('dashboard'))->toBeTrue();

    $this->get(route('admin.settings.currencies.index'))->assertOk();
})->with(['pim', 'pim,mdm']);

it('never hides a section that is not marked hideable', function () {
    $this->setPlatformModules('mdm');
    storeHiddenSections('configuration.system_settings,settings.users,settings.roles,masterdata');

    $this->loginAsAdmin();

    $gate = app(ModuleGate::class);

    expect($gate->allowsKey('configuration.system_settings'))->toBeTrue()
        ->and($gate->allowsKey('settings.users'))->toBeTrue()
        ->and($gate->allowsKey('settings.roles'))->toBeTrue()
        ->and($gate->allowsKey('masterdata.records'))->toBeTrue();

    $this->get(route('admin.settings.users.index'))->assertOk();
});

it('does not hide api permissions that share a section name', function () {
    $this->setPlatformModules('mdm');

    expect(app(ModuleGate::class)->allowsKey('api.settings.currencies'))->toBeTrue();
});

it('drops hidden sections from the sidebar', function () {
    $this->setPlatformModules('mdm');

    $this->loginAsAdmin();

    $this->get(route('admin.masterdata.dashboard.index'))
        ->assertOk()
        ->assertDontSee(route('admin.settings.currencies.index'), false)
        ->assertSee(route('admin.settings.channels.index'), false);
});

it('lists only hideable sections as options', function () {
    $values = collect(app(ModuleGate::class)->hideableSectionOptions())->pluck('value');

    expect($values)->toContain('dashboard', 'catalog.attributes', 'settings.currencies', 'data_transfer.imports')
        ->not->toContain('configuration.system_settings', 'settings.users', 'settings.roles', 'masterdata');
});

it('rejects a section that is not hideable on save', function () {
    $this->setPlatformModules('mdm');

    $this->loginAsAdmin();

    CoreConfig::query()->where('code', ModuleGate::HIDDEN_SECTIONS_KEY)->delete();

    $group = collect(config('core'))->firstWhere('key', 'general.platform.settings');

    $this->post(route('admin.configuration.store', ['slug' => 'general', 'slug2' => 'platform']), [
        'keys'    => [json_encode($group)],
        'general' => ['platform' => ['settings' => [
            'modules'         => 'mdm',
            'hidden_sections' => 'dashboard,settings.users',
        ]]],
    ])->assertSessionHasErrors('general.platform.settings.hidden_sections');

    $this->assertDatabaseMissing('core_config', ['code' => ModuleGate::HIDDEN_SECTIONS_KEY]);
});

it('renders the hidden sections field on the platform settings page', function () {
    $this->setPlatformModules('mdm');

    $this->loginAsAdmin();

    $this->get(route('admin.configuration.edit', ['slug' => 'general', 'slug2' => 'platform']))
        ->assertOk()
        ->assertSee('general[platform][settings][hidden_sections]', false)
        ->assertSee(trans('admin::app.configuration.index.general.platform.hidden-sections.title'));
});
