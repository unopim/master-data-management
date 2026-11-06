<?php

use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Webkul\AdminApi\Tests\Traits\ApiHelperTrait;
use Webkul\DataTransfer\Helpers\Import;
use Webkul\DataTransfer\Models\JobInstances;
use Webkul\MasterDataManagement\Enums\QualitySeverity;
use Webkul\MasterDataManagement\Models\Field;
use Webkul\MasterDataManagement\Models\QualityIssue;
use Webkul\MasterDataManagement\Models\Record;
use Webkul\MasterDataManagement\Quality\QualityScanner;
use Webkul\MasterDataManagement\Quality\Rules\FieldRuleWarningRule;
use Webkul\MasterDataManagement\Repositories\EntityTypeRepository;
use Webkul\MasterDataManagement\Repositories\FieldTransformRepository;
use Webkul\MasterDataManagement\Repositories\FieldValidationRuleRepository;
use Webkul\MasterDataManagement\Services\FieldService;
use Webkul\MasterDataManagement\Services\RecordWriter;
use Webkul\User\Models\Admin;

uses(ApiHelperTrait::class);

beforeEach(function () {
    $this->type = app(EntityTypeRepository::class)->create(['code' => $this->uniqueCode('supplier'), 'status' => 'active', 'code_strategy' => 'source_id']);

    $fields = app(FieldService::class);

    $this->name = $fields->create($this->type, ['code' => 'name', 'type' => 'text']);
    $this->email = $fields->create($this->type, ['code' => 'email', 'type' => 'email']);
    $this->vat = $fields->create($this->type, ['code' => 'vat', 'type' => 'text']);
    $this->minQty = $fields->create($this->type, ['code' => 'min_qty', 'type' => 'integer']);
    $this->maxQty = $fields->create($this->type, ['code' => 'max_qty', 'type' => 'integer']);
    $this->motto = $fields->create($this->type, ['code' => 'motto', 'type' => 'text', 'value_per_locale' => true]);

    $this->writer = app(RecordWriter::class);

    $this->transform = function (Field $field, array $transforms): void {
        foreach ($transforms as $position => $transform) {
            app(FieldTransformRepository::class)->create(['field_id' => $field->id, 'transform' => $transform, 'sort_order' => $position]);
        }
    };

    $this->rule = fn (Field $field, string $type, array $params, string $severity = 'block', ?array $messages = null) => app(FieldValidationRuleRepository::class)->create([
        'field_id' => $field->id,
        'type'     => $type,
        'params'   => $params,
        'severity' => $severity,
        'messages' => $messages,
    ]);

    $this->violation = function (Closure $write): array {
        try {
            $write();
        } catch (ValidationException $exception) {
            return $exception->errors();
        }

        return [];
    };
});

it('standardizes values with the field transforms in their order before storing them', function () {
    ($this->transform)($this->name, ['collapse_spaces', 'trim', 'title']);
    ($this->transform)($this->email, ['email_normalize']);
    ($this->transform)($this->motto, ['upper']);

    $record = $this->writer->create($this->type, [
        'code'   => 'S-1',
        'values' => [
            'common'          => ['name' => '  acme   holdings ', 'email' => ' Sales@Acme.COM '],
            'locale_specific' => ['en_US' => ['motto' => 'built to last']],
        ],
    ], null);

    expect($record->values['common']['name'])->toBe('Acme Holdings')
        ->and($record->values['common']['email'])->toBe('sales@acme.com')
        ->and($record->values['locale_specific']['en_US']['motto'])->toBe('BUILT TO LAST');

    $updated = $this->writer->update($record, ['values' => ['common' => ['name' => 'globex  corp']]], $record->revision, null);

    expect($updated->values['common']['name'])->toBe('Globex Corp');
});

