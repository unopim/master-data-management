<?php

use Webkul\AdminApi\Tests\Traits\ApiHelperTrait;
use Webkul\MasterDataManagement\Models\MatchRule;
use Webkul\MasterDataManagement\Repositories\EntityTypeRepository;
use Webkul\MasterDataManagement\Repositories\MatchRuleRepository;
use Webkul\MasterDataManagement\Services\FieldService;

uses(ApiHelperTrait::class);

beforeEach(function () {
    $this->type = app(EntityTypeRepository::class)->create(['code' => $this->uniqueCode('supplier'), 'code_strategy' => 'source_id', 'status' => 'active']);

    app(FieldService::class)->create($this->type, ['code' => 'vat', 'type' => 'text', 'is_searchable' => true]);
    app(FieldService::class)->create($this->type, ['code' => 'founded', 'type' => 'date']);

    $this->storeUrl = route('admin.masterdata.match_rules.store', $this->type->id);
});

it('shows the matching tab with its grid', function () {
    $this->loginAsAdmin();

    app(MatchRuleRepository::class)->create(['entity_type_id' => $this->type->id, 'target' => 'vat', 'comparator' => 'exact']);

    $this->get(route('admin.masterdata.entity_types.edit', ['id' => $this->type->id, 'matching' => 1]))
        ->assertOk()
        ->assertSee(trans('masterdata::app.match-rules.title'))
        ->assertSee(trans('masterdata::app.match-rules.form.is-blocking-info'));

    $this->ajaxGrid(route('admin.masterdata.match_rules.index', $this->type->id))
        ->assertOk()
        ->assertJsonPath('records.0.target', 'vat');
});

it('creates, updates and deletes a match rule', function () {
    $this->loginAsAdmin();

    $this->postJson($this->storeUrl, [
        'target'      => 'vat',
        'comparator'  => 'exact',
        'weight'      => 3,
        'is_blocking' => '1',
    ])
        ->assertOk()
        ->assertJsonStructure(['redirect_url', 'message']);

    $rule = MatchRule::query()->where('entity_type_id', $this->type->id)->sole();

    expect($rule->target)->toBe('vat')
        ->and($rule->weight)->toBe(3.0)
        ->and($rule->is_blocking)->toBeTrue()
        ->and($rule->options)->toBeNull();

    $this->putJson(route('admin.masterdata.match_rules.update', [$this->type->id, $rule->id]), [
        'target'      => 'founded',
        'comparator'  => 'date',
        'weight'      => 1.5,
        'is_blocking' => '0',
        'options'     => ['days' => 30, 'tolerance' => 5],
    ])->assertOk();

    $rule->refresh();

    expect($rule->target)->toBe('founded')
        ->and($rule->comparator)->toBe('date')
        ->and($rule->is_blocking)->toBeFalse()
        ->and($rule->options)->toBe(['days' => 30]);

    $this->deleteJson(route('admin.masterdata.match_rules.destroy', [$this->type->id, $rule->id]))->assertOk();

    expect(MatchRule::query()->whereKey($rule->id)->exists())->toBeFalse();
});

it('rejects invalid match rules', function (array $payload, string $error) {
    $this->loginAsAdmin();

    app(MatchRuleRepository::class)->create(['entity_type_id' => $this->type->id, 'target' => 'label', 'comparator' => 'fuzzy']);

    $this->postJson($this->storeUrl, $payload + ['target' => 'vat', 'comparator' => 'exact', 'weight' => 1])
        ->assertUnprocessable()
        ->assertJsonValidationErrors($error);
})->with([
    'unknown target'          => [['target' => 'missing'], 'target'],
    'unknown comparator'      => [['comparator' => 'telepathy'], 'comparator'],
    'weight too low'          => [['weight' => 0], 'weight'],
    'weight too high'         => [['weight' => 101], 'weight'],
    'negative tolerance'      => [['comparator' => 'numeric', 'options' => ['tolerance' => -1]], 'options.tolerance'],
    'negative days'           => [['comparator' => 'date', 'options' => ['days' => -2]], 'options.days'],
    'same target, comparator' => [['target' => 'label', 'comparator' => 'fuzzy'], 'comparator'],
    'blocking unsearchable'   => [['target' => 'founded', 'comparator' => 'date', 'is_blocking' => true], 'is_blocking'],
]);

it('keeps match rules of another entity type out of reach', function () {
    $this->loginAsAdmin();

    $other = app(EntityTypeRepository::class)->create(['code' => $this->uniqueCode('other')]);
    $rule = app(MatchRuleRepository::class)->create(['entity_type_id' => $other->id, 'target' => 'code', 'comparator' => 'exact']);

    $this->putJson(route('admin.masterdata.match_rules.update', [$this->type->id, $rule->id]), ['target' => 'code', 'comparator' => 'fuzzy', 'weight' => 1])
        ->assertNotFound();

    $this->deleteJson(route('admin.masterdata.match_rules.destroy', [$this->type->id, $rule->id]))->assertNotFound();
});

it('forbids match rules without the entity type edit permission', function () {
    $this->loginWithPermissions('custom', ['dashboard', 'masterdata', 'masterdata.entity_types']);

    $rule = app(MatchRuleRepository::class)->create(['entity_type_id' => $this->type->id, 'target' => 'code', 'comparator' => 'exact']);

    $this->getJson(route('admin.masterdata.match_rules.index', $this->type->id))->assertForbidden();
    $this->postJson($this->storeUrl, ['target' => 'code', 'comparator' => 'fuzzy', 'weight' => 1])->assertForbidden();
    $this->putJson(route('admin.masterdata.match_rules.update', [$this->type->id, $rule->id]), ['target' => 'code', 'comparator' => 'fuzzy', 'weight' => 1])->assertForbidden();
    $this->deleteJson(route('admin.masterdata.match_rules.destroy', [$this->type->id, $rule->id]))->assertForbidden();

    expect(MatchRule::query()->where('entity_type_id', $this->type->id)->count())->toBe(1);
});

it('saves the match on write setting of the entity type', function () {
    $this->loginAsAdmin();

    $this->putJson(route('admin.masterdata.entity_types.update', $this->type->id), ['settings' => ['match_on_write' => '0']])->assertOk();

    expect($this->type->refresh()->matchesOnWrite())->toBeFalse();

    $this->putJson(route('admin.masterdata.entity_types.update', $this->type->id), ['settings' => ['match_on_write' => '1']])->assertOk();

    expect($this->type->refresh()->matchesOnWrite())->toBeTrue();

    $this->get(route('admin.masterdata.entity_types.edit', $this->type->id))
        ->assertOk()
        ->assertSee(trans('masterdata::app.entity-types.form.match-on-write-info'));
});

it('lists the match rules on the entity type api', function () {
    app(MatchRuleRepository::class)->create(['entity_type_id' => $this->type->id, 'target' => 'vat', 'comparator' => 'exact', 'weight' => 2, 'is_blocking' => true]);

    $this->getJson(route('admin.api.masterdata.entity_types.get', $this->type->code), $this->getAuthenticationHeaders())
        ->assertOk()
        ->assertJsonPath('data.match_rules.0.target', 'vat')
        ->assertJsonPath('data.match_rules.0.comparator', 'exact')
        ->assertJsonPath('data.match_rules.0.weight', 2)
        ->assertJsonPath('data.match_rules.0.is_blocking', true);
});
