<?php

it('uses a master data menu icon that the admin icon font defines', function () {
    $item = collect(config('menu.admin'))->firstWhere('key', 'masterdata');

    $css = file_get_contents(base_path('packages/Webkul/Admin/src/Resources/assets/css/app.css'));

    expect($item['icon'])->toBe('icon-mdm')
        ->and($css)->toMatch('/\.icon-mdm:before\s*\{\s*content:\s*"\\\\e93b";/');
});