it('runs the transforms before the validation rules', function () {
    ($this->transform)($this->vat, ['strip_non_alnum', 'upper']);
    ($this->rule)($this->vat, 'regex', ['pattern' => '/^[A-Z]{2}[0-9]+$/']);

    $record = $this->writer->create($this->type, ['code' => 'S-1', 'values' => ['common' => ['vat' => 'de 123-456']]], null);

    expect($record->values['common']['vat'])->toBe('DE123456');
});

it('refuses a write while a validation rule type is no longer registered', function () {
    ($this->rule)($this->vat, 'vat_check', []);

    $errors = ($this->violation)(fn () => $this->writer->create($this->type, ['code' => 'S-1', 'values' => ['common' => ['vat' => 'DE123']]], null));

    expect($errors)->toHaveKey('values.common.vat')
        ->and($errors['values.common.vat'][0])->toContain('vat_check')
        ->and(Record::query()->where('entity_type_id', $this->type->id)->exists())->toBeFalse();
});

it('refuses a write while a transform is no longer registered', function () {
    ($this->transform)($this->vat, ['trim', 'legacy_clean']);

    $errors = ($this->violation)(fn () => $this->writer->create($this->type, ['code' => 'S-1', 'values' => ['common' => ['vat' => ' DE123 ']]], null));

    expect($errors)->toHaveKey('values.common.vat')
        ->and($errors['values.common.vat'][0])->toContain('legacy_clean')
        ->and(Record::query()->where('entity_type_id', $this->type->id)->exists())->toBeFalse();

    $record = $this->writer->create($this->type, ['code' => 'S-2', 'values' => ['common' => ['name' => 'Acme']]], null);

    expect($record->values['common']['name'])->toBe('Acme');
});

it('refuses a value breaking a block rule with the message of the current locale', function () {
    ($this->rule)($this->vat, 'regex', ['pattern' => '/^[A-Z]{2}[0-9]+$/'], 'block', [
        'en_US' => 'Enter the VAT number with its country prefix.',
        'fr_FR' => 'Saisissez le numéro de TVA avec son préfixe pays.',
    ]);

    $write = fn () => $this->writer->create($this->type, ['code' => 'S-1', 'values' => ['common' => ['vat' => '123']]], null);

    expect(($this->violation)($write))->toBe(['values.common.vat' => ['Enter the VAT number with its country prefix.']]);

    app()->setLocale('fr_FR');

    expect(($this->violation)($write))->toBe(['values.common.vat' => ['Saisissez le numéro de TVA avec son préfixe pays.']])
        ->and(Record::query()->where('entity_type_id', $this->type->id)->exists())->toBeFalse();
});

it('falls back to the default message of the rule type', function () {
    ($this->rule)($this->name, 'length', ['min' => 3, 'max' => 10]);

    $errors = ($this->violation)(fn () => $this->writer->create($this->type, ['code' => 'S-1', 'values' => ['common' => ['name' => 'AB']]], null));

    expect($errors['values.common.name'][0])->toBe(trans('masterdata::app.validation-rules.messages.length', ['attribute' => 'name', 'min' => 3, 'max' => 10]));
});

it('checks a cross field rule against the other values of the record, stored ones included', function () {
    ($this->rule)($this->maxQty, 'cross_field', ['field' => 'min_qty', 'operator' => 'gte']);

    $record = $this->writer->create($this->type, ['code' => 'S-1', 'values' => ['common' => ['min_qty' => 5, 'max_qty' => 10]]], null);

    $errors = ($this->violation)(fn () => $this->writer->update($record, ['values' => ['common' => ['min_qty' => 20]]], $record->revision, null));

    expect($errors)->toHaveKey('values.common.max_qty');
});

it('checks the rules of per-locale values in every locale', function () {
    ($this->rule)($this->motto, 'length', ['max' => 5]);

    $errors = ($this->violation)(fn () => $this->writer->create($this->type, ['code' => 'S-1', 'values' => ['locale_specific' => ['en_US' => ['motto' => 'ok'], 'de_DE' => ['motto' => 'zu lang']]]], null));

    expect(array_keys($errors))->toBe(['values.locale_specific.de_DE.motto']);
});

