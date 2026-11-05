<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Webkul\DataTransfer\Helpers\Import;
use Webkul\DataTransfer\Jobs\Import\ImportTrackBatch;
use Webkul\DataTransfer\Models\JobInstances;
use Webkul\DataTransfer\Models\JobTrack;
use Webkul\MasterDataManagement\Demo\Services\StewardshipInstaller;
use Webkul\MasterDataManagement\Enums\ChangeRequestStatus;
use Webkul\MasterDataManagement\Models\ChangeRequest;
use Webkul\MasterDataManagement\Models\CodeList;
use Webkul\MasterDataManagement\Models\EntityType;
use Webkul\MasterDataManagement\Models\Record;
use Webkul\MasterDataManagement\Models\SourceSystem;
use Webkul\MasterDataManagement\Repositories\CodeListVersionRepository;
use Webkul\MasterDataManagement\Services\XrefService;

beforeEach(function () {
    foreach (['mdm_change_requests', 'mdm_match_candidates', 'mdm_xrefs', 'mdm_records', 'mdm_relation_types', 'mdm_fields', 'mdm_entity_types', 'mdm_aliases', 'mdm_code_lists', 'mdm_source_systems'] as $table) {
        DB::table($table)->delete();
    }

    DB::table('job_instances')->where('entity_type', 'like', 'masterdata_%')->delete();

    Storage::fake('private');

    $this->artisan('masterdata-demo:install')->assertSuccessful();

    $this->equipment = EntityType::query()->where('code', 'equipment')->firstOrFail();

    $this->owner = (int) $this->equipment->data_owner_id;

    $this->steward = (int) DB::table('admins')->where('email', StewardshipInstaller::STEWARD_EMAIL)->value('id');

    $this->runImport = function (string $jobType, string $sample, array $filters, int $userId): JobTrack {
        $path = 'imports/'.$sample;

        Storage::disk('private')->put($path, file_get_contents(dirname(__DIR__, 3).'/src/Resources/samples/'.$sample));

        $instance = JobInstances::query()->create([
            'code'                => 'mdm_import_'.str_replace(['.', '-'], '_', $sample).'_'.$userId,
            'entity_type'         => $jobType,
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
            'user_id'             => $userId,
        ]);

        ImportTrackBatch::dispatchSync($track);

        return $track->refresh();
    };

    $this->equipmentRecord = fn (string $code): ?Record => Record::query()
        ->where('entity_type_id', $this->equipment->id)
        ->where('code', $code)
        ->first();
});

it('imports the Maximo equipment sample as the data owner: creates, updates and links in the crosswalk', function () {
    $maximo = SourceSystem::query()->where('code', 'maximo')->firstOrFail();

    $existing = ($this->equipmentRecord)('LKV-L02-A1-E1');

    $track = ($this->runImport)('masterdata_records', 'records.csv', ['entity_type' => 'equipment', 'source_system' => 'maximo'], $this->owner);

    expect($track->state)->toBe(Import::STATE_COMPLETED)
        ->and((int) ($track->summary['created'] ?? 0))->toBe(2)
        ->and((int) ($track->summary['updated'] ?? 0))->toBe(1);

    $labeler = ($this->equipmentRecord)('LKV-L03-A1-E3');

    expect($labeler)->not->toBeNull()
        ->and($labeler->values['common']['equipment_class'])->toBe('labeler')
        ->and($labeler->values['common']['serial_number'])->toBe('ETK-LB-300-02210')
        ->and($labeler->label)->toBe('Lakeview L3 Case Labeler')
        ->and(Record::query()->find($labeler->parent_id)->code)->toBe('LKV-L03-A1')
        ->and(app(XrefService::class)->resolve($maximo, $this->equipment->id, 'A-0101210')?->id)->toBe($labeler->id)
        ->and(app(XrefService::class)->resolve($maximo, $this->equipment->id, 'A-0101211')?->code)->toBe('LKV-L03-A2-E3');

    $updated = ($this->equipmentRecord)('LKV-L02-A1-E1');

    expect($updated->id)->toBe($existing->id)
        ->and($updated->revision)->toBe($existing->revision + 1)
        ->and($updated->values['common']['operating_status'])->toBe('maintenance')
        ->and($updated->values['common']['name'])->toBe($existing->values['common']['name']);
});

