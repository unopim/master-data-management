<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Webkul\MasterDataManagement\Enums\GrantPrincipal;
use Webkul\MasterDataManagement\Enums\WorkloadAgeBucket;
use Webkul\MasterDataManagement\Jobs\CaptureQualitySnapshots;
use Webkul\MasterDataManagement\Models\ChangeRequest;
use Webkul\MasterDataManagement\Models\QualitySnapshot;
use Webkul\MasterDataManagement\Quality\QualitySnapshotCalculator;
use Webkul\MasterDataManagement\Quality\StewardWorkload;
use Webkul\MasterDataManagement\Repositories\AccessGrantRepository;
use Webkul\MasterDataManagement\Repositories\EntityTypeRepository;
use Webkul\MasterDataManagement\Repositories\MatchCandidateRepository;
use Webkul\MasterDataManagement\Repositories\QualitySnapshotRepository;
use Webkul\MasterDataManagement\Repositories\RoleEntityPermissionRepository;
use Webkul\MasterDataManagement\Repositories\SourceSystemRepository;
use Webkul\MasterDataManagement\Security\AccessGrantResolver;
use Webkul\MasterDataManagement\Security\EntityTypePermissions;
use Webkul\MasterDataManagement\Services\RecordWriter;
use Webkul\User\Models\Admin;
use Webkul\User\Models\Role;

beforeEach(function () {
    Carbon::setTestNow(Carbon::parse('2026-11-06 12:00:00'));

    $types = app(EntityTypeRepository::class);
    $writer = app(RecordWriter::class);

    $this->steward = Admin::factory()->create(['name' => 'Steward Sam']);

    $this->type = $types->create(['code' => $this->uniqueCode('vendor'), 'status' => 'active', 'code_strategy' => 'source_id', 'data_owner_id' => $this->steward->id]);

    $this->records = collect(['V-1', 'V-2', 'V-3', 'V-4', 'V-5'])
        ->map(fn (string $code) => $writer->create($this->type, ['code' => $code], null))
        ->values();

    DB::table('mdm_dq_issues')->where('entity_type_id', $this->type->id)->delete();

    foreach ([100, 50, 80, 70, 10] as $index => $score) {
        DB::table('mdm_records')->where('id', $this->records[$index]->id)->update(['completeness' => $score]);
    }

    DB::table('mdm_records')->where('id', $this->records[4]->id)->update(['status' => 'retired']);

    $issue = fn (int $record, string $rule, string $status, ?int $related = null) => DB::table('mdm_dq_issues')->insert([
        'entity_type_id'    => $this->type->id,
        'record_id'         => $record,
        'related_record_id' => $related,
        'rule'              => $rule,
        'rule_key'          => $rule.'-'.$record,
        'severity'          => 'error',
        'status'            => $status,
        'detected_at'       => now(),
        'created_at'        => now(),
        'updated_at'        => now(),
    ]);

    $issue($this->records[0]->id, 'validity', 'open');
    $issue($this->records[1]->id, 'validity', 'resolved');
    $issue($this->records[2]->id, 'duplicate', 'open', $this->records[3]->id);

    $this->changeRequest = fn (int $typeId, ?int $recordId, string $status, string $createdAt) => tap(ChangeRequest::query()->create([
        'entity_type_id' => $typeId,
        'record_id'      => $recordId,
        'operation'      => 'update',
        'status'         => $status,
        'after'          => [],
        'input'          => [],
    ]))->forceFill(['created_at' => $createdAt])->saveQuietly();

    ($this->changeRequest)($this->type->id, $this->records[0]->id, 'pending', '2026-11-06 08:00:00');
    ($this->changeRequest)($this->type->id, $this->records[1]->id, 'approved', '2026-11-01 08:00:00');

    $this->system = app(SourceSystemRepository::class)->create(['code' => $this->uniqueCode('erp'), 'name' => 'ERP', 'type' => 'erp']);

    $this->candidate = fn (int $typeId, string $externalId, string $status, string $createdAt, ?int $matched = null) => tap(app(MatchCandidateRepository::class)->create([
        'source_system_id'  => $this->system->id,
        'entity_type_id'    => $typeId,
        'external_id'       => $externalId,
        'status'            => $status,
        'matched_record_id' => $matched,
    ]))->forceFill(['created_at' => $createdAt])->saveQuietly();

    ($this->candidate)($this->type->id, 'X-1', 'pending', '2026-10-01 08:00:00');
    ($this->candidate)($this->type->id, 'X-2', 'rejected', '2026-10-01 08:00:00');

    $this->snapshotOf = fn (int $typeId, string $date = '2026-11-06'): ?QualitySnapshot => QualitySnapshot::query()
        ->where('entity_type_id', $typeId)
        ->whereDate('snapshot_date', $date)
        ->first();
});

