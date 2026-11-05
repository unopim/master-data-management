<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Webkul\DataTransfer\Helpers\Import;
use Webkul\DataTransfer\Models\JobInstances;
use Webkul\DataTransfer\Models\JobTrack;
use Webkul\MasterDataManagement\Demo\Services\DemoInstaller;
use Webkul\MasterDataManagement\Demo\Services\StewardshipInstaller;
use Webkul\MasterDataManagement\Enums\AliasScope;
use Webkul\MasterDataManagement\Enums\ChangeRequestStatus;
use Webkul\MasterDataManagement\Models\ChangeRequest;
use Webkul\MasterDataManagement\Models\CodeList;
use Webkul\MasterDataManagement\Models\EntityType;
use Webkul\MasterDataManagement\Models\MatchCandidate;
use Webkul\MasterDataManagement\Models\QualityIssue;
use Webkul\MasterDataManagement\Models\Record;
use Webkul\MasterDataManagement\Models\Relation;
use Webkul\MasterDataManagement\Models\SourceSystem;
use Webkul\MasterDataManagement\Models\Xref;
use Webkul\MasterDataManagement\Repositories\CodeListVersionRepository;
use Webkul\MasterDataManagement\Repositories\EntityTypeRepository;
use Webkul\MasterDataManagement\Security\ChangeApprovals;
use Webkul\MasterDataManagement\Services\AliasResolver;
use Webkul\MasterDataManagement\Services\XrefService;
use Webkul\User\Models\Admin;

beforeEach(function () {
    Storage::fake('private');

    foreach (['mdm_match_candidates', 'mdm_xrefs', 'mdm_records', 'mdm_relation_types', 'mdm_fields', 'mdm_entity_types', 'mdm_aliases', 'mdm_code_lists', 'mdm_source_systems'] as $table) {
        DB::table($table)->delete();
    }

    DB::table('job_instances')->where('entity_type', 'like', 'masterdata_%')->delete();
});

it('installs the demo once and skips or reruns it without duplicates', function () {
    $this->artisan('masterdata-demo:install')
        ->expectsOutputToContain('88 records')
        ->assertSuccessful();

    $counts = fn (): array => [Record::query()->count(), Xref::query()->count(), ChangeRequest::query()->count(), MatchCandidate::query()->count()];

    $first = $counts();

    $this->artisan('masterdata-demo:install')
        ->expectsOutputToContain(trans('masterdata-demo::app.install.already-installed'))
        ->assertSuccessful();

    $this->artisan('masterdata-demo:install', ['--force' => true])->assertSuccessful();

    expect($first)->toBe([88, 250, 6, 5])
        ->and($counts())->toBe($first);
});

it('links one line across the source systems and resolves aliases to approved codes', function () {
    $this->artisan('masterdata-demo:install')->assertSuccessful();

    $line = EntityType::query()->where('code', 'line')->firstOrFail();
    $mes = SourceSystem::query()->where('code', 'mes')->firstOrFail();

    $record = app(XrefService::class)->resolve($mes, $line->id, 'RHN.FILLING.L2');

    expect($record->code)->toBe('RHN-L02')
        ->and(Record::query()->find($record->parent_id)->code)->toBe('RHN-PCK')
        ->and(app(XrefService::class)->idsFor($record))->toMatchArray([
            'sap_s4' => ['2040-PK-L02'],
            'maximo' => ['RHN-LN02'],
        ]);

    $functions = CodeList::query()->where('code', 'department_function')->firstOrFail();

    expect(app(AliasResolver::class)->resolve('pkg', AliasScope::CodeList, $functions->id))->toBe('packing')
        ->and(app(AliasResolver::class)->resolve('QC', AliasScope::CodeList, $functions->id))->toBe('quality');
});

it('seeds the stewardship, quality and publishing scenario', function () {
    $this->artisan('masterdata-demo:install')->assertSuccessful();

    $statuses = ChangeRequest::query()->pluck('status')->map(fn (ChangeRequestStatus $status): string => $status->value)->countBy()->all();

    $steward = DB::table('admins')->where('email', StewardshipInstaller::STEWARD_EMAIL)->value('id');

    $approved = Record::query()->where('code', 'LKV-L01-A1-E2')->firstOrFail();

    expect($statuses)->toMatchArray(['pending' => 4, 'approved' => 1, 'rejected' => 1])
        ->and(ChangeRequest::query()->where('requested_by', '!=', $steward)->exists())->toBeFalse()
        ->and($approved->values['common']['criticality'])->toBe('B')
        ->and(QualityIssue::query()->where('status', 'open')->pluck('rule')->countBy()->all())->toMatchArray([
            'required_xref' => 4,
            'validity'      => 3,
            'duplicate'     => 1,
        ])
        ->and(MatchCandidate::query()->where('status', 'pending')->count())->toBe(5)
        ->and(DB::table('job_instances')->where('entity_type', 'masterdata_records')->where('type', 'export')->count())->toBe(7);
});

