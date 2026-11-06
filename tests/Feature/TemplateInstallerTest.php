<?php

use Illuminate\Validation\ValidationException;
use Webkul\MasterDataManagement\Models\Code;
use Webkul\MasterDataManagement\Models\CodeList;
use Webkul\MasterDataManagement\Models\CodeListVersion;
use Webkul\MasterDataManagement\Models\EntityType;
use Webkul\MasterDataManagement\Models\Field;
use Webkul\MasterDataManagement\Models\Record;
use Webkul\MasterDataManagement\Services\TemplateInstaller;
use Webkul\MasterDataManagement\Services\TemplateReader;
use Webkul\MasterDataManagement\Services\TemplateValidator;

function templateLocales(): array
{
    return array_map('basename', glob(dirname(__DIR__, 2).'/src/Resources/lang/*', GLOB_ONLYDIR));
}

/**
 * Every label map of a template or code list, keyed by where it sits.
 *
 * @return array<string, array<string, string>>
 */
function templateLabelMaps(array $data, string $prefix): array
{
    $maps = isset($data['names']) ? [$prefix.'.names' => $data['names']] : [];

    foreach ($data['entity_types'] ?? [] as $type) {
        $maps[$prefix.'.'.$type['code']] = $type['names'];

        foreach ($type['fields'] ?? [] as $field) {
            $maps[$prefix.'.'.$type['code'].'.'.$field['code']] = $field['labels'];
        }
    }

    foreach ($data['codes'] ?? [] as $code) {
        $maps[$prefix.'.'.$code['code']] = $code['labels'];
    }

    return $maps;
}

function codeListCodes(string $code): int
{
    $list = CodeList::query()->where('code', $code)->firstOrFail();

    return Code::query()->whereIn('version_id', CodeListVersion::query()->where('code_list_id', $list->id)->pluck('id'))->count();
}

it('ships the domain templates and shared code lists', function () {
    $reader = app(TemplateReader::class);

    expect($reader->available())->toContain('customer', 'supplier', 'employee', 'location', 'product-reference', 'asset');

    foreach (['country', 'currency', 'uom', 'industry', 'payment_terms'] as $code) {
        expect($reader->hasCodeList($code))->toBeTrue();
    }

    expect(count($reader->readCodeList('country')['codes']))->toBe(249)
        ->and(array_column($reader->readCodeList('currency')['codes'], 'code'))->toContain('EUR', 'USD', 'JPY', 'INR', 'XCG')
        ->and(array_column($reader->readCodeList('industry')['codes'], 'code'))->toBe(range('A', 'U'));
});

it('validates every template and code list against the installer format', function () {
    $reader = app(TemplateReader::class);
    $validator = app(TemplateValidator::class);

    foreach ($reader->available() as $name) {
        $validator->validateTemplate($reader->read($name));
    }

    foreach (glob(dirname(__DIR__, 2).'/src/Resources/templates/code-lists/*.json') as $path) {
        $validator->validateCodeList($reader->readCodeList(basename($path, '.json')));
    }

    expect(true)->toBeTrue();
});

it('labels every template entity type, field and code in every locale, translated apart from proper country names', function () {
    $reader = app(TemplateReader::class);
    $locales = templateLocales();

    expect($locales)->toHaveCount(33);

    $maps = [];

    foreach ($reader->available() as $name) {
        $maps += templateLabelMaps($reader->read($name), $name);
    }

    foreach (glob(dirname(__DIR__, 2).'/src/Resources/templates/code-lists/*.json') as $path) {
        $maps += templateLabelMaps($reader->readCodeList(basename($path, '.json')), basename($path, '.json'));
    }

    $copies = 0;
    $total = 0;

    foreach ($maps as $where => $labels) {
        foreach ($locales as $locale) {
            expect($labels[$locale] ?? '')->not->toBe('', "{$where} has no {$locale} label");

            if (! str_starts_with($locale, 'en_') && ! str_starts_with($where, 'country.')) {
                $total++;
                $copies += (int) ($labels[$locale] === $labels['en_US']);
            }
        }
    }

    expect($copies / $total)->toBeLessThan(0.1);
});

it('rejects a template whose select field names no shipped code list', function () {
    $template = app(TemplateReader::class)->read('customer');

    $template['entity_types'][0]['fields'][] = ['code' => 'tier', 'type' => 'select', 'code_list' => 'missing_list', 'labels' => ['en_US' => 'Tier']];

    app(TemplateValidator::class)->validateTemplate($template);
})->throws(ValidationException::class);

