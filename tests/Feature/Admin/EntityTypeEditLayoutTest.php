<?php

use Webkul\MasterDataManagement\Repositories\EntityTypeRepository;

$cardHeadings = fn (): array => [
    trans('masterdata::app.entity-types.form.basics'),
    trans('masterdata::app.entity-types.form.identity'),
    trans('masterdata::app.entity-types.form.hierarchy'),
    trans('masterdata::app.entity-types.form.governance'),
    trans('masterdata::app.entity-types.form.quality-matching'),
];

$submittedFields = [
    'name="icon"',
    'name="status"',
    'name="sort_order"',
    'name="mastering_style"',
    'name="code_strategy"',
    'name="code_pattern"',
    'name="is_hierarchical"',
    'name="allowed_parent_type_ids"',
    'name="approval_required"',
    'name="data_owner_id"',
    'name="settings[completeness_scope]"',
    'name="settings[required_source_system_ids]"',
    'name="settings[auto_match_threshold]"',
];

it('groups the general tab of the edit page into cards', function () use ($cardHeadings, $submittedFields) {
    $this->loginAsAdmin();

    $type = app(EntityTypeRepository::class)->create(['code' => $this->uniqueCode('layout')]);

    $this->get(route('admin.masterdata.entity_types.edit', $type->id))
        ->assertOk()
        ->assertSeeTextInOrder($cardHeadings())
        ->assertSee('name="label_field_code"', false)
        ->assertSee('name="translations['.core()->getDefaultLocaleCodeFromDefaultChannel().'][name]"', false)
        ->assertSee($submittedFields, false);
});

it('shows the status badge, schema version and publish action in the edit page header', function () {
    $this->loginAsAdmin();

    $type = app(EntityTypeRepository::class)->create(['code' => $this->uniqueCode('header'), 'status' => 'active']);
    $type->forceFill(['schema_version' => 3])->save();

    $html = $this->get(route('admin.masterdata.entity_types.edit', $type->id))
        ->assertOk()
        ->assertSee('<span class="label-active">'.trans('masterdata::app.entity-types.statuses.active').'</span>', false)
        ->assertSeeText(trans('masterdata::app.entity-types.edit.schema-version', ['version' => 3]))
        ->getContent();

    expect(substr_count($html, route('admin.masterdata.entity_types.publish', $type->id)))->toBe(1)
        ->and(substr_count($html, '<v-masterdata-publish-schema>'))->toBe(1);
});

it('shows a draft entity type with the pending badge', function () {
    $this->loginAsAdmin();

    $type = app(EntityTypeRepository::class)->create(['code' => $this->uniqueCode('draft')]);

    $this->get(route('admin.masterdata.entity_types.edit', $type->id))
        ->assertOk()
        ->assertSee('<span class="label-pending">'.trans('masterdata::app.entity-types.statuses.draft').'</span>', false)
        ->assertSeeText(trans('masterdata::app.entity-types.edit.schema-version', ['version' => 0]));
});

it('keeps the governance controls read only without the governance permission', function () {
    $type = app(EntityTypeRepository::class)->create(['code' => $this->uniqueCode('locked')]);

    $this->loginWithPermissions('custom', ['dashboard', 'masterdata', 'masterdata.entity_types', 'masterdata.entity_types.edit']);

    $this->get(route('admin.masterdata.entity_types.edit', $type->id))
        ->assertOk()
        ->assertSeeText(trans('masterdata::app.entity-types.form.governance'))
        ->assertSee('name="approval_required_display"', false)
        ->assertSee('name="data_owner_display"', false)
        ->assertDontSee('name="approval_required"', false);
});

it('renders the same cards on the create page', function () use ($cardHeadings, $submittedFields) {
    $this->loginAsAdmin();

    $this->get(route('admin.masterdata.entity_types.create'))
        ->assertOk()
        ->assertSeeTextInOrder($cardHeadings())
        ->assertSee('name="code"', false)
        ->assertSee($submittedFields, false)
        ->assertDontSee('name="label_field_code"', false);
});
