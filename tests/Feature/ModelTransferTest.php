<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\ValidationException;
use Webkul\MasterDataManagement\Enums\CodeListVersionStatus;
use Webkul\MasterDataManagement\Enums\EntityTypeStatus;
use Webkul\MasterDataManagement\Models\CodeList;
use Webkul\MasterDataManagement\Models\CodeListVersion;
use Webkul\MasterDataManagement\Models\EntityType;
use Webkul\MasterDataManagement\Models\EntityTypeVersion;
use Webkul\MasterDataManagement\Models\Field;
use Webkul\MasterDataManagement\Models\MatchRule;
use Webkul\MasterDataManagement\Models\RelationType;
use Webkul\MasterDataManagement\Models\SurvivorshipRule;
use Webkul\MasterDataManagement\Repositories\CodeListRepository;
use Webkul\MasterDataManagement\Repositories\CodeRepository;
use Webkul\MasterDataManagement\Repositories\EntityTypeRepository;
use Webkul\MasterDataManagement\Repositories\MatchRuleRepository;
use Webkul\MasterDataManagement\Repositories\RelationTypeRepository;
use Webkul\MasterDataManagement\Repositories\SourceSystemRepository;
use Webkul\MasterDataManagement\Repositories\SurvivorshipRuleRepository;
use Webkul\MasterDataManagement\Services\CodeListPublisher;
use Webkul\MasterDataManagement\Services\FieldService;
use Webkul\MasterDataManagement\Services\ModelTransfer\ModelBundleExporter;
use Webkul\MasterDataManagement\Services\ModelTransfer\ModelBundleImporter;

beforeEach(function () {
    $this->prefix = strtolower(substr(md5(uniqid('', true)), 0, 6));

    $this->system = app(SourceSystemRepository::class)->create(['code' => 'erp_'.$this->prefix, 'name' => 'ERP', 'type' => 'erp', 'priority' => 10, 'is_active' => true]);

    $this->bundle = fn (): array => [
        'format_version' => 1,
        'code_lists'     => [[
            'code'         => 'status_'.$this->prefix,
            'owner'        => 'Ops',
            'is_versioned' => true,
            'translations' => ['en_US' => ['name' => 'Status', 'description' => null]],
            'codes'        => [
                ['code' => 'run', 'parent_code' => null, 'sort' => 1, 'is_active' => true, 'effective_from' => null, 'effective_to' => null, 'attributes' => null, 'labels' => ['en_US' => 'Running']],
                ['code' => 'stop', 'parent_code' => null, 'sort' => 2, 'is_active' => true, 'effective_from' => null, 'effective_to' => null, 'attributes' => null, 'labels' => ['en_US' => 'Stopped']],
            ],
        ]],
        'entity_types' => [[
            'code'                 => 'site_'.$this->prefix,
            'translations'         => ['en_US' => ['name' => 'Site', 'description' => 'A plant']],
            'icon'                 => null,
            'label_field_code'     => 'name',
            'is_hierarchical'      => false,
            'is_scoped'            => false,
            'allowed_parent_types' => [],
            'approval_required'    => false,
            'mastering_style'      => 'consolidation',
            'code_strategy'        => 'source_id',
            'code_pattern'         => '{TYPE}-{seq:6}',
            'status'               => 'active',
            'sort_order'           => 1,
            'settings'             => ['required_source_systems' => [$this->system->code]],
            'fields'               => [
                ['code' => 'name', 'type' => 'text', 'group' => null, 'position' => 1, 'is_required' => true, 'is_unique' => false, 'is_identifier' => false, 'value_per_locale' => false, 'is_effective_dated' => false, 'is_searchable' => true, 'multiple' => false, 'is_archived' => false, 'validation' => null, 'code_list' => null, 'reference_entity_type' => null, 'labels' => ['en_US' => 'Name'], 'help' => []],
                ['code' => 'state', 'type' => 'select', 'group' => null, 'position' => 2, 'is_required' => false, 'is_unique' => false, 'is_identifier' => false, 'value_per_locale' => false, 'is_effective_dated' => false, 'is_searchable' => false, 'multiple' => false, 'is_archived' => false, 'validation' => null, 'code_list' => 'status_'.$this->prefix, 'reference_entity_type' => null, 'labels' => ['en_US' => 'State'], 'help' => ['en_US' => 'Run state']],
            ],
            'match_rules' => [
                ['target' => 'name', 'comparator' => 'fuzzy', 'weight' => 2.0, 'is_blocking' => true, 'options' => null, 'sort_order' => 0],
            ],
            'survivorship_rules' => [
                ['target' => null, 'strategy' => 'source_priority', 'options' => ['source_order' => [$this->system->code]], 'sort_order' => 0],
            ],
        ], [
            'code'                 => 'line_'.$this->prefix,
            'translations'         => ['en_US' => ['name' => 'Line', 'description' => null]],
            'icon'                 => null,
            'label_field_code'     => null,
            'is_hierarchical'      => true,
            'is_scoped'            => false,
            'allowed_parent_types' => ['site_'.$this->prefix],
            'approval_required'    => false,
            'mastering_style'      => 'consolidation',
            'code_strategy'        => 'pattern',
            'code_pattern'         => 'LN-{seq:4}',
            'status'               => 'active',
            'sort_order'           => 2,
            'settings'             => null,
            'fields'               => [
                ['code' => 'site', 'type' => 'reference', 'group' => null, 'position' => 1, 'is_required' => false, 'is_unique' => false, 'is_identifier' => false, 'value_per_locale' => false, 'is_effective_dated' => false, 'is_searchable' => false, 'multiple' => false, 'is_archived' => false, 'validation' => null, 'code_list' => null, 'reference_entity_type' => 'site_'.$this->prefix, 'labels' => ['en_US' => 'Site'], 'help' => []],
            ],
            'match_rules'        => [],
            'survivorship_rules' => [],
        ]],
        'relation_types' => [[
            'code'             => 'feeds_'.$this->prefix,
            'from_entity_type' => 'line_'.$this->prefix,
            'to_entity_type'   => 'site_'.$this->prefix,
            'cardinality'      => 'many',
            'labels'           => ['en_US' => 'Feeds'],
        ]],
    ];

    $this->importer = app(ModelBundleImporter::class);

    $this->type = fn (string $code): ?EntityType => EntityType::query()->where('code', $code.'_'.$this->prefix)->first();
});

