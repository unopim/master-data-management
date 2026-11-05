<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Webkul\DataTransfer\Helpers\Import;
use Webkul\DataTransfer\Jobs\Import\ImportTrackBatch;
use Webkul\DataTransfer\Models\JobInstances;
use Webkul\DataTransfer\Models\JobTrack;
use Webkul\MasterDataManagement\Jobs\MatchRecordOnWrite;
use Webkul\MasterDataManagement\Models\Record;
use Webkul\MasterDataManagement\Repositories\EntityTypeRepository;
use Webkul\MasterDataManagement\Services\FieldService;
use Webkul\User\Models\Admin;

beforeEach(function () {
    Storage::fake('local');
    Storage::fake('private');

    config(['masterdata.scheduled_imports.disk' => 'local', 'masterdata.scheduled_imports.path' => 'mdm-inbox', 'masterdata.scheduled_imports.user' => null]);

    $this->type = app(EntityTypeRepository::class)->create(['code' => $this->uniqueCode('equipment'), 'status' => 'active', 'code_strategy' => 'source_id']);
    app(FieldService::class)->create($this->type, ['code' => 'speed', 'type' => 'integer']);

    $this->admin = Admin::factory()->create();

    $this->job = JobInstances::query()->create([
        'code'                => $this->uniqueCode('maximo_equipment'),
        'entity_type'         => 'masterdata_records',
        'type'                => 'import',
        'action'              => Import::ACTION_APPEND,
        'validation_strategy' => Import::VALIDATION_STRATEGY_SKIP_ERRORS,
        'allowed_errors'      => 0,
        'field_separator'     => ',',
        'filters'             => ['entity_type' => $this->type->code],
    ]);

    $this->inbox = 'mdm-inbox/'.$this->job->code;
});

it('imports the oldest waiting file as the configured admin and moves it to processed', function () {
    Storage::disk('local')->put($this->inbox.'/b.csv', "code,speed\nE-2,20\n");
    touch(Storage::disk('local')->path($this->inbox.'/b.csv'), time());
    Storage::disk('local')->put($this->inbox.'/a.csv', "code,speed\nE-1,10\n");
    touch(Storage::disk('local')->path($this->inbox.'/a.csv'), time() - 60);
    Storage::disk('local')->put($this->inbox.'/notes.txt', 'ignored');

    config(['masterdata.scheduled_imports.user' => $this->admin->email]);

    $this->artisan('masterdata:import', ['job_code' => $this->job->code, '--sync' => true])->assertSuccessful();

    $track = JobTrack::query()->where('job_instances_id', $this->job->id)->sole();

    expect($track->state)->toBe(Import::STATE_COMPLETED)
        ->and($track->user_id)->toBe($this->admin->id)
        ->and(Storage::disk('private')->exists($track->file_path))->toBeTrue()
        ->and($this->job->fresh()->file_path)->toBe($track->file_path)
        ->and(Record::query()->where('entity_type_id', $this->type->id)->pluck('code')->all())->toBe(['E-1'])
        ->and(Storage::disk('local')->exists($this->inbox.'/a.csv'))->toBeFalse()
        ->and(Storage::disk('local')->files($this->inbox.'/processed'))->toHaveCount(1)
        ->and(Storage::disk('local')->exists($this->inbox.'/b.csv'))->toBeTrue();
});

it('queues the job on a named file for the admin given by option', function () {
    Queue::fake();

    Storage::disk('local')->put('drop/equipment.csv', "code,speed\nE-1,10\n");

    $this->artisan('masterdata:import', ['job_code' => $this->job->code, '--file' => 'drop/equipment.csv', '--user' => $this->admin->email])->assertSuccessful();

    Queue::assertPushed(ImportTrackBatch::class);

    expect(JobTrack::query()->where('job_instances_id', $this->job->id)->sole()->state)->toBe(Import::STATE_PENDING)
        ->and(Storage::disk('local')->exists('drop/equipment.csv'))->toBeFalse()
        ->and(Storage::disk('local')->files('drop/processed'))->toHaveCount(1);
});

it('does nothing when no file is waiting', function () {
    Queue::fake();

    $this->artisan('masterdata:import', ['job_code' => $this->job->code, '--user' => $this->admin->email])
        ->expectsOutputToContain($this->inbox)
        ->assertSuccessful();

    Queue::assertNothingPushed();
});

it('refuses an unknown job, a job of another importer, a missing admin and a missing file', function () {
    $products = JobInstances::query()->create([
        'code'                => $this->uniqueCode('products'), 'entity_type' => 'products', 'type' => 'import', 'action' => 'append',
        'validation_strategy' => Import::VALIDATION_STRATEGY_SKIP_ERRORS, 'allowed_errors' => 0, 'field_separator' => ',',
    ]);

    $this->artisan('masterdata:import', ['job_code' => 'zzz_none', '--user' => $this->admin->email])->assertFailed();
    $this->artisan('masterdata:import', ['job_code' => $products->code, '--user' => $this->admin->email])->assertFailed();
    $this->artisan('masterdata:import', ['job_code' => $this->job->code])->assertFailed();
    $this->artisan('masterdata:import', ['job_code' => $this->job->code, '--user' => 'nobody@example.com'])->assertFailed();
    $this->artisan('masterdata:import', ['job_code' => $this->job->code, '--user' => $this->admin->email, '--file' => 'nope.csv'])->assertFailed();

    expect(JobTrack::query()->where('job_instances_id', $this->job->id)->exists())->toBeFalse();
});

it('schedules each configured job on its cron expression', function () {
    config(['masterdata.scheduled_imports.jobs' => [$this->job->code => '*/15 * * * *']]);

    $this->app->forgetInstance(Schedule::class);

    $event = collect($this->app->make(Schedule::class)->events())
        ->first(fn ($event): bool => str_contains((string) $event->command, 'masterdata:import') && str_contains((string) $event->command, $this->job->code));

    expect($event)->not->toBeNull()
        ->and($event->expression)->toBe('*/15 * * * *')
        ->and($event->withoutOverlapping)->toBeTrue();
});

it('does not queue a duplicate check per imported row', function () {
    Queue::fake([MatchRecordOnWrite::class]);

    Storage::disk('local')->put($this->inbox.'/a.csv', "code,speed\nE-1,10\nE-2,20\n");

    $this->artisan('masterdata:import', ['job_code' => $this->job->code, '--user' => $this->admin->email, '--sync' => true])->assertSuccessful();

    expect(Record::query()->where('entity_type_id', $this->type->id)->count())->toBe(2);

    Queue::assertNotPushed(MatchRecordOnWrite::class);
});
