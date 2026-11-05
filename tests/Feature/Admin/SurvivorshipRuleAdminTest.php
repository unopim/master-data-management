<?php

use Webkul\AdminApi\Tests\Traits\ApiHelperTrait;
use Webkul\MasterDataManagement\Models\SurvivorshipRule;
use Webkul\MasterDataManagement\Repositories\EntityTypeRepository;
use Webkul\MasterDataManagement\Repositories\SourceSystemRepository;
use Webkul\MasterDataManagement\Repositories\SurvivorshipRuleRepository;
use Webkul\MasterDataManagement\Services\FieldService;
use Webkul\MasterDataManagement\Services\RecordWriter;

uses(ApiHelperTrait::class);

beforeEach(function () {
    $this->type = app(EntityTypeRepository::class)->create(['code' => $this->uniqueCode('supplier'), 'code_strategy' => 'source_id', 'status' => 'active']);

    app(FieldService::class)->create($this->type, ['code' => 'vat', 'type' => 'text']);

    $this->erp = app(SourceSystemRepository::class)->create(['code' => $this->uniqueCode('erp'), 'name' => 'ERP', 'type' => 'erp']);
    $this->crm = app(SourceSystemRepository::class)->create(['code' => $this->uniqueCode('crm'), 'name' => 'CRM', 'type' => 'crm']);

    $this->storeUrl = route('admin.masterdata.survivorship_rules.store', $this->type->id);
});

it('shows the survivorship tab with its grid', function () {
    $this->loginAsAdmin();

    app(SurvivorshipRuleRepository::class)->create(['entity_type_id' => $this->type->id, 'target' => 'vat', 'strategy' => 'most_recent']);

    $this->get(route('admin.masterdata.entity_types.edit', ['id' => $this->type->id, 'survivorship' => 1]))
        ->assertOk()
        ->assertSee(trans('masterdata::app.survivorship-rules.title'))
        ->assertSee(trans('masterdata::app.survivorship-rules.form.source-order-info'))
        ->assertSee("strategy === 'source_priority'", false);

    $this->ajaxGrid(route('admin.masterdata.survivorship_rules.index', $this->type->id))
        ->assertOk()
        ->assertJsonPath('records.0.target', 'vat');
});

it('creates, updates and deletes a survivorship rule', function () {
    $this->loginAsAdmin();

    $this->postJson($this->storeUrl, [
        'target'   => '*',
        'strategy' => 'source_priority',
        'options'  => ['source_order' => json_encode([$this->crm->id, $this->erp->id])],
    ])
        ->assertOk()
        ->assertJsonStructure(['redirect_url', 'message']);

    $rule = SurvivorshipRule::query()->where('entity_type_id', $this->type->id)->sole();

    expect($rule->target)->toBeNull()
        ->and($rule->strategy)->toBe('source_priority')
        ->and($rule->options)->toBe(['source_order' => [$this->crm->id, $this->erp->id]]);

    $this->putJson(route('admin.masterdata.survivorship_rules.update', [$this->type->id, $rule->id]), [
        'target'   => 'vat',
        'strategy' => 'most_complete',
        'options'  => ['source_order' => (string) $this->erp->id],
    ])->assertOk();

    $rule->refresh();

    expect($rule->target)->toBe('vat')
        ->and($rule->strategy)->toBe('most_complete')
        ->and($rule->options)->toBeNull();

    $this->deleteJson(route('admin.masterdata.survivorship_rules.destroy', [$this->type->id, $rule->id]))->assertOk();

    expect(SurvivorshipRule::query()->whereKey($rule->id)->exists())->toBeFalse();
});