it('exports the model of the chosen entity types as a versioned bundle', function () {
    $list = app(CodeListRepository::class)->createWithDraft(['code' => 'cls_'.$this->prefix, 'en_US' => ['name' => 'Classes']]);
    $draft = $list->versions()->firstOrFail();
    app(CodeRepository::class)->create(['version_id' => $draft->id, 'code' => 'pump', 'sort' => 1, 'en_US' => ['label' => 'Pump']]);
    app(CodeListPublisher::class)->publish($draft);

    $types = app(EntityTypeRepository::class);
    $site = $types->create(['code' => 'site_'.$this->prefix, 'en_US' => ['name' => 'Site']]);
    $line = $types->create(['code' => 'line_'.$this->prefix, 'is_hierarchical' => true, 'allowed_parent_type_ids' => [$site->id], 'en_US' => ['name' => 'Line']]);
    $types->create(['code' => 'other_'.$this->prefix, 'en_US' => ['name' => 'Other']]);

    app(FieldService::class)->create($line, ['code' => 'class', 'type' => 'select', 'code_list_id' => $list->id, 'en_US' => ['label' => 'Class']]);
    app(FieldService::class)->create($line, ['code' => 'site', 'type' => 'reference', 'reference_entity_type_id' => $site->id, 'en_US' => ['label' => 'Site']]);
    app(RelationTypeRepository::class)->create(['code' => 'feeds_'.$this->prefix, 'from_entity_type_id' => $line->id, 'to_entity_type_id' => $site->id, 'cardinality' => 'many', 'is_system' => false, 'en_US' => ['label' => 'Feeds']]);
    app(MatchRuleRepository::class)->create(['entity_type_id' => $line->id, 'target' => 'code', 'comparator' => 'exact', 'weight' => 3]);
    app(SurvivorshipRuleRepository::class)->create(['entity_type_id' => $line->id, 'target' => null, 'strategy' => 'source_priority', 'options' => ['source_order' => [$this->system->id]]]);

    $bundle = app(ModelBundleExporter::class)->export([$line->code]);

    $exported = collect($bundle['entity_types'])->keyBy('code');

    expect($bundle['format_version'])->toBe(ModelBundleExporter::FORMAT_VERSION)
        ->and($bundle)->toHaveKeys(['exported_at', 'code_lists', 'entity_types', 'relation_types'])
        ->and($exported->keys()->all())->toBe([$line->code])
        ->and($exported[$line->code]['allowed_parent_types'])->toBe([$site->code])
        ->and(collect($exported[$line->code]['fields'])->pluck('code')->all())->toBe(['class', 'site'])
        ->and(collect($exported[$line->code]['fields'])->firstWhere('code', 'class')['code_list'])->toBe($list->code)
        ->and(collect($exported[$line->code]['fields'])->firstWhere('code', 'site')['reference_entity_type'])->toBe($site->code)
        ->and($exported[$line->code]['match_rules'][0])->toMatchArray(['target' => 'code', 'comparator' => 'exact', 'weight' => 3.0])
        ->and($exported[$line->code]['survivorship_rules'][0]['options'])->toBe(['source_order' => [$this->system->code]])
        ->and(array_column($bundle['code_lists'], 'code'))->toBe([$list->code])
        ->and($bundle['code_lists'][0]['codes'][0])->toMatchArray(['code' => 'pump', 'labels' => ['en_US' => 'Pump']])
        ->and(array_column($bundle['relation_types'], 'code'))->toBe(['feeds_'.$this->prefix])
        ->and(collect($bundle['relation_types'])->pluck('code')->filter(fn (string $code): bool => str_starts_with($code, RelationTypeRepository::SYSTEM_PREFIX))->all())->toBe([]);
});