it('installs the customer template with its fields, hierarchy and shared code lists', function () {
    $totals = app(TemplateInstaller::class)->install('customer');

    expect($totals)->toBe(['entity_types' => 2, 'fields' => 30, 'code_lists' => 5, 'source_systems' => 0, 'records' => 0]);

    $customer = EntityType::query()->where('code', 'customer')->firstOrFail();
    $contact = EntityType::query()->where('code', 'contact')->firstOrFail();

    expect($customer->is_hierarchical)->toBeTrue()
        ->and($customer->allowed_parent_type_ids)->toBe([$customer->id])
        ->and((int) $customer->schema_version)->toBe(1)
        ->and($customer->translate('en_US')->name)->toBe('Customer')
        ->and(Field::query()->where('entity_type_id', $customer->id)->count())->toBe(23)
        ->and(Field::query()->where('entity_type_id', $contact->id)->where('code', 'customer')->value('reference_entity_type_id'))->toBe($customer->id)
        ->and(Field::query()->where('entity_type_id', $customer->id)->where('code', 'customer_number')->value('is_identifier'))->toBeTruthy()
        ->and(codeListCodes('country'))->toBe(249)
        ->and(CodeListVersion::query()->where('code_list_id', CodeList::query()->where('code', 'country')->value('id'))->value('status')->value)->toBe('published');

    $country = Field::query()->where('entity_type_id', $customer->id)->where('code', 'country')->firstOrFail();

    expect($country->code_list_id)->toBe(CodeList::query()->where('code', 'country')->value('id'));
});

it('installs a template again without changing anything', function () {
    $installer = app(TemplateInstaller::class);

    $installer->install('supplier');

    $counts = fn (): array => [EntityType::query()->count(), Field::query()->count(), CodeList::query()->count(), Code::query()->count()];

    $before = $counts();

    expect($installer->install('supplier'))->toBe(['entity_types' => 0, 'fields' => 0, 'code_lists' => 0, 'source_systems' => 0, 'records' => 0])
        ->and($counts())->toBe($before);
});

it('shares one copy of a code list between templates', function () {
    $installer = app(TemplateInstaller::class);

    $installer->install('customer');

    expect($installer->install('supplier')['code_lists'])->toBe(2)
        ->and(CodeList::query()->where('code', 'country')->count())->toBe(1)
        ->and(CodeList::query()->where('code', 'payment_terms')->count())->toBe(1)
        ->and(codeListCodes('country'))->toBe(249);

    $countryId = CodeList::query()->where('code', 'country')->value('id');

    expect(Field::query()->where('code', 'country')->pluck('code_list_id')->unique()->values()->all())->toBe([$countryId]);
});

it('adds the sample records once, with references and parents resolved', function () {
    $installer = app(TemplateInstaller::class);

    expect($installer->install('customer', true)['records'])->toBe(4)
        ->and($installer->install('customer', true)['records'])->toBe(0);

    $customer = EntityType::query()->where('code', 'customer')->firstOrFail();

    $parent = Record::query()->where('entity_type_id', $customer->id)->where('code', 'C-10001')->firstOrFail();
    $child = Record::query()->where('entity_type_id', $customer->id)->where('code', 'C-10002')->firstOrFail();

    expect($child->parent_id)->toBe($parent->id)
        ->and($parent->label)->toBe('Acme Industrial Supplies Ltd')
        ->and($parent->values['common']['country'])->toBe('GB');
});

it('installs every template with its samples', function () {
    $installer = app(TemplateInstaller::class);

    foreach (app(TemplateReader::class)->available() as $name) {
        $installer->install($name, true);
    }

    $product = EntityType::query()->where('code', 'product_reference')->firstOrFail();
    $employee = EntityType::query()->where('code', 'employee')->firstOrFail();

    expect(Record::query()->where('entity_type_id', $product->id)->count())->toBe(2)
        ->and(Record::query()->where('entity_type_id', $employee->id)->count())->toBe(2)
        ->and(codeListCodes('uom'))->toBeGreaterThan(30)
        ->and(CodeList::query()->where('code', 'country')->count())->toBe(1);
});

it('still seeds the manufacturing demo on top of the location template', function () {
    app(TemplateInstaller::class)->install('location');

    $this->artisan('masterdata:demo:manufacturing')->assertSuccessful();

    $site = EntityType::query()->where('code', 'site')->firstOrFail();

    expect(Record::query()->where('entity_type_id', $site->id)->where('code', 'SITE-01')->exists())->toBeTrue()
        ->and(Field::query()->where('entity_type_id', $site->id)->where('code', 'gln')->exists())->toBeTrue();
});

it('installs templates from the command line', function () {
    $this->artisan('masterdata:templates:install', ['templates' => ['employee'], '--samples' => true])
        ->assertSuccessful();

    expect(EntityType::query()->where('code', 'employee')->exists())->toBeTrue()
        ->and(Record::query()->where('code', 'E-30002')->exists())->toBeTrue();

    $this->artisan('masterdata:templates:install', ['templates' => ['employee']])->assertSuccessful();
    $this->artisan('masterdata:templates:install', ['--list' => true])->assertSuccessful();
});

it('fails on the command line without a template or with an unknown one', function () {
    $this->artisan('masterdata:templates:install')->assertFailed();
    $this->artisan('masterdata:templates:install', ['templates' => ['nope']])->assertFailed();
});
