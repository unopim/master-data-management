<?php

use Illuminate\Support\Facades\URL;
use Webkul\MasterDataManagement\Repositories\EntityTypeRepository;
use Webkul\MasterDataManagement\Services\RecordWriter;

beforeEach(function () {
    URL::forceRootUrl('https://pim.example.com');
    URL::forceScheme('https');
});

it('renders no sub-menu tab bar on the records dashboard when APP_URL differs from the request host', function () {
    $this->loginAsAdmin();

    $html = $this->get('http://localhost/'.config('app.admin_url').'/masterdata/records')->assertOk()->getContent();

    expect($html)->not->toContain('border-b-2 max-sm:hidden');
});

it('shows the breadcrumb on a record page when APP_URL differs from the request host', function () {
    $this->loginAsAdmin();

    $type = app(EntityTypeRepository::class)->create(['code' => $this->uniqueCode('line'), 'code_strategy' => 'source_id']);
    $record = app(RecordWriter::class)->create($type, ['code' => 'L-1', 'label' => 'Line One'], null);

    $this->get('http://localhost/'.config('app.admin_url').'/masterdata/records/record/'.$record->uuid)
        ->assertOk()
        ->assertSee(trans('admin::app.components.layouts.breadcrumbs.label'), false);
});

it('shows the breadcrumb on every master data menu page when APP_URL differs from the request host', function () {
    $this->loginAsAdmin();

    $routes = collect(config('menu.admin'))
        ->filter(fn (array $item): bool => str_starts_with($item['key'], 'masterdata.'))
        ->pluck('route')
        ->unique();

    foreach ($routes as $name) {
        $path = parse_url(route($name), PHP_URL_PATH);

        $this->get('http://localhost'.$path)
            ->assertOk()
            ->assertSee(trans('admin::app.components.layouts.breadcrumbs.label'), false);
    }

    expect($routes)->not->toBeEmpty();
});

it('shows the breadcrumb on an entity type record listing when APP_URL differs from the request host', function () {
    $this->loginAsAdmin();

    $type = app(EntityTypeRepository::class)->create(['code' => $this->uniqueCode('site'), 'code_strategy' => 'source_id']);

    $this->get('http://localhost/'.config('app.admin_url').'/masterdata/records/'.$type->code)
        ->assertOk()
        ->assertSee(trans('admin::app.components.layouts.breadcrumbs.label'), false);
});

it('renders the current menu page as the breadcrumb leaf, not a link, when APP_URL differs from the request host', function () {
    $this->loginAsAdmin();

    $html = $this->get('http://localhost/'.config('app.admin_url').'/masterdata/entity-types')->assertOk()->getContent();

    preg_match('/<nav\s+aria-label=.*?<\/nav>/s', $html, $crumb);

    expect($crumb[0] ?? '')->toContain(route('admin.masterdata.dashboard.index'))
        ->and($crumb[0] ?? '')->not->toContain('href="'.route('admin.masterdata.entity_types.index').'"');
});