it('orders the entity types by the business hierarchy and keeps their descriptions in step on a forced rerun', function () {
    $this->artisan('masterdata-demo:install')->assertSuccessful();

    $hierarchy = ['site', 'department', 'platform', 'line', 'area', 'equipment'];
    $locale = core()->getDefaultLocaleCodeFromDefaultChannel();

    EntityType::query()->whereIn('code', $hierarchy)->each(fn (EntityType $type) => $type->update(['sort_order' => 0, 'icon' => null, $locale => ['description' => null]]));

    $this->artisan('masterdata-demo:install', ['--force' => true])->assertSuccessful();

    $types = app(EntityTypeRepository::class)->listActive();

    expect($types->pluck('code')->all())->toBe($hierarchy)
        ->and($types->pluck('sort_order')->all())->toBe([10, 20, 30, 40, 50, 60])
        ->and($types->pluck('icon')->unique()->count())->toBe(6)
        ->and($types->pluck('icon')->diff(config('masterdata.entity_type_icons'))->all())->toBe([])
        ->and($types->firstWhere('code', 'site')->translate($locale)->description)->toBe('A factory or plant at one address.')
        ->and($types->filter(fn (EntityType $type): bool => blank($type->translate($locale)->description))->all())->toBe([])
        ->and(EntityType::query()->whereIn('code', $hierarchy)->count())->toBe(6);
});

it('seeds plant data that holds together: one platform per production chain, assets installed after their line, valid GLNs', function () {
    $this->artisan('masterdata-demo:install')->assertSuccessful();

    $line = EntityType::query()->where('code', 'line')->firstOrFail();

    $platforms = Relation::query()
        ->whereHas('relationType', fn ($query) => $query->where('code', 'field:line.platform'))
        ->with(['fromRecord:id,code', 'toRecord:id,code'])
        ->get()
        ->groupBy(fn (Relation $relation): string => explode('-', $relation->fromRecord->code)[0])
        ->map(fn ($relations) => $relations->pluck('toRecord.code')->unique()->values()->all())
        ->sortKeys()
        ->all();

    expect($platforms)->toBe(['LKV' => ['PLAT-LIQ'], 'PNE' => ['PLAT-HAIR'], 'RHN' => ['PLAT-LIQ']]);

    $commissioned = Record::query()->where('entity_type_id', $line->id)->get()
        ->mapWithKeys(fn (Record $record): array => [$record->code => $record->values['common']['commissioned_on']]);

    $early = Record::query()
        ->whereHas('entityType', fn ($query) => $query->where('code', 'equipment'))
        ->get()
        ->filter(fn (Record $record): bool => isset($record->values['common']['install_date'])
            && $record->values['common']['install_date'] < $commissioned[substr($record->code, 0, 7)])
        ->pluck('code')
        ->all();

    expect($early)->toBe([]);

    $glns = Record::query()
        ->whereHas('entityType', fn ($query) => $query->where('code', 'site'))
        ->get()
        ->map(fn (Record $record): string => $record->values['common']['gln']);

    $checkDigitHolds = function (string $gln): bool {
        $sum = 0;

        foreach (str_split(substr($gln, 0, 12)) as $index => $digit) {
            $sum += (int) $digit * ($index % 2 === 0 ? 1 : 3);
        }

        return (10 - $sum % 10) % 10 === (int) $gln[12];
    };

    expect($glns->reject($checkDigitHolds)->values()->all())->toBe([]);

    $regions = CodeList::query()->where('code', 'region')->firstOrFail();

    expect(app(AliasResolver::class)->resolve('GC', AliasScope::CodeList, $regions->id))->toBe('greater_china')
        ->and(app(AliasResolver::class)->resolve('EMEA', AliasScope::CodeList, $regions->id))->not->toBe('europe');
});

