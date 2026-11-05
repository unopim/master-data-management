<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use OwenIt\Auditing\Models\Audit;
use Webkul\MasterDataManagement\Enums\RecordStatus;
use Webkul\MasterDataManagement\Events\RecordErased;
use Webkul\MasterDataManagement\Exceptions\StaleRecordException;
use Webkul\MasterDataManagement\Models\Record;
use Webkul\MasterDataManagement\Repositories\EntityTypeRepository;
use Webkul\MasterDataManagement\Repositories\SourceSystemRepository;
use Webkul\MasterDataManagement\Services\FieldService;
use Webkul\MasterDataManagement\Services\RecordEraser;
use Webkul\MasterDataManagement\Services\RecordMerger;
use Webkul\MasterDataManagement\Services\RecordWriter;
use Webkul\MasterDataManagement\Services\XrefService;
use Webkul\User\Models\Admin;

beforeEach(function () {
    $this->writer = app(RecordWriter::class);
    $this->eraser = app(RecordEraser::class);

    $this->type = app(EntityTypeRepository::class)->create(['code' => $this->uniqueCode('employee'), 'code_strategy' => 'source_id']);
    app(FieldService::class)->create($this->type, ['code' => 'email', 'type' => 'text']);

    $this->retired = function (string $code, string $email): Record {
        $record = $this->writer->create($this->type, ['code' => $code, 'label' => 'Jane '.$code, 'values' => ['common' => ['email' => $email]]], null);

        return $this->writer->retire($record, $record->revision, null);
    };

    $this->auditsOf = fn (Record $record): int => Audit::query()
        ->where('auditable_type', $record->getMorphClass())
        ->where('auditable_id', $record->id)
        ->count();
});

it('wipes the personal data of a retired record and keeps it as an erased tombstone', function () {
    Event::fake([RecordErased::class]);

    $admin = Admin::factory()->create();
    $record = ($this->retired)('EMP-1', 'jane@example.com');

    expect(($this->auditsOf)($record))->toBeGreaterThan(0);

    $erased = $this->eraser->erase($record, $record->revision, $admin);

    expect($erased->status)->toBe(RecordStatus::Erased)
        ->and($erased->values)->toBeNull()
        ->and($erased->label)->toBeNull()
        ->and($erased->code)->toBe('erased-'.$record->uuid)
        ->and($erased->uuid)->toBe($record->uuid)
        ->and($erased->updated_by)->toBe($admin->id)
        ->and(($this->auditsOf)($erased))->toBe(0);

    Event::assertDispatched(RecordErased::class, fn (RecordErased $event): bool => $event->record->is($erased) && $event->erasedIds === [$erased->id]);
});