it('imports a bundle as unpublished draft schema', function () {
    $result = $this->importer->import(($this->bundle)());

    $site = ($this->type)('site');
    $line = ($this->type)('line');
    $list = CodeList::query()->where('code', 'status_'.$this->prefix)->firstOrFail();

    expect($result->created)->toBe(['code_lists' => 1, 'codes' => 2, 'entity_types' => 2, 'fields' => 3, 'relation_types' => 1, 'match_rules' => 1, 'survivorship_rules' => 1])
        ->and($site->status)->toBe(EntityTypeStatus::Draft)
        ->and($site->schema_version)->toBe(0)
        ->and($site->label_field_code)->toBe('name')
        ->and($site->settings['required_source_system_ids'])->toBe([(int) $this->system->id])
        ->and($line->allowed_parent_type_ids)->toBe([(int) $site->id])
        ->and(Field::query()->where('entity_type_id', $site->id)->where('code', 'state')->value('code_list_id'))->toBe((int) $list->id)
        ->and(Field::query()->where('entity_type_id', $line->id)->where('code', 'site')->value('reference_entity_type_id'))->toBe((int) $site->id)
        ->and(RelationType::query()->where('code', 'feeds_'.$this->prefix)->value('to_entity_type_id'))->toBe((int) $site->id)
        ->and(SurvivorshipRule::query()->where('entity_type_id', $site->id)->firstOrFail()->options)->toBe(['source_order' => [(int) $this->system->id]])
        ->and(MatchRule::query()->where('entity_type_id', $site->id)->firstOrFail()->is_blocking)->toBeTrue()
        ->and(EntityTypeVersion::query()->whereIn('entity_type_id', [$site->id, $line->id])->count())->toBe(0);

    $versions = CodeListVersion::query()->where('code_list_id', $list->id)->get();

    expect($versions)->toHaveCount(1)
        ->and($versions[0]->status)->toBe(CodeListVersionStatus::Draft)
        ->and($versions[0]->codes()->pluck('code')->sort()->values()->all())->toBe(['run', 'stop']);
});

it('publishes the imported schema and code lists when asked', function () {
    $this->importer->import(($this->bundle)(), publish: true);

    $site = ($this->type)('site');
    $list = CodeList::query()->where('code', 'status_'.$this->prefix)->firstOrFail();

    expect($site->status)->toBe(EntityTypeStatus::Active)
        ->and($site->schema_version)->toBe(1)
        ->and(EntityTypeVersion::query()->where('entity_type_id', $site->id)->count())->toBe(1)
        ->and(CodeListVersion::query()->where('code_list_id', $list->id)->pluck('status')->all())->toBe([CodeListVersionStatus::Published]);
});

