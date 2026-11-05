<?php

use Webkul\DataTransfer\Services\JobTypeGate;

it('offers only the master data job types when the platform runs master data alone', function () {
    $this->setPlatformModules('mdm');

    $gate = app(JobTypeGate::class);

    expect(array_keys($gate->visible('importers')))->toEqualCanonicalizing(['masterdata_records', 'masterdata_xrefs', 'masterdata_code_lists'])
        ->and(array_keys($gate->visible('exporters')))->toBe(['masterdata_records'])
        ->and($gate->hides('attributes'))->toBeTrue()
        ->and($gate->hides('users'))->toBeTrue()
        ->and($gate->hides('masterdata_records'))->toBeFalse();
});

it('offers the catalog and master data job types together when both modules run', function () {
    $this->setPlatformModules(['pim', 'mdm']);

    $gate = app(JobTypeGate::class);

    $catalog = ['products', 'categories', 'attributes', 'attribute-groups', 'attribute-options', 'locales', 'channels', 'currencies', 'roles', 'users'];

    expect(array_keys($gate->visible('importers')))->toContain(...$catalog, ...['masterdata_records', 'masterdata_xrefs', 'masterdata_code_lists'])
        ->and(array_keys($gate->visible('exporters')))->toContain(...$catalog, ...['masterdata_records'])
        ->and($gate->hidden('importers'))->toBe([])
        ->and($gate->hidden('exporters'))->toBe([]);
});

it('lists only the master data types in the create import type dropdown in master data mode', function () {
    $this->setPlatformModules('mdm');

    $this->loginAsAdmin();

    $this->get(route('admin.settings.data_transfer.imports.create'))
        ->assertOk()
        ->assertSee('{&quot;id&quot;:&quot;masterdata_records&quot;', false)
        ->assertDontSee('{&quot;id&quot;:&quot;attributes&quot;', false)
        ->assertDontSee('{&quot;id&quot;:&quot;users&quot;', false);
});