it('rejects invalid survivorship rules', function (array $payload, string $error) {
    $this->loginAsAdmin();

    app(SurvivorshipRuleRepository::class)->create(['entity_type_id' => $this->type->id, 'target' => null, 'strategy' => 'most_recent']);
    app(SurvivorshipRuleRepository::class)->create(['entity_type_id' => $this->type->id, 'target' => 'label', 'strategy' => 'most_recent']);

    $this->postJson($this->storeUrl, $payload + ['target' => 'vat', 'strategy' => 'most_recent'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors($error);
})->with([
    'unknown target'        => [['target' => 'missing'], 'target'],
    'unknown strategy'      => [['strategy' => 'telepathy'], 'strategy'],
    'second default'        => [['target' => '*'], 'target'],
    'second label rule'     => [['target' => 'label'], 'target'],
    'unknown source system' => [['strategy' => 'source_priority', 'options' => ['source_order' => [999999999]]], 'options.source_order.0'],
]);

it('keeps survivorship rules of another entity type out of reach', function () {
    $this->loginAsAdmin();

    $other = app(EntityTypeRepository::class)->create(['code' => $this->uniqueCode('other')]);
    $rule = app(SurvivorshipRuleRepository::class)->create(['entity_type_id' => $other->id, 'target' => 'label', 'strategy' => 'most_recent']);

    $this->putJson(route('admin.masterdata.survivorship_rules.update', [$this->type->id, $rule->id]), ['target' => 'label', 'strategy' => 'manual'])
        ->assertNotFound();

    $this->deleteJson(route('admin.masterdata.survivorship_rules.destroy', [$this->type->id, $rule->id]))->assertNotFound();
});

it('forbids survivorship rules without the entity type edit permission', function () {
    $this->loginWithPermissions('custom', ['dashboard', 'masterdata', 'masterdata.entity_types']);

    $rule = app(SurvivorshipRuleRepository::class)->create(['entity_type_id' => $this->type->id, 'target' => 'label', 'strategy' => 'most_recent']);

    $this->getJson(route('admin.masterdata.survivorship_rules.index', $this->type->id))->assertForbidden();
    $this->postJson($this->storeUrl, ['target' => 'vat', 'strategy' => 'manual'])->assertForbidden();
    $this->putJson(route('admin.masterdata.survivorship_rules.update', [$this->type->id, $rule->id]), ['target' => 'label', 'strategy' => 'manual'])->assertForbidden();
    $this->deleteJson(route('admin.masterdata.survivorship_rules.destroy', [$this->type->id, $rule->id]))->assertForbidden();

    expect(SurvivorshipRule::query()->where('entity_type_id', $this->type->id)->count())->toBe(1);
});

it('lists the survivorship rules on the entity type api', function () {
    app(SurvivorshipRuleRepository::class)->create(['entity_type_id' => $this->type->id, 'target' => null, 'strategy' => 'source_priority', 'options' => ['source_order' => [$this->erp->id]]]);

    $this->getJson(route('admin.api.masterdata.entity_types.get', $this->type->code), $this->getAuthenticationHeaders())
        ->assertOk()
        ->assertJsonPath('data.survivorship_rules.0.target', null)
        ->assertJsonPath('data.survivorship_rules.0.strategy', 'source_priority')
        ->assertJsonPath('data.survivorship_rules.0.options.source_order', [$this->erp->id]);
});

it('shows a label rule on the merge screen', function () {
    $this->loginAsAdmin();

    app(SurvivorshipRuleRepository::class)->create(['entity_type_id' => $this->type->id, 'target' => 'label', 'strategy' => 'most_complete']);

    $writer = app(RecordWriter::class);
    $survivor = $writer->create($this->type, ['code' => 'S-1', 'label' => 'Acme'], null);
    $duplicate = $writer->create($this->type, ['code' => 'S-2', 'label' => 'Acme Holdings'], null);

    $this->get(route('admin.masterdata.records.merge.create', ['uuid' => $survivor->uuid, 'with' => $duplicate->uuid]))
        ->assertOk()
        ->assertSee(trans('masterdata::app.survivorship-rules.targets.label'))
        ->assertSee('Acme Holdings');
});
