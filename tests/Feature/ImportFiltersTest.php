<?php

use Illuminate\Support\Facades\Storage;
use Webkul\DataTransfer\Helpers\Import;
use Webkul\DataTransfer\Jobs\Import\ImportTrackBatch;
use Webkul\DataTransfer\Models\JobInstances;
use Webkul\DataTransfer\Models\JobTrack;
use Webkul\MasterDataManagement\Models\Record;
use Webkul\MasterDataManagement\Models\SourceSystem;
use Webkul\MasterDataManagement\Repositories\CodeListRepository;
use Webkul\MasterDataManagement\Repositories\EntityTypeRepository;
use Webkul\MasterDataManagement\Services\FieldService;

beforeEach(function () {
    $this->type = app(EntityTypeRepository::class)->create(['code' => $this->uniqueCode('asset'), 'status' => 'active', 'code_strategy' => 'source_id', 'sort_order' => 1]);
    $this->inactive = app(EntityTypeRepository::class)->create(['code' => $this->uniqueCode('retired'), 'status' => 'archived']);

    app(FieldService::class)->create($this->type, ['code' => 'name', 'type' => 'text']);

    $this->system = SourceSystem::query()->create(['code' => $this->uniqueCode('cmms'), 'name' => 'Plant CMMS', 'type' => 'cmms', 'priority' => 1, 'is_active' => true]);
    $this->offline = SourceSystem::query()->create(['code' => $this->uniqueCode('legacy'), 'name' => 'Legacy ERP', 'type' => 'erp', 'priority' => 2, 'is_active' => false]);

    $this->codeList = app(CodeListRepository::class)->createWithDraft(['code' => $this->uniqueCode('classes')]);

    $this->escapedUrl = fn (string $route): string => str_replace('/', '\/', route($route));
});

it('renders the master data import filters as dropdowns of existing data on the create import page', function () {
    $this->loginAsAdmin();

    $response = $this->get(route('admin.settings.data_transfer.imports.create'))->assertOk();

    $response->assertSee(($this->escapedUrl)('admin.masterdata.imports.filters.entity_types'), false)
        ->assertSee(($this->escapedUrl)('admin.masterdata.imports.filters.source_systems'), false)
        ->assertSee(($this->escapedUrl)('admin.masterdata.imports.filters.code_lists'), false)
        ->assertSee('Asset Num=external_id', false);
});

it('lists the active entity types the admin may write, keyed by code, and restores a saved one', function () {
    $this->loginAsAdmin();

    $codes = collect($this->getJson(route('admin.masterdata.imports.filters.entity_types'))->assertOk()->json('options'))->pluck('code');

    expect($codes)->toContain($this->type->code)
        ->and($codes)->not->toContain($this->inactive->code);

    $restored = $this->getJson(route('admin.masterdata.imports.filters.entity_types', ['identifiers' => ['values' => [$this->type->code]], 'access' => 'edit']))
        ->assertOk()
        ->json('options');

    expect($restored)->toHaveCount(1)
        ->and($restored[0]['code'])->toBe($this->type->code)
        ->and($restored[0]['label'])->toContain('('.$this->type->code.')');
});

it('lists the active source systems and the code lists by code', function () {
    $this->loginAsAdmin();

    $systems = collect($this->getJson(route('admin.masterdata.imports.filters.source_systems'))->assertOk()->json('options'))->pluck('code');

    expect($systems)->toContain($this->system->code)
        ->and($systems)->not->toContain($this->offline->code);

    $lists = $this->getJson(route('admin.masterdata.imports.filters.code_lists', ['query' => $this->codeList->code]))->assertOk()->json('options');

    expect($lists)->toBe([['code' => $this->codeList->code, 'label' => $this->codeList->code]]);
});

it('refuses the filter options to admins who may not import', function () {
    $this->loginWithPermissions('custom', ['dashboard']);

    $this->getJson(route('admin.masterdata.imports.filters.entity_types'))->assertForbidden();
    $this->getJson(route('admin.masterdata.imports.filters.source_systems'))->assertForbidden();
    $this->getJson(route('admin.masterdata.imports.filters.code_lists'))->assertForbidden();
});

it('asks for the source system when the file has an external_id column but the job has none', function () {
    Storage::fake('private');

    $admin = $this->loginAsAdmin();

    $run = function (array $filters, string $csv) use ($admin): JobTrack {
        $path = 'imports/'.$this->uniqueCode('file').'.csv';

        Storage::disk('private')->put($path, $csv);

        $instance = JobInstances::query()->create([
            'code'                => $this->uniqueCode('records'),
            'entity_type'         => 'masterdata_records',
            'type'                => 'import',
            'action'              => Import::ACTION_APPEND,
            'validation_strategy' => Import::VALIDATION_STRATEGY_SKIP_ERRORS,
            'allowed_errors'      => 0,
            'field_separator'     => ',',
            'file_path'           => $path,
            'filters'             => $filters,
        ]);

        $track = JobTrack::query()->create([
            'state'               => Import::STATE_PENDING,
            'type'                => 'import',
            'action'              => Import::ACTION_APPEND,
            'validation_strategy' => Import::VALIDATION_STRATEGY_SKIP_ERRORS,
            'allowed_errors'      => 0,
            'field_separator'     => ',',
            'file_path'           => $path,
            'meta'                => $instance->toArray(),
            'job_instances_id'    => $instance->id,
            'user_id'             => $admin->id,
        ]);

        ImportTrackBatch::dispatchSync($track);

        return $track->refresh();
    };

    $track = $run(['entity_type' => $this->type->code, 'mapping' => 'Asset Num=external_id'], "code,name,Asset Num\nA-1,Pump,EQ-1\n");

    $errors = implode(' ', array_map(strval(...), (array) $track->errors));

    expect($track->state)->not->toBe(Import::STATE_COMPLETED)
        ->and($errors)->toContain(trans('masterdata::app.imports.errors.external-id-without-source-system', ['column' => 'Asset Num']))
        ->and($errors)->not->toContain('invalid attribute')
        ->and(Record::query()->where('entity_type_id', $this->type->id)->exists())->toBeFalse();

    $linked = $run(['entity_type' => $this->type->code, 'source_system' => $this->system->code], "code,name,external_id\nA-2,Valve,EQ-2\n");

    expect($linked->state)->toBe(Import::STATE_COMPLETED)
        ->and(Record::query()->where('entity_type_id', $this->type->id)->pluck('code')->all())->toBe(['A-2']);
});

it('marks the filter fields as owning their error, so a refused save shows its message once', function () {
    $this->loginAsAdmin();

    $this->get(route('admin.settings.data_transfer.imports.create'))
        ->assertOk()
        ->assertSee(':data-error-slot="qualifiedName"', false);
});