afterEach(function () {
    Carbon::setTestNow();
});

it('computes the daily quality aggregates of an entity type in SQL', function () {
    app(QualitySnapshotCalculator::class)->capture(now());

    $snapshot = ($this->snapshotOf)($this->type->id);

    expect($snapshot)->not->toBeNull()
        ->and($snapshot->record_count)->toBe(4)
        ->and($snapshot->completeness_avg)->toBe(75.0)
        ->and($snapshot->validity_rate)->toBe(75.0)
        ->and($snapshot->duplicate_rate)->toBe(50.0)
        ->and($snapshot->open_tasks)->toBe(4);
});

it('stores a type without live records without rates', function () {
    $empty = app(EntityTypeRepository::class)->create(['code' => $this->uniqueCode('empty'), 'status' => 'active', 'code_strategy' => 'source_id']);

    app(QualitySnapshotCalculator::class)->capture(now());

    $snapshot = ($this->snapshotOf)($empty->id);

    expect($snapshot->record_count)->toBe(0)
        ->and($snapshot->completeness_avg)->toBeNull()
        ->and($snapshot->validity_rate)->toBeNull()
        ->and($snapshot->duplicate_rate)->toBeNull()
        ->and($snapshot->open_tasks)->toBe(0);
});

it('keeps one snapshot per type and day when the capture runs again', function () {
    $calculator = app(QualitySnapshotCalculator::class);

    $calculator->capture(now());

    DB::table('mdm_dq_issues')->where('entity_type_id', $this->type->id)->where('rule', 'validity')->update(['status' => 'resolved']);

    $calculator->capture(now());

    expect(QualitySnapshot::query()->where('entity_type_id', $this->type->id)->count())->toBe(1)
        ->and(($this->snapshotOf)($this->type->id)->validity_rate)->toBe(100.0)
        ->and(($this->snapshotOf)($this->type->id)->open_tasks)->toBe(3);
});

it('captures the snapshots from the command, queued or in process', function () {
    Queue::fake([CaptureQualitySnapshots::class]);

    $this->artisan('masterdata:quality:snapshot')->assertSuccessful();

    Queue::assertPushed(CaptureQualitySnapshots::class);

    $this->artisan('masterdata:quality:snapshot', ['--now' => true])->assertSuccessful();

    expect(($this->snapshotOf)($this->type->id))->not->toBeNull();
});

it('schedules the snapshot capture daily on one server', function () {
    $this->app->forgetInstance(Schedule::class);

    $event = collect($this->app->make(Schedule::class)->events())
        ->first(fn ($event): bool => $event->description === CaptureQualitySnapshots::class);

    expect($event)->not->toBeNull()
        ->and($event->expression)->toBe('0 0 * * *')
        ->and($event->onOneServer)->toBeTrue()
        ->and($event->withoutOverlapping)->toBeTrue();
});

it('reads the latest snapshot with the previous one for the delta', function () {
    $repository = app(QualitySnapshotRepository::class);

    $calculator = app(QualitySnapshotCalculator::class);

    $calculator->capture(now()->subDays(2));

    DB::table('mdm_dq_issues')->where('entity_type_id', $this->type->id)->where('rule', 'validity')->update(['status' => 'resolved']);

    $calculator->capture(now());

    $pair = $repository->latestWithPrevious([$this->type->id])[$this->type->id];

    expect($pair['latest']['snapshot_date'])->toBe('2026-11-06')
        ->and($pair['latest']['validity_rate'])->toBe(100.0)
        ->and($pair['previous']['snapshot_date'])->toBe('2026-11-04')
        ->and($pair['previous']['validity_rate'])->toBe(75.0)
        ->and(array_column($repository->trend($this->type->id, now()->subDays(30)), 'snapshot_date'))->toBe(['2026-11-04', '2026-11-06']);
});