it('holds the steward imports of a governed type as change requests', function () {
    $pendingBefore = ChangeRequest::query()->where('status', ChangeRequestStatus::Pending)->count();

    $track = ($this->runImport)('masterdata_records', 'records.csv', ['entity_type' => 'equipment', 'source_system' => 'maximo'], $this->steward);

    expect($track->state)->toBe(Import::STATE_COMPLETED)
        ->and((int) ($track->summary['created'] ?? 0))->toBe(0)
        ->and(($this->equipmentRecord)('LKV-L03-A1-E3'))->toBeNull()
        ->and(($this->equipmentRecord)('LKV-L02-A1-E1')->values['common']['operating_status'])->toBe('running')
        ->and(ChangeRequest::query()->where('status', ChangeRequestStatus::Pending)->count())->toBe($pendingBefore + 3);
});

it('does not open a second change request when the steward imports the same file again', function () {
    $pendingBefore = ChangeRequest::query()->where('status', ChangeRequestStatus::Pending)->count();

    ($this->runImport)('masterdata_records', 'records.csv', ['entity_type' => 'equipment', 'source_system' => 'maximo'], $this->steward);

    $instance = JobInstances::query()->where('code', 'mdm_import_records_csv_'.$this->steward)->firstOrFail();

    $track = JobTrack::query()->create([
        'state'               => Import::STATE_PENDING,
        'type'                => 'import',
        'action'              => Import::ACTION_APPEND,
        'validation_strategy' => Import::VALIDATION_STRATEGY_SKIP_ERRORS,
        'allowed_errors'      => 0,
        'field_separator'     => ',',
        'file_path'           => $instance->file_path,
        'meta'                => $instance->toArray(),
        'job_instances_id'    => $instance->id,
        'user_id'             => $this->steward,
    ]);

    ImportTrackBatch::dispatchSync($track);

    expect($track->refresh()->state)->toBe(Import::STATE_COMPLETED)
        ->and(ChangeRequest::query()->where('status', ChangeRequestStatus::Pending)->count())->toBe($pendingBefore + 3);
});

it('fills the missing Maximo ids of the demo from the crosswalk sample', function () {
    $maximo = SourceSystem::query()->where('code', 'maximo')->firstOrFail();

    $track = ($this->runImport)('masterdata_xrefs', 'xrefs.csv', ['entity_type' => 'equipment', 'source_system' => 'maximo'], $this->owner);

    expect($track->state)->toBe(Import::STATE_COMPLETED)
        ->and((int) ($track->summary['created'] ?? 0))->toBe(2)
        ->and(app(XrefService::class)->resolve($maximo, $this->equipment->id, 'A-0204024')?->code)->toBe('RHN-L03-A2-E2')
        ->and(app(XrefService::class)->resolve($maximo, $this->equipment->id, 'A-0311029')?->code)->toBe('PNE-L02-A1-E1');
});

it('adds the code list sample to the draft version of the equipment classes', function () {
    $list = CodeList::query()->where('code', 'equipment_class')->firstOrFail();

    $track = ($this->runImport)('masterdata_code_lists', 'code-lists.csv', ['code_list' => 'equipment_class'], $this->owner);

    $draft = app(CodeListVersionRepository::class)->findDraft($list->id);

    expect($track->state)->toBe(Import::STATE_COMPLETED)
        ->and((int) ($track->summary['created'] ?? 0))->toBe(2)
        ->and($draft)->not->toBeNull()
        ->and($draft->codes()->pluck('code')->all())->toContain('metal_detector', 'case_sealer');
});