it('re-imports the same bundle without duplicating or republishing anything', function () {
    $this->importer->import(($this->bundle)(), publish: true);

    $result = $this->importer->import(($this->bundle)(), publish: true);

    $site = ($this->type)('site');
    $list = CodeList::query()->where('code', 'status_'.$this->prefix)->firstOrFail();

    expect(array_sum($result->created))->toBe(0)
        ->and(array_sum($result->updated))->toBe(0)
        ->and($result->published)->toBe(['entity_types' => [], 'code_lists' => []])
        ->and($site->schema_version)->toBe(1)
        ->and(Field::query()->where('entity_type_id', $site->id)->count())->toBe(2)
        ->and(MatchRule::query()->where('entity_type_id', $site->id)->count())->toBe(1)
        ->and(CodeListVersion::query()->where('code_list_id', $list->id)->count())->toBe(1);
});

it('updates changed definitions by code and republishes only what changed', function () {
    $this->importer->import(($this->bundle)(), publish: true);

    $bundle = ($this->bundle)();
    $bundle['entity_types'][0]['fields'][0]['labels'] = ['en_US' => 'Site name'];
    $bundle['code_lists'][0]['codes'][] = ['code' => 'idle', 'sort' => 3, 'labels' => ['en_US' => 'Idle']];

    $result = $this->importer->import($bundle, publish: true);

    $site = ($this->type)('site');
    $list = CodeList::query()->where('code', 'status_'.$this->prefix)->firstOrFail();

    expect($result->updated['fields'])->toBe(1)
        ->and($result->published['entity_types'])->toBe([$site->code])
        ->and($result->published['code_lists'])->toBe([$list->code])
        ->and($site->schema_version)->toBe(2)
        ->and(($this->type)('line')->schema_version)->toBe(1)
        ->and(CodeListVersion::query()->where('code_list_id', $list->id)->where('status', 'published')->firstOrFail()->codes()->count())->toBe(3);
});

it('round trips an exported bundle into the same install as a no-op', function () {
    $this->importer->import(($this->bundle)(), publish: true);

    $exported = app(ModelBundleExporter::class)->export([($this->type)('site')->code, ($this->type)('line')->code]);

    $result = $this->importer->import(json_decode(json_encode($exported), true), publish: true);

    expect(array_sum($result->created) + array_sum($result->updated))->toBe(0)
        ->and($result->published)->toBe(['entity_types' => [], 'code_lists' => []]);
});

it('refuses an invalid bundle as a whole and leaves the database untouched', function (Closure $break, string $errorKey) {
    $bundle = ($this->bundle)();
    $break($bundle);

    $entityTypes = EntityType::query()->count();
    $codeLists = CodeList::query()->count();

    expect(fn () => $this->importer->import($bundle, publish: true))->toThrow(ValidationException::class);

    try {
        $this->importer->import($bundle);
    } catch (ValidationException $exception) {
        expect(array_keys($exception->errors()))->toContain($errorKey);
    }

    expect(EntityType::query()->count())->toBe($entityTypes)
        ->and(CodeList::query()->count())->toBe($codeLists);
})->with([
    'unsupported format version' => [function (array &$bundle) {
        $bundle['format_version'] = 99;
    }, 'format_version'],
    'unknown field type' => [function (array &$bundle) {
        $bundle['entity_types'][1]['fields'][0]['type'] = 'hologram';
    }, 'entity_types.1.fields.0.type'],
    'unknown code list' => [function (array &$bundle) {
        $bundle['entity_types'][0]['fields'][1]['code_list'] = 'nowhere';
    }, 'entity_types.0.fields.1.code_list'],
    'unknown relation target' => [function (array &$bundle) {
        $bundle['relation_types'][0]['to_entity_type'] = 'nowhere';
    }, 'relation_types.0.to_entity_type'],
    'unknown source system' => [function (array &$bundle) {
        $bundle['entity_types'][0]['survivorship_rules'][0]['options']['source_order'] = ['nowhere'];
    }, 'entity_types.0.survivorship_rules.0.options.source_order.0'],
    'match rule on an unknown field' => [function (array &$bundle) {
        $bundle['entity_types'][0]['match_rules'][0]['target'] = 'nowhere';
    }, 'entity_types.0.match_rules.0.target'],
    'duplicate entity type code' => [function (array &$bundle) {
        $bundle['entity_types'][1]['code'] = $bundle['entity_types'][0]['code'];
    }, 'entity_types.1.code'],
]);

it('refuses to change the type of an existing field', function () {
    $this->importer->import(($this->bundle)());

    $bundle = ($this->bundle)();
    $bundle['entity_types'][0]['fields'][0]['type'] = 'integer';

    expect(fn () => $this->importer->import($bundle))->toThrow(ValidationException::class);

    expect(Field::query()->where('entity_type_id', ($this->type)('site')->id)->where('code', 'name')->value('type'))->toBe('text');
});

