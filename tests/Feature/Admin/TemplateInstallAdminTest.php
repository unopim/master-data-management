<?php

use Webkul\MasterDataManagement\Models\CodeList;
use Webkul\MasterDataManagement\Models\EntityType;
use Webkul\MasterDataManagement\Models\Record;

it('offers the template installer on the entity type listing', function () {
    $this->loginAsAdmin();

    $this->get(route('admin.masterdata.entity_types.index'))
        ->assertOk()
        ->assertSee(trans('masterdata::app.templates.install-btn'))
        ->assertSee(route('admin.masterdata.templates.install'));
});

it('installs a template from the admin', function () {
    $this->loginAsAdmin();

    $this->postJson(route('admin.masterdata.templates.install'), ['template' => 'product-reference', 'with_samples' => '1'])
        ->assertOk()
        ->assertJsonPath('redirect_url', route('admin.masterdata.entity_types.index'))
        ->assertJsonPath('message', trans('masterdata::app.templates.installed', [
            'name' => 'product-reference', 'types' => 1, 'fields' => 20, 'lists' => 3, 'records' => 2,
        ]));

    $type = EntityType::query()->where('code', 'product_reference')->firstOrFail();

    expect(Record::query()->where('entity_type_id', $type->id)->count())->toBe(2)
        ->and(CodeList::query()->whereIn('code', ['uom', 'country', 'product_lifecycle'])->count())->toBe(3);
});

it('rejects an unknown template', function () {
    $this->loginAsAdmin();

    $this->postJson(route('admin.masterdata.templates.install'), ['template' => '../asset'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('template');
});

it('forbids installing templates without the templates permission', function () {
    $this->loginWithPermissions('custom', ['dashboard', 'masterdata', 'masterdata.entity_types', 'masterdata.entity_types.create']);

    $this->get(route('admin.masterdata.entity_types.index'))
        ->assertOk()
        ->assertDontSee(trans('masterdata::app.templates.install-btn'));

    $this->postJson(route('admin.masterdata.templates.install'), ['template' => 'customer'])->assertForbidden();

    expect(EntityType::query()->where('code', 'customer')->exists())->toBeFalse();
});

it('lets a role with the templates permission install', function () {
    $this->loginWithPermissions('custom', ['dashboard', 'masterdata', 'masterdata.entity_types', 'masterdata.entity_types.templates']);

    $this->postJson(route('admin.masterdata.templates.install'), ['template' => 'employee'])->assertOk();

    expect(EntityType::query()->where('code', 'employee')->exists())->toBeTrue();
});
