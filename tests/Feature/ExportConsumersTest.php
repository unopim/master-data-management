<?php

use Illuminate\Support\Facades\DB;
use Webkul\DataTransfer\Models\JobTrack;
use Webkul\DataTransfer\Repositories\JobInstancesRepository;
use Webkul\MasterDataManagement\Export\ExportConsumers;
use Webkul\MasterDataManagement\Export\RecordExporter;
use Webkul\MasterDataManagement\Models\Record;
use Webkul\MasterDataManagement\Repositories\EntityTypeRepository;
use Webkul\MasterDataManagement\Services\RecordWriter;

beforeEach(function () {
    $this->type = app(EntityTypeRepository::class)->create(['code' => $this->uniqueCode('line')]);
    $this->record = app(RecordWriter::class)->create($this->type, ['label' => 'Filler Line North'], null);

    Record::query()->whereKey($this->record->id)->update(['updated_at' => now()->subHour()]);
    $this->record->refresh();

    $this->makeJob = function (string $suffix, array $filters) {
        return app(JobInstancesRepository::class)->create([
            'code'                => $this->type->code.'_'.$suffix,
            'entity_type'         => ExportConsumers::EXPORTER,
            'type'                => 'export',
            'action'              => 'export',
            'validation_strategy' => '',
            'filters'             => $filters + [
                RecordExporter::FILTER_ENTITY_TYPE            => $this->type->code,
                RecordExporter::FILTER_FORMAT                 => 'Jsonl',
                RecordExporter::FILTER_CHANGED_SINCE_LAST_RUN => '0',
            ],
        ]);
    };

    $this->run = fn ($job, $startedAt) => JobTrack::factory()->create([
        'state'            => 'completed',
        'type'             => 'export',
        'action'           => 'export',
        'job_instances_id' => $job->id,
        'started_at'       => $startedAt,
        'completed_at'     => $startedAt->copy()->addMinutes(2),
    ]);
});

it('lists the export jobs of the record type and status with the record state of their last run', function () {
    $this->freezeTime();

    $full = ($this->makeJob)('a_full', [RecordExporter::FILTER_STATUSES => [$this->record->status->value]]);
    $delta = ($this->makeJob)('b_delta', [RecordExporter::FILTER_CHANGED_SINCE_LAST_RUN => '1', RecordExporter::FILTER_FORMAT => 'Csv']);
    $stale = ($this->makeJob)('c_stale', []);
    $never = ($this->makeJob)('d_never', []);
    ($this->makeJob)('e_other_status', [RecordExporter::FILTER_STATUSES => ['retired']]);

    app(JobInstancesRepository::class)->create([
        'code'                => $this->type->code.'_f_other_type',
        'entity_type'         => ExportConsumers::EXPORTER,
        'type'                => 'export',
        'action'              => 'export',
        'validation_strategy' => '',
        'filters'             => [RecordExporter::FILTER_ENTITY_TYPE => 'another_type'],
    ]);

    ($this->run)($full, now()->subDays(2));
    ($this->run)($full, now()->subMinutes(10));
    ($this->run)($delta, now()->subMinutes(10));
    ($this->run)($stale, now()->subDays(2));

    DB::enableQueryLog();

    $consumers = app(ExportConsumers::class)->forRecord($this->record);

    $queries = count(DB::getQueryLog());

    DB::disableQueryLog();

    expect($queries)->toBeLessThanOrEqual(2)
        ->and(array_column($consumers, 'code'))->toBe([$full->code, $delta->code, $stale->code, $never->code])
        ->and(array_column($consumers, 'state', 'code'))->toBe([
            $full->code  => ExportConsumers::STATE_INCLUDED,
            $delta->code => ExportConsumers::STATE_UP_TO_DATE,
            $stale->code => ExportConsumers::STATE_PENDING,
            $never->code => ExportConsumers::STATE_NEVER_RUN,
        ])
        ->and(array_column($consumers, 'format', 'code')[$delta->code])->toBe('csv')
        ->and($consumers[0]['last_run_at']->toDateTimeString())->toBe(now()->subMinutes(8)->toDateTimeString())
        ->and($consumers[3]['last_run_at'])->toBeNull();
});

it('marks a record changed since the last run as pending for the next delta', function () {
    $delta = ($this->makeJob)('delta', [RecordExporter::FILTER_CHANGED_SINCE_LAST_RUN => '1']);

    ($this->run)($delta, now()->subMinutes(10));

    Record::query()->whereKey($this->record->id)->update(['updated_at' => now()]);

    $consumers = app(ExportConsumers::class)->forRecord($this->record->refresh());

    expect($consumers)->toHaveCount(1)
        ->and($consumers[0]['delta'])->toBeTrue()
        ->and($consumers[0]['state'])->toBe(ExportConsumers::STATE_PENDING);
});

it('shows the consuming jobs on the lineage tab, linked for admins who may edit exports', function () {
    $job = ($this->makeJob)('lineage', []);

    $this->loginAsAdmin();

    $this->get(route('admin.masterdata.records.edit', $this->record->uuid))
        ->assertOk()
        ->assertSeeText(trans('masterdata::app.lineage.consumers.title'))
        ->assertSeeText($job->code)
        ->assertSeeText(trans('masterdata::app.lineage.consumers.states.never_run'))
        ->assertSee(route('admin.settings.data_transfer.exports.edit', $job->id), false);
});
