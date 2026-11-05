<?php

use Webkul\Core\Models\Locale;
use Webkul\MasterDataManagement\Repositories\EntityTypeRepository;
use Webkul\MasterDataManagement\Repositories\FieldRepository;
use Webkul\MasterDataManagement\Services\FieldService;
use Webkul\MasterDataManagement\Services\RecordFormBuilder;
use Webkul\MasterDataManagement\Services\RecordWriter;

beforeEach(function () {
    $this->loginAsAdmin();

    Locale::query()->where('code', 'fr_FR')->update(['status' => 1]);

    $this->type = app(EntityTypeRepository::class)->create(['code' => $this->uniqueCode('line'), 'code_strategy' => 'source_id']);

    app(FieldService::class)->create($this->type, ['code' => 'name', 'type' => 'text', 'value_per_locale' => true]);
    app(FieldService::class)->create($this->type, ['code' => 'speed', 'type' => 'text']);

    $this->record = app(RecordWriter::class)->create($this->type, [
        'code'   => 'L-1',
        'values' => [
            'common'          => ['speed' => 'fast'],
            'locale_specific' => ['en_US' => ['name' => 'Filling line'], 'fr_FR' => ['name' => 'Ligne de remplissage']],
        ],
    ], null);
});

it('edits per-locale fields in the default locale by default', function () {
    $this->get(route('admin.masterdata.records.edit', $this->record->uuid))
        ->assertOk()
        ->assertSee('values[locale_specific][en_US][name]', false)
        ->assertDontSee('values[locale_specific][fr_FR][name]', false)
        ->assertSee('locale=fr_FR', false);
});

it('edits per-locale fields in the requested active locale', function () {
    $this->get(route('admin.masterdata.records.edit', ['uuid' => $this->record->uuid, 'locale' => 'fr_FR']))
        ->assertOk()
        ->assertSee('values[locale_specific][fr_FR][name]', false)
        ->assertDontSee('values[locale_specific][en_US][name]', false)
        ->assertSee('values[common][speed]', false);
});

it('falls back to the default locale for an inactive or unknown locale', function (string $locale) {
    $this->get(route('admin.masterdata.records.edit', ['uuid' => $this->record->uuid, 'locale' => $locale]))
        ->assertOk()
        ->assertSee('values[locale_specific][en_US][name]', false);
})->with(['xx_XX', 'de_DE']);

it('saves a second locale without losing the default locale value', function () {
    $this->put(route('admin.masterdata.records.update', $this->record->uuid), [
        'revision' => $this->record->revision,
        'values'   => [
            'common'          => ['speed' => 'fast'],
            'locale_specific' => ['fr_FR' => ['name' => 'Ligne 1']],
        ],
    ])->assertSessionHasNoErrors();

    $values = $this->record->fresh()->values;

    expect($values['locale_specific']['fr_FR']['name'])->toBe('Ligne 1')
        ->and($values['locale_specific']['en_US']['name'])->toBe('Filling line');
});

it('marks a required per-locale field required only in the default locale', function () {
    $field = app(FieldRepository::class)->activeFor($this->type->id)->firstWhere('code', 'name');
    $field->update(['is_required' => true]);

    $builder = app(RecordFormBuilder::class);

    $required = fn (string $locale): bool => collect($builder->groups($this->type, $this->record, $locale))
        ->flatMap(fn (array $group) => $group['fields'])
        ->firstWhere('code', 'name')['required'];

    expect($required('en_US'))->toBeTrue()
        ->and($required('fr_FR'))->toBeFalse();
});

it('returns to the edited locale after saving', function () {
    $this->putJson(route('admin.masterdata.records.update', ['uuid' => $this->record->uuid, 'locale' => 'fr_FR']), [
        'revision' => $this->record->revision,
        'values'   => ['locale_specific' => ['fr_FR' => ['name' => 'Ligne 1']]],
    ])->assertOk()->assertJsonPath('redirect_url', route('admin.masterdata.records.edit', ['uuid' => $this->record->uuid, 'locale' => 'fr_FR']));
});