it('exports and imports the model from the command line', function () {
    $this->importer->import(($this->bundle)());

    $path = storage_path('framework/testing/model-'.$this->prefix.'.json');

    File::ensureDirectoryExists(dirname($path));

    $this->artisan('masterdata:model:export', ['--types' => ['site_'.$this->prefix], '--path' => $path])->assertSuccessful();

    $bundle = json_decode((string) file_get_contents($path), true);

    expect(array_column($bundle['entity_types'], 'code'))->toBe(['site_'.$this->prefix]);

    $bundle['entity_types'][0]['code'] = 'plant_'.$this->prefix;
    $bundle['entity_types'][0]['match_rules'] = [];
    file_put_contents($path, json_encode($bundle));

    $this->artisan('masterdata:model:import', ['path' => $path, '--publish' => true])->assertSuccessful();

    expect(($this->type)('plant')->schema_version)->toBe(1);

    $this->artisan('masterdata:model:import', ['path' => $path.'.missing'])->assertFailed();

    File::delete($path);
});

it('downloads the model bundle from the admin', function () {
    $this->importer->import(($this->bundle)());

    $this->loginAsAdmin();

    $response = $this->get(route('admin.masterdata.model_transfer.export', ['types' => ['site_'.$this->prefix]]))
        ->assertOk()
        ->assertHeader('content-type', 'application/json');

    expect($response->headers->get('content-disposition'))->toContain('attachment')
        ->and(array_column(json_decode($response->streamedContent(), true)['entity_types'], 'code'))->toBe(['site_'.$this->prefix]);
});

it('imports an uploaded model bundle from the admin', function () {
    $this->loginAsAdmin();

    $file = UploadedFile::fake()->createWithContent('model.json', json_encode(($this->bundle)()));

    $this->post(route('admin.masterdata.model_transfer.import'), ['file' => $file, 'publish' => '1'], ['Accept' => 'application/json'])
        ->assertOk()
        ->assertJsonPath('redirect_url', route('admin.masterdata.entity_types.index'));

    expect(($this->type)('site')->schema_version)->toBe(1);
});

it('rejects uploads that are not a json bundle within the size limit', function (Closure $file) {
    $this->loginAsAdmin();

    $this->post(route('admin.masterdata.model_transfer.import'), ['file' => $file()], ['Accept' => 'application/json'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('file');

    expect(($this->type)('site'))->toBeNull();
})->with([
    'not json'     => [fn () => UploadedFile::fake()->createWithContent('model.csv', "code,name\nsite,Site")],
    'invalid json' => [fn () => UploadedFile::fake()->createWithContent('model.json', '{"format_version": 1,')],
    'too large'    => [fn () => UploadedFile::fake()->create('model.json', (int) config('masterdata.model_transfer.max_file_kb') + 1, 'application/json')],
]);

it('reports bundle validation errors of an upload without importing it', function () {
    $this->loginAsAdmin();

    $bundle = ($this->bundle)();
    $bundle['entity_types'][1]['fields'][0]['type'] = 'hologram';

    $this->post(route('admin.masterdata.model_transfer.import'), ['file' => UploadedFile::fake()->createWithContent('model.json', json_encode($bundle))], ['Accept' => 'application/json'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('file');

    expect(($this->type)('site'))->toBeNull();
});

it('forbids model export and import without their permissions', function () {
    $this->loginWithPermissions('custom', ['dashboard', 'masterdata', 'masterdata.entity_types']);

    $this->get(route('admin.masterdata.model_transfer.export'))->assertForbidden();

    $this->post(route('admin.masterdata.model_transfer.import'), ['file' => UploadedFile::fake()->createWithContent('model.json', json_encode(($this->bundle)()))], ['Accept' => 'application/json'])
        ->assertForbidden();

    expect(($this->type)('site'))->toBeNull();
});

it('shows the model transfer actions on the entity types page for permitted admins', function () {
    $this->loginAsAdmin();

    $this->get(route('admin.masterdata.entity_types.index'))
        ->assertOk()
        ->assertSee(route('admin.masterdata.model_transfer.export'), false)
        ->assertSee(route('admin.masterdata.model_transfer.import'), false)
        ->assertSeeText(trans('masterdata::app.model-transfer.import.title'));
});