it('buckets the open steward tasks per data owner by age', function () {
    ($this->changeRequest)($this->type->id, $this->records[2]->id, 'pending', '2026-11-03 12:00:00');
    ($this->candidate)($this->type->id, 'X-3', 'pending', '2026-10-25 12:00:00');

    $unowned = app(EntityTypeRepository::class)->create(['code' => $this->uniqueCode('site'), 'status' => 'active', 'code_strategy' => 'source_id']);
    ($this->changeRequest)($unowned->id, null, 'pending', '2026-11-06 11:00:00');

    $rows = collect(app(StewardWorkload::class)->forAdmin(Admin::factory()->create(), [$this->type->id, $unowned->id], now()))
        ->keyBy(fn (array $row): string => (string) $row['steward_id']);

    expect($rows[(string) $this->steward->id])->toMatchArray([
        'steward_name'     => 'Steward Sam',
        'change_requests'  => 2,
        'match_candidates' => 2,
        'buckets'          => [
            WorkloadAgeBucket::Day->value   => 1,
            WorkloadAgeBucket::Week->value  => 1,
            WorkloadAgeBucket::Month->value => 1,
            WorkloadAgeBucket::Older->value => 1,
        ],
    ])->and($rows['']['change_requests'])->toBe(1)
        ->and($rows['']['buckets'][WorkloadAgeBucket::Day->value])->toBe(1);
});

it('renders the scorecards, trend chart and steward workload', function () {
    app(QualitySnapshotCalculator::class)->capture(now()->subDay());
    app(QualitySnapshotCalculator::class)->capture(now());

    $this->loginAsAdmin();

    $this->get(route('admin.masterdata.quality_dashboard.index', ['entity_type' => $this->type->id, 'days' => 30]))
        ->assertOk()
        ->assertSeeText(trans('masterdata::app.quality-dashboard.index.title'))
        ->assertSeeText(trans('masterdata::app.quality-dashboard.index.workload'))
        ->assertSeeText('Steward Sam')
        ->assertSee('var(--chart-1)', false)
        ->assertSee($this->type->code);
});

it('rejects an unknown trend range', function () {
    $this->loginAsAdmin();

    $this->get(route('admin.masterdata.quality_dashboard.index', ['days' => 11]))->assertSessionHasErrors('days');
});

it('denies the dashboard without its ACL key', function () {
    $this->loginWithPermissions('custom', ['dashboard', 'masterdata', 'masterdata.quality']);

    $this->get(route('admin.masterdata.quality_dashboard.index'))->assertForbidden();
});

it('limits the dashboard to the entity types and subtrees the admin may view', function () {
    $types = app(EntityTypeRepository::class);
    $writer = app(RecordWriter::class);

    $site = $types->create(['code' => $this->uniqueCode('site'), 'status' => 'active', 'code_strategy' => 'source_id', 'is_hierarchical' => true]);
    $equipment = $types->create([
        'code'                    => $this->uniqueCode('equipment'),
        'status'                  => 'active',
        'code_strategy'           => 'source_id',
        'is_hierarchical'         => true,
        'is_scoped'               => true,
        'allowed_parent_type_ids' => [$site->id],
        'data_owner_id'           => $this->steward->id,
    ]);
    $hidden = $types->create(['code' => $this->uniqueCode('hidden'), 'status' => 'active', 'code_strategy' => 'source_id']);

    $siteA = $writer->create($site, ['code' => 'SITE-A'], null);
    $siteB = $writer->create($site, ['code' => 'SITE-B'], null);
    $eqA = $writer->create($equipment, ['code' => 'EQ-A', 'parent' => $siteA->uuid], null);
    $eqB = $writer->create($equipment, ['code' => 'EQ-B', 'parent' => $siteB->uuid], null);

    ($this->changeRequest)($equipment->id, $eqA->id, 'pending', '2026-11-06 10:00:00');
    ($this->changeRequest)($equipment->id, $eqB->id, 'pending', '2026-11-06 10:00:00');

    app(QualitySnapshotCalculator::class)->capture(now());

    $role = Role::factory()->create(['permission_type' => 'custom', 'permissions' => ['dashboard', 'masterdata', 'masterdata.quality_dashboard']]);

    foreach ([$this->type, $site, $equipment] as $type) {
        app(RoleEntityPermissionRepository::class)->syncForEntityType($type->id, [$role->id => ['view']]);
    }

    $admin = Admin::factory()->create(['role_id' => $role->id]);

    app(AccessGrantRepository::class)->create([
        'principal_type' => GrantPrincipal::Role,
        'principal_id'   => $role->id,
        'entity_type_id' => null,
        'record_id'      => $siteA->id,
        'actions'        => ['view'],
    ]);

    app(EntityTypePermissions::class)->flush();
    app(AccessGrantResolver::class)->flush();

    $workload = collect(app(StewardWorkload::class)->forAdmin($admin, [$equipment->id], now()));

    expect($workload->sum('change_requests'))->toBe(1);

    $this->loginAsAdmin($admin);

    $this->get(route('admin.masterdata.quality_dashboard.index'))
        ->assertOk()
        ->assertSee($this->type->code)
        ->assertSee($site->code)
        ->assertDontSee($hidden->code)
        ->assertDontSee($equipment->code);
});
