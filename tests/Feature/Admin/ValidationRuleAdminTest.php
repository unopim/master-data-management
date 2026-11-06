<?php

use Webkul\AdminApi\Tests\Traits\ApiHelperTrait;
use Webkul\MasterDataManagement\Models\FieldTransform;
use Webkul\MasterDataManagement\Models\FieldValidationRule;
use Webkul\MasterDataManagement\Repositories\EntityTypeRepository;
use Webkul\MasterDataManagement\Repositories\FieldTransformRepository;
use Webkul\MasterDataManagement\Repositories\FieldValidationRuleRepository;
use Webkul\MasterDataManagement\Services\FieldService;

uses(ApiHelperTrait::class);

beforeEach(function () {
    $this->type = app(EntityTypeRepository::class)->create(['code' => $this->uniqueCode('supplier'), 'code_strategy' => 'source_id', 'status' => 'active']);

    $this->vat = app(FieldService::class)->create($this->type, ['code' => 'vat', 'type' => 'text']);
    $this->minQty = app(FieldService::class)->create($this->type, ['code' => 'min_qty', 'type' => 'integer']);

    $this->rulesUrl = route('admin.masterdata.validation_rules.store', $this->type->id);
    $this->transformsUrl = route('admin.masterdata.field_transforms.store', $this->type->id);
});

it('shows the validation tab with its grids', function () {
    $this->loginAsAdmin();

    app(FieldValidationRuleRepository::class)->create(['field_id' => $this->vat->id, 'type' => 'regex', 'params' => ['pattern' => '/^[A-Z]+$/'], 'severity' => 'warn']);
    app(FieldTransformRepository::class)->create(['field_id' => $this->vat->id, 'transform' => 'upper']);

    $this->get(route('admin.masterdata.entity_types.edit', ['id' => $this->type->id, 'validation' => 1]))
        ->assertOk()
        ->assertSee(trans('masterdata::app.validation-rules.title'))
        ->assertSee(trans('masterdata::app.field-transforms.title'))
        ->assertSee("type === 'cross_field'", false);

    $this->ajaxGrid(route('admin.masterdata.validation_rules.index', $this->type->id))
        ->assertOk()
        ->assertJsonPath('records.0.field_code', 'vat');

    $this->ajaxGrid(route('admin.masterdata.field_transforms.index', $this->type->id))
        ->assertOk()
        ->assertJsonPath('records.0.field_code', 'vat');
});

it('creates, updates and deletes a validation rule', function () {
    $this->loginAsAdmin();

    $this->postJson($this->rulesUrl, [
        'field_id' => $this->vat->id,
        'type'     => 'one_of_codes',
        'severity' => 'block',
        'params'   => ['codes' => "DE1, FR2\nIT3"],
        'messages' => ['en_US' => 'Pick a known VAT code.'],
    ])
        ->assertOk()
        ->assertJsonStructure(['redirect_url', 'message']);

    $rule = FieldValidationRule::query()->where('field_id', $this->vat->id)->sole();

    expect($rule->type)->toBe('one_of_codes')
        ->and($rule->severity->value)->toBe('block')
        ->and($rule->params)->toBe(['codes' => ['DE1', 'FR2', 'IT3']])
        ->and($rule->messages)->toBe(['en_US' => 'Pick a known VAT code.']);

    $this->putJson(route('admin.masterdata.validation_rules.update', [$this->type->id, $rule->id]), [
        'field_id' => $this->minQty->id,
        'type'     => 'cross_field',
        'severity' => 'warn',
        'params'   => ['field' => 'vat', 'operator' => 'neq', 'codes' => 'ignored'],
    ])->assertOk();

    $rule->refresh();

    expect($rule->field_id)->toBe($this->minQty->id)
        ->and($rule->severity->value)->toBe('warn')
        ->and($rule->params)->toBe(['field' => 'vat', 'operator' => 'neq'])
        ->and($rule->messages)->toBeNull();

    $this->deleteJson(route('admin.masterdata.validation_rules.destroy', [$this->type->id, $rule->id]))->assertOk();

    expect(FieldValidationRule::query()->whereKey($rule->id)->exists())->toBeFalse();
});

it('rejects invalid validation rules', function (array $payload, string $error) {
    $this->loginAsAdmin();

    $this->postJson($this->rulesUrl, $payload + ['field_id' => $this->vat->id, 'type' => 'regex', 'severity' => 'block', 'params' => ['pattern' => '/^A/']])
        ->assertUnprocessable()
        ->assertJsonValidationErrors($error);
})->with([
    'unknown type'           => [['type' => 'telepathy'], 'type'],
    'unknown severity'       => [['severity' => 'panic'], 'severity'],
    'broken regex'           => [['params' => ['pattern' => '/[a-/']], 'params.pattern'],
    'missing min value'      => [['type' => 'min', 'params' => []], 'params.value'],
    'length without bounds'  => [['type' => 'length', 'params' => []], 'params.min'],
    'cross field self'       => [['type' => 'cross_field', 'params' => ['field' => 'vat', 'operator' => 'eq']], 'params.field'],
    'cross field unknown op' => [['type' => 'cross_field', 'params' => ['field' => 'min_qty', 'operator' => 'like']], 'params.operator'],
    'empty code list'        => [['type' => 'one_of_codes', 'params' => ['codes' => ' , ']], 'params.codes'],
    'bad date bound'         => [['type' => 'date_range', 'params' => ['after' => 'not a date']], 'params.after'],
    'unknown locale message' => [['messages' => ['xx_YY' => 'Hi']], 'messages'],
]);