it('scrubs every copy of the values kept beside the record', function () {
    $record = ($this->retired)('EMP-2', 'john@example.com');
    $other = $this->writer->create($this->type, ['code' => 'EMP-3', 'values' => ['common' => ['email' => 'keep@example.com']]], null);

    DB::table('mdm_record_index_values')->insert([
        ['record_id' => $record->id, 'entity_type_id' => $this->type->id, 'field_code' => 'email', 'value_normalized' => 'john@example.com'],
        ['record_id' => $other->id, 'entity_type_id' => $this->type->id, 'field_code' => 'email', 'value_normalized' => 'keep@example.com'],
    ]);

    $requestId = DB::table('mdm_change_requests')->insertGetId([
        'entity_type_id' => $this->type->id,
        'record_id'      => $record->id,
        'operation'      => 'update',
        'status'         => 'approved',
        'before'         => json_encode(['values' => ['common' => ['email' => 'john@example.com']]]),
        'after'          => json_encode(['values' => ['common' => ['email' => 'john@new.example']]]),
        'input'          => json_encode(['values' => ['common' => ['email' => 'john@new.example']]]),
        'comment'        => 'john@example.com asked for it',
        'created_at'     => now(),
        'updated_at'     => now(),
    ]);

    DB::table('mdm_dq_issues')->insert([
        'entity_type_id' => $this->type->id,
        'record_id'      => $record->id,
        'rule'           => 'duplicate',
        'rule_key'       => 'email',
        'severity'       => 'warning',
        'details'        => json_encode(['value' => 'john@example.com']),
        'created_at'     => now(),
        'updated_at'     => now(),
    ]);

    $system = app(SourceSystemRepository::class)->create(['code' => $this->uniqueCode('hr'), 'name' => 'HR', 'type' => 'other']);
    app(XrefService::class)->attach($record->fresh(), $system, 'HR-9');

    $candidateId = DB::table('mdm_match_candidates')->insertGetId([
        'source_system_id'  => $system->id,
        'entity_type_id'    => $this->type->id,
        'external_id'       => 'HR-10',
        'label'             => 'John',
        'payload'           => json_encode(['email' => 'john@example.com']),
        'status'            => 'accepted',
        'matched_record_id' => $record->id,
        'created_at'        => now(),
        'updated_at'        => now(),
    ]);

    $record->refresh();
    $this->eraser->erase($record, $record->revision, null);

    $request = DB::table('mdm_change_requests')->find($requestId);
    $candidate = DB::table('mdm_match_candidates')->find($candidateId);

    expect(DB::table('mdm_record_index_values')->where('record_id', $record->id)->exists())->toBeFalse()
        ->and(DB::table('mdm_record_index_values')->where('record_id', $other->id)->exists())->toBeTrue()
        ->and(json_decode($request->before, true))->toBe([])
        ->and(json_decode($request->after, true))->toBe([])
        ->and(json_decode($request->input, true))->toBe([])
        ->and($request->comment)->toBeNull()
        ->and(DB::table('mdm_dq_issues')->where('record_id', $record->id)->exists())->toBeFalse()
        ->and($candidate->label)->toBeNull()
        ->and($candidate->payload)->toBeNull()
        ->and(DB::table('mdm_xrefs')->where('record_id', $record->id)->value('external_id'))->toBe('HR-9')
        ->and(Audit::query()->where('tags', 'masterdata_record')->where('history_id', $record->id)->exists())->toBeFalse()
        ->and(($this->auditsOf)($other))->toBeGreaterThan(0);
});

it('erases the duplicates merged into the record with it', function () {
    $survivor = $this->writer->create($this->type, ['code' => 'EMP-4', 'values' => ['common' => ['email' => 'a@example.com']]], null);
    $duplicate = $this->writer->create($this->type, ['code' => 'EMP-5', 'values' => ['common' => ['email' => 'a@example.org']]], null);

    app(RecordMerger::class)->merge($survivor, $duplicate, [], $survivor->revision, $duplicate->revision, null);

    $survivor->refresh();
    $survivor = $this->writer->retire($survivor, $survivor->revision, null);

    $this->eraser->erase($survivor, $survivor->revision, null);

    $duplicate->refresh();
    $merge = DB::table('mdm_record_merges')->where('survivor_id', $survivor->id)->first();

    expect($duplicate->status)->toBe(RecordStatus::Erased)
        ->and($duplicate->values)->toBeNull()
        ->and($duplicate->code)->toBe('erased-'.$duplicate->uuid)
        ->and(($this->auditsOf)($duplicate))->toBe(0)
        ->and(json_decode($merge->survivor_before, true))->toBe([])
        ->and(json_decode($merge->merged_before, true))->toBe([])
        ->and(json_decode($merge->result, true))->toBe([]);
});

it('refuses to erase a record that is not retired', function (RecordStatus $status) {
    $record = $this->writer->create($this->type, ['code' => 'EMP-6'], null);
    $record->forceFill(['status' => $status])->saveQuietly();

    expect(fn () => $this->eraser->erase($record, $record->revision, null))->toThrow(ValidationException::class);
})->with([RecordStatus::Draft, RecordStatus::Active, RecordStatus::Merged, RecordStatus::Erased]);

it('refuses to erase at a stale revision', function () {
    $record = ($this->retired)('EMP-7', 'stale@example.com');

    expect(fn () => $this->eraser->erase($record, $record->revision - 1, null))->toThrow(StaleRecordException::class)
        ->and($record->fresh()->values)->not->toBeNull();
});
