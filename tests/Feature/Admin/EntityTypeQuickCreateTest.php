<?php

use Webkul\MasterDataManagement\Enums\CodeStrategy;
use Webkul\MasterDataManagement\Enums\EntityTypeStatus;
use Webkul\MasterDataManagement\Enums\MasteringStyle;
use Webkul\MasterDataManagement\Models\EntityType;

it('renders the quick-create modal on the entity type listing', function () {
    $this->loginAsAdmin();

    $defaultLocale = core()->getDefaultLocaleCodeFromDefaultChannel();

    $this->get(route('admin.masterdata.entity_types.index'))
        ->assertOk()
        ->assertSee('ref="entityTypeCreateModal"', false)
        ->assertSee('action="'.route('admin.masterdata.entity_types.store').'"', false)
        ->assertSee('name="translations['.$defaultLocale.'][name]"', false)
        ->assertSee('name="code_strategy"', false)
        ->assertSee('name="code_pattern"', false)
        ->assertSee('name="mastering_style"', false)
        ->assertSee(trans('masterdata::app.entity-types.quick-create.mastering-styles-info.consolidation'))
        ->assertSee(trans('masterdata::app.entity-types.quick-create.mastering-styles-info.centralized'))
        ->assertDontSee('href="'.route('admin.masterdata.entity_types.create').'"', false);
});

it('hides the quick-create modal without the create permission', function () {
    $this->loginWithPermissions('custom', ['dashboard', 'masterdata', 'masterdata.entity_types']);

    $this->get(route('admin.masterdata.entity_types.index'))
        ->assertOk()
        ->assertDontSee('ref="entityTypeCreateModal"', false);
});

it('creates a draft entity type from the quick-create payload and redirects to its edit page', function () {
    $this->loginAsAdmin();

    $code = $this->uniqueCode('vendor');
    $defaultLocale = core()->getDefaultLocaleCodeFromDefaultChannel();

    $response = $this->postJson(route('admin.masterdata.entity_types.store'), [
        'translations'    => [$defaultLocale => ['name' => 'Vendor']],
        'code'            => $code,
        'code_strategy'   => CodeStrategy::IdentifierFields->value,
        'mastering_style' => MasteringStyle::Centralized->value,
    ])->assertOk();

    $type = EntityType::query()->where('code', $code)->firstOrFail();

    expect($response->json('redirect_url'))->toBe(route('admin.masterdata.entity_types.edit', $type->id))
        ->and($type->status)->toBe(EntityTypeStatus::Draft)
        ->and($type->code_strategy)->toBe(CodeStrategy::IdentifierFields)
        ->and($type->mastering_style)->toBe(MasteringStyle::Centralized)
        ->and($type->translate($defaultLocale)->name)->toBe('Vendor');
});

it('requires a sequence placeholder when the quick-create picks the pattern strategy', function () {
    $this->loginAsAdmin();

    $defaultLocale = core()->getDefaultLocaleCodeFromDefaultChannel();

    $this->postJson(route('admin.masterdata.entity_types.store'), [
        'translations'  => [$defaultLocale => ['name' => 'Vendor']],
        'code'          => $this->uniqueCode('vendor'),
        'code_strategy' => CodeStrategy::Pattern->value,
        'code_pattern'  => 'VEN-',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('code_pattern');
});

it('keeps the standalone create page reachable', function () {
    $this->loginAsAdmin();

    $this->get(route('admin.masterdata.entity_types.create'))->assertOk();
});