it('creates, updates and deletes a field transform, once per field', function () {
    $this->loginAsAdmin();

    $this->postJson($this->transformsUrl, ['field_id' => $this->vat->id, 'transform' => 'trim', 'sort_order' => 1])->assertOk();

    $transform = FieldTransform::query()->where('field_id', $this->vat->id)->sole();

    expect($transform->transform)->toBe('trim')
        ->and($transform->sort_order)->toBe(1);

    $this->postJson($this->transformsUrl, ['field_id' => $this->vat->id, 'transform' => 'trim'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('transform');

    $this->postJson($this->transformsUrl, ['field_id' => $this->vat->id, 'transform' => 'telepathy'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('transform');

    $this->putJson(route('admin.masterdata.field_transforms.update', [$this->type->id, $transform->id]), ['field_id' => $this->vat->id, 'transform' => 'upper', 'sort_order' => 3])->assertOk();

    expect($transform->refresh()->transform)->toBe('upper')
        ->and($transform->sort_order)->toBe(3);

    $this->deleteJson(route('admin.masterdata.field_transforms.destroy', [$this->type->id, $transform->id]))->assertOk();

    expect(FieldTransform::query()->whereKey($transform->id)->exists())->toBeFalse();
});

it('keeps rules and transforms of another entity type out of reach', function () {
    $this->loginAsAdmin();

    $other = app(EntityTypeRepository::class)->create(['code' => $this->uniqueCode('other')]);
    $foreign = app(FieldService::class)->create($other, ['code' => 'name', 'type' => 'text']);

    $rule = app(FieldValidationRuleRepository::class)->create(['field_id' => $foreign->id, 'type' => 'min', 'params' => ['value' => 1], 'severity' => 'block']);
    $transform = app(FieldTransformRepository::class)->create(['field_id' => $foreign->id, 'transform' => 'trim']);

    $this->postJson($this->rulesUrl, ['field_id' => $foreign->id, 'type' => 'min', 'severity' => 'block', 'params' => ['value' => 1]])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('field_id');

    $this->putJson(route('admin.masterdata.validation_rules.update', [$this->type->id, $rule->id]), ['field_id' => $this->vat->id, 'type' => 'min', 'severity' => 'block', 'params' => ['value' => 1]])
        ->assertNotFound();

    $this->deleteJson(route('admin.masterdata.validation_rules.destroy', [$this->type->id, $rule->id]))->assertNotFound();
    $this->deleteJson(route('admin.masterdata.field_transforms.destroy', [$this->type->id, $transform->id]))->assertNotFound();
});

it('forbids validation rules and transforms without the entity type edit permission', function () {
    $this->loginWithPermissions('custom', ['dashboard', 'masterdata', 'masterdata.entity_types']);

    $rule = app(FieldValidationRuleRepository::class)->create(['field_id' => $this->vat->id, 'type' => 'min', 'params' => ['value' => 1], 'severity' => 'block']);
    $transform = app(FieldTransformRepository::class)->create(['field_id' => $this->vat->id, 'transform' => 'trim']);

    $this->getJson(route('admin.masterdata.validation_rules.index', $this->type->id))->assertForbidden();
    $this->postJson($this->rulesUrl, ['field_id' => $this->vat->id, 'type' => 'min', 'severity' => 'block', 'params' => ['value' => 2]])->assertForbidden();
    $this->putJson(route('admin.masterdata.validation_rules.update', [$this->type->id, $rule->id]), ['field_id' => $this->vat->id, 'type' => 'min', 'severity' => 'warn', 'params' => ['value' => 2]])->assertForbidden();
    $this->deleteJson(route('admin.masterdata.validation_rules.destroy', [$this->type->id, $rule->id]))->assertForbidden();

    $this->getJson(route('admin.masterdata.field_transforms.index', $this->type->id))->assertForbidden();
    $this->postJson($this->transformsUrl, ['field_id' => $this->vat->id, 'transform' => 'upper'])->assertForbidden();
    $this->putJson(route('admin.masterdata.field_transforms.update', [$this->type->id, $transform->id]), ['field_id' => $this->vat->id, 'transform' => 'upper'])->assertForbidden();
    $this->deleteJson(route('admin.masterdata.field_transforms.destroy', [$this->type->id, $transform->id]))->assertForbidden();

    expect(FieldValidationRule::query()->where('field_id', $this->vat->id)->count())->toBe(1)
        ->and(FieldTransform::query()->where('field_id', $this->vat->id)->count())->toBe(1);
});

it('lists the validation rules and transforms of each field on the entity type api', function () {
    app(FieldValidationRuleRepository::class)->create(['field_id' => $this->vat->id, 'type' => 'regex', 'params' => ['pattern' => '/^[A-Z]+$/'], 'severity' => 'warn', 'messages' => ['en_US' => 'Upper case only.']]);
    app(FieldTransformRepository::class)->create(['field_id' => $this->vat->id, 'transform' => 'upper']);

    $this->getJson(route('admin.api.masterdata.entity_types.get', $this->type->code), $this->getAuthenticationHeaders())
        ->assertOk()
        ->assertJsonPath('data.fields.0.code', 'vat')
        ->assertJsonPath('data.fields.0.validation_rules.0.type', 'regex')
        ->assertJsonPath('data.fields.0.validation_rules.0.severity', 'warn')
        ->assertJsonPath('data.fields.0.validation_rules.0.params.pattern', '/^[A-Z]+$/')
        ->assertJsonPath('data.fields.0.validation_rules.0.messages.en_US', 'Upper case only.')
        ->assertJsonPath('data.fields.0.transforms', ['upper'])
        ->assertJsonPath('data.fields.1.transforms', []);
});