it('creates the stewardship roles and users, all signing in with admin123, with the owner deciding governed changes', function () {
    $this->artisan('masterdata-demo:install')
        ->expectsOutputToContain('site.owner@example.com')
        ->assertSuccessful();

    $personas = app(DemoInstaller::class)->dataset()['personas'];

    $users = Admin::query()->with('role')->whereIn('email', array_column($personas, 'email'))->get()->keyBy('email');

    expect($users)->toHaveCount(5)
        ->and($users->every(fn (Admin $user): bool => Hash::check('admin123', $user->password) && $user->status))->toBeTrue()
        ->and($users->pluck('role.name')->sort()->values()->all())->toBe(collect($personas)->pluck('role')->sort()->values()->all())
        ->and($users->pluck('role.permission_type')->unique()->all())->toBe(['custom']);

    $owner = $users[$personas['owner']['email']];
    $steward = $users[$personas['steward']['email']];

    $governed = EntityType::query()->where('approval_required', true)->get();

    expect($governed->pluck('code')->sort()->values()->all())->toBe(['equipment', 'line', 'site'])
        ->and($governed->pluck('data_owner_id')->unique()->all())->toBe([$owner->id])
        ->and($governed->every(fn (EntityType $type): bool => app(ChangeApprovals::class)->bypasses($owner, $type)))->toBeTrue()
        ->and($governed->contains(fn (EntityType $type): bool => app(ChangeApprovals::class)->bypasses($steward, $type)))->toBeFalse()
        ->and(ChangeRequest::query()->whereNotNull('decided_by')->pluck('decided_by')->unique()->all())->toBe([$owner->id]);
});

it('signs the demo users in with the password given on the command line', function () {
    $this->artisan('masterdata-demo:install', ['--force' => true, '--password' => 'S1te-Demo!'])->assertSuccessful();

    expect(Hash::check('S1te-Demo!', Admin::query()->where('email', 'reference.data@example.com')->value('password')))->toBeTrue();
});

it('seeds import jobs with their file stored, each completing on the demo data when the admin presses Import Now', function () {
    $this->artisan('masterdata-demo:install')->assertSuccessful();
    $this->artisan('masterdata-demo:install', ['--force' => true])->assertSuccessful();

    $codes = ['mdm_maximo_equipment_import', 'mdm_sap_lines_import', 'mdm_maximo_crosswalk_import', 'mdm_equipment_class_import'];

    $jobs = JobInstances::query()->where('type', 'import')->whereIn('code', $codes)->get()->keyBy('code');

    expect($jobs->keys()->sort()->values()->all())->toBe(collect($codes)->sort()->values()->all())
        ->and($jobs->every(fn (JobInstances $job): bool => Storage::disk('private')->exists($job->file_path)))->toBeTrue();

    $this->loginAsAdmin();

    $pendingBefore = ChangeRequest::query()->where('status', ChangeRequestStatus::Pending)->count();

    $summaries = [];

    foreach ($codes as $code) {
        $this->put(route('admin.settings.data_transfer.imports.import_now', $jobs[$code]->id))->assertRedirect();

        $track = JobTrack::query()->where('job_instances_id', $jobs[$code]->id)->latest('id')->firstOrFail();

        expect($track->state)->toBe(Import::STATE_COMPLETED, $code)
            ->and((int) ($track->summary['skipped'] ?? 0))->toBe(0, $code);

        $summaries[$code] = [(int) ($track->summary['created'] ?? 0), (int) ($track->summary['updated'] ?? 0)];
    }

    $line = EntityType::query()->where('code', 'line')->firstOrFail();
    $lines = Record::query()->where('entity_type_id', $line->id)->whereIn('code', ['LKV-L03', 'RHN-L01', 'PNE-L02'])->get()->keyBy('code');
    $classes = CodeList::query()->where('code', 'equipment_class')->firstOrFail();

    expect($summaries)->toBe([
        'mdm_maximo_equipment_import' => [2, 1],
        'mdm_sap_lines_import'        => [0, 3],
        'mdm_maximo_crosswalk_import' => [2, 0],
        'mdm_equipment_class_import'  => [2, 0],
    ])
        ->and($lines['LKV-L03']->values['common'])->toMatchArray(['rated_speed_ppm' => 65, 'shifts_per_day' => 3, 'operating_status' => 'running'])
        ->and($lines['RHN-L01']->values['common'])->toMatchArray(['rated_speed_ppm' => 40, 'shifts_per_day' => 2])
        ->and($lines['PNE-L02']->values['common'])->toMatchArray(['rated_speed_ppm' => 220, 'operating_status' => 'maintenance'])
        ->and(app(CodeListVersionRepository::class)->findDraft($classes->id)->codes()->pluck('code')->all())->toContain('metal_detector', 'case_sealer')
        ->and(ChangeRequest::query()->where('status', ChangeRequestStatus::Pending)->count())->toBe($pendingBefore);
});
