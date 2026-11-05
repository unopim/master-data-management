<?php

use Illuminate\Support\Facades\Route;

it('registers masterdata config, translations and routes', function () {
    expect(config('masterdata.batch_size'))->toBe(500)
        ->and(config('masterdata.match.block_cap'))->toBe(200)
        ->and(config('masterdata.field_types'))->toBeArray()
        ->and(trans('masterdata::app.menu.master-data'))->not->toBe('masterdata::app.menu.master-data')
        ->and(Route::has('admin.masterdata.dashboard.index'))->toBeTrue();
});

it('renders the dashboard page for an admin', function () {
    $this->loginAsAdmin();

    $this->get(route('admin.masterdata.dashboard.index'))
        ->assertOk()
        ->assertSeeText(trans('masterdata::app.dashboard.title'));

    $this->get(route('admin.masterdata.home'))
        ->assertRedirect(route('admin.masterdata.dashboard.index'));
});

it('merges the menu and acl roots', function () {
    expect(collect(config('menu.admin'))->firstWhere('key', 'masterdata'))
        ->toMatchArray(['route' => 'admin.masterdata.dashboard.index', 'icon' => 'icon-mdm', 'sort' => 4])
        ->and(collect(config('menu.admin'))->firstWhere('key', 'masterdata.records'))
        ->toMatchArray(['route' => 'admin.masterdata.dashboard.index', 'sort' => 0])
        ->and(collect(config('acl'))->firstWhere('key', 'masterdata'))
        ->toMatchArray(['route' => 'admin.masterdata.dashboard.index']);
});
