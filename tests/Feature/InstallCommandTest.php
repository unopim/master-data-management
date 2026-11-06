<?php

use Webkul\Core\Models\CoreConfig;
use Webkul\Core\Platform\ModuleGate;

function storedPlatformModules(): ?string
{
    return CoreConfig::query()
        ->where('code', ModuleGate::CONFIG_KEY)
        ->whereNull('channel_code')
        ->whereNull('locale_code')
        ->value('value');
}

it('adds master data management next to pim', function () {
    $this->setPlatformModules('pim');

    $this->artisan('masterdata:install')->assertSuccessful();

    expect(storedPlatformModules())->toBe('pim,mdm');
});

it('keeps the module selection unchanged when run again', function () {
    $this->setPlatformModules('pim,mdm');

    $this->artisan('masterdata:install')->assertSuccessful();

    expect(storedPlatformModules())->toBe('pim,mdm');
});

it('runs master data management alone with the standalone option', function () {
    $this->setPlatformModules('pim');

    $this->artisan('masterdata:install', ['--standalone' => true])->assertSuccessful();

    expect(storedPlatformModules())->toBe('mdm');
});