it('stores a value breaking a warn rule and records a quality warning that clears once fixed', function () {
    ($this->rule)($this->vat, 'one_of_codes', ['codes' => ['DE1', 'FR2']], 'warn', ['en_US' => 'Unusual VAT number.']);

    $record = $this->writer->create($this->type, ['code' => 'S-1', 'values' => ['common' => ['vat' => 'XX9']]], null);

    expect($record->exists)->toBeTrue();

    $issue = QualityIssue::query()->where('record_id', $record->id)->where('rule', FieldRuleWarningRule::CODE)->sole();

    expect($issue->severity)->toBe(QualitySeverity::Warning)
        ->and($issue->status->value)->toBe('open')
        ->and($issue->details['message'])->toBe('Unusual VAT number.');

    $this->writer->update($record, ['values' => ['common' => ['vat' => 'DE1']]], $record->revision, null);

    expect($issue->refresh()->status->value)->toBe('resolved');
});

it('reports stored values breaking a block rule added later as validity issues', function () {
    $record = $this->writer->create($this->type, ['code' => 'S-1', 'values' => ['common' => ['min_qty' => 2]]], null);

    ($this->rule)($this->minQty, 'min', ['value' => 5]);

    app(QualityScanner::class)->scan($this->type->id);

    expect(QualityIssue::query()->where('record_id', $record->id)->where('rule', 'validity')->where('rule_key', 'values.common.min_qty')->exists())->toBeTrue();
});

it('standardizes and validates values written through the api', function () {
    ($this->transform)($this->email, ['email_normalize']);
    ($this->rule)($this->minQty, 'min', ['value' => 5]);

    $headers = $this->getAuthenticationHeaders();

    $this->postJson(route('admin.api.masterdata.records.store', $this->type->code), ['code' => 'S-1', 'values' => ['common' => ['email' => ' Buyer@Acme.COM', 'min_qty' => 2]]], $headers)
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['values.common.min_qty']);

    $this->postJson(route('admin.api.masterdata.records.store', $this->type->code), ['code' => 'S-1', 'values' => ['common' => ['email' => ' Buyer@Acme.COM', 'min_qty' => 7]]], $headers)
        ->assertCreated()
        ->assertJsonPath('data.values.common.email', 'buyer@acme.com');
});

it('standardizes and validates values written through an import', function () {
    Storage::fake('local');
    Storage::fake('private');

    ($this->transform)($this->email, ['trim', 'email_normalize']);
    ($this->transform)($this->name, ['title']);
    ($this->rule)($this->minQty, 'max', ['value' => 100]);

    $admin = Admin::factory()->create();

    $job = JobInstances::query()->create([
        'code'                => $this->uniqueCode('supplier_feed'),
        'entity_type'         => 'masterdata_records',
        'type'                => 'import',
        'action'              => Import::ACTION_APPEND,
        'validation_strategy' => Import::VALIDATION_STRATEGY_SKIP_ERRORS,
        'allowed_errors'      => 10,
        'field_separator'     => ',',
        'filters'             => ['entity_type' => $this->type->code],
    ]);

    config([
        'masterdata.scheduled_imports.disk' => 'local',
        'masterdata.scheduled_imports.path' => 'mdm-inbox',
        'masterdata.scheduled_imports.user' => $admin->email,
    ]);

    Storage::disk('local')->put('mdm-inbox/'.$job->code.'/feed.csv', "code,name,email,min_qty\nS-1,acme corp,\" Sales@Acme.COM \",10\nS-2,globex,buyer@globex.com,500\n");

    $this->artisan('masterdata:import', ['job_code' => $job->code, '--sync' => true])->assertSuccessful();

    $records = Record::query()->where('entity_type_id', $this->type->id)->get()->keyBy('code');

    expect($records->keys()->all())->toBe(['S-1'])
        ->and($records['S-1']->values['common']['name'])->toBe('Acme Corp')
        ->and($records['S-1']->values['common']['email'])->toBe('sales@acme.com');
});
