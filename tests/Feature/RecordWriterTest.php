<?php

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use OwenIt\Auditing\Models\Audit;
use Webkul\MasterDataManagement\Contracts\ChangeGate;
use Webkul\MasterDataManagement\Enums\RecordStatus;
use Webkul\MasterDataManagement\Events\RecordCreated;
use Webkul\MasterDataManagement\Events\RecordRetired;
use Webkul\MasterDataManagement\Events\RecordUpdated;
use Webkul\MasterDataManagement\Exceptions\StaleRecordException;
use Webkul\MasterDataManagement\Models\EntityType;
use Webkul\MasterDataManagement\Models\Record;
use Webkul\MasterDataManagement\Repositories\EntityTypeRepository;
use Webkul\MasterDataManagement\Repositories\RecordRepository;
use Webkul\MasterDataManagement\Security\RecordScope;
use Webkul\MasterDataManagement\Services\FieldService;
use Webkul\MasterDataManagement\Services\RecordWriter;
use Webkul\MasterDataManagement\ValueObjects\ChangeSet;
use Webkul\MasterDataManagement\ValueObjects\GateResult;
use Webkul\MasterDataManagement\ValueObjects\SourceContext;
use Webkul\User\Models\Admin;

beforeEach(function () {
    $this->entityTypes = app(EntityTypeRepository::class);
    $this->writer = app(RecordWriter::class);

    $this->withFields = function (EntityType $type, array $fields): EntityType {
        foreach ($fields as $code => $fieldType) {
            app(FieldService::class)->create($type, ['code' => $code, 'type' => $fieldType]);
        }

        return $type;
    };
});

it('creates a record with generated code and label', function () {
    $type = ($this->withFields)($this->entityTypes->create(['code' => $this->uniqueCode('line'), 'label_field_code' => 'name', 'schema_version' => 3]), ['name' => 'text']);
    $admin = Admin::factory()->create();

    $record = $this->writer->create($type, ['values' => ['common' => ['name' => 'Line A']]], $admin);

    expect($record->exists)->toBeTrue()
        ->and($record->code)->toBe(strtoupper($type->code).'-000001')
        ->and($record->label)->toBe('Line A')
        ->and($record->uuid)->toBeString()->not->toBeEmpty()
        ->and($record->revision)->toBe(1)
        ->and($record->status)->toBe(RecordStatus::Active)
        ->and($record->schema_version)->toBe(3)
        ->and($record->path)->toBe('/'.$record->id.'/')
        ->and($record->depth)->toBe(0)
        ->and($record->created_by)->toBe($admin->id)
        ->and($record->values)->toBe(['common' => ['name' => 'Line A']]);

    expect($this->writer->create($type, ['label' => 'Second'], null)->code)->toBe(strtoupper($type->code).'-000002');
});

it('uses the explicit label when the type has no label field', function () {
    $type = $this->entityTypes->create(['code' => $this->uniqueCode('site')]);

    $record = $this->writer->create($type, ['label' => 'Main Plant'], null);

    expect($record->label)->toBe('Main Plant');
});

it('requires a caller code for identifier and source id strategies', function (string $strategy) {
    $type = $this->entityTypes->create(['code' => $this->uniqueCode('equipment'), 'code_strategy' => $strategy]);

    expect(fn () => $this->writer->create($type, ['label' => 'Pump'], null))
        ->toThrow(ValidationException::class);

    $record = $this->writer->create($type, ['code' => 'PUMP-7', 'label' => 'Pump'], null);

    expect($record->code)->toBe('PUMP-7');

    expect(fn () => $this->writer->create($type, ['code' => 'PUMP-7', 'label' => 'Again'], null))
        ->toThrow(ValidationException::class);
})->with(['identifier_fields', 'source_id']);

it('rejects a caller code for the pattern strategy', function () {
    $type = $this->entityTypes->create(['code' => $this->uniqueCode('area')]);

    expect(fn () => $this->writer->create($type, ['code' => 'MINE', 'label' => 'Area'], null))
        ->toThrow(ValidationException::class);
});

it('rejects non-array values', function (mixed $values) {
    $type = $this->entityTypes->create(['code' => $this->uniqueCode('line')]);

    expect(fn () => $this->writer->create($type, ['label' => 'Line', 'values' => $values], null))
        ->toThrow(ValidationException::class);

    expect(Record::query()->where('entity_type_id', $type->id)->exists())->toBeFalse();
})->with([
    'string'             => 'not-an-array',
    'unknown section'    => [['channel_specific' => ['x' => 1]]],
    'scalar common'      => [['common' => 'x']],
    'scalar locale part' => [['locale_specific' => ['en_US' => 'x']]],
]);

it('rejects a label longer than 255 characters', function () {
    $type = $this->entityTypes->create(['code' => $this->uniqueCode('line')]);

    expect(fn () => $this->writer->create($type, ['label' => str_repeat('a', 256)], null))
        ->toThrow(ValidationException::class);
});

it('stores locale-specific values under locale_specific', function () {
    $type = $this->entityTypes->create(['code' => $this->uniqueCode('line'), 'label_field_code' => 'name']);

    $record = $this->writer->create($type, [
        'values' => [
            'common'          => ['name' => 'Line A'],
            'locale_specific' => ['de_DE' => ['description' => 'Linie A']],
        ],
    ], null);

    $record = $this->writer->update($record, [
        'values' => ['locale_specific' => ['fr_FR' => ['description' => 'Ligne A']]],
    ], 1, null);

    expect($record->fresh()->values)->toBe([
        'common'          => ['name' => 'Line A'],
        'locale_specific' => [
            'de_DE' => ['description' => 'Linie A'],
            'fr_FR' => ['description' => 'Ligne A'],
        ],
    ]);
});

it('merges values and recomputes the label on update', function () {
    $type = ($this->withFields)($this->entityTypes->create(['code' => $this->uniqueCode('line'), 'label_field_code' => 'name']), ['name' => 'text', 'speed' => 'integer']);
    $admin = Admin::factory()->create();

    $record = $this->writer->create($type, ['values' => ['common' => ['name' => 'Line A', 'speed' => 10]]], null);

    $record = $this->writer->update($record, ['values' => ['common' => ['name' => 'Line B']]], 1, $admin);

    expect($record->label)->toBe('Line B')
        ->and($record->revision)->toBe(2)
        ->and($record->updated_by)->toBe($admin->id)
        ->and($record->fresh()->values)->toBe(['common' => ['name' => 'Line B', 'speed' => 10]]);
});

it('records history on update', function () {
    $type = ($this->withFields)($this->entityTypes->create(['code' => $this->uniqueCode('line'), 'label_field_code' => 'name']), ['name' => 'text']);

    $record = $this->writer->create($type, ['values' => ['common' => ['name' => 'Line A']]], null);

    $this->writer->update($record, ['values' => ['common' => ['name' => 'Line B']]], 1, null);

    $audit = Audit::query()
        ->where('auditable_type', $record->getMorphClass())
        ->where('auditable_id', $record->id)
        ->where('event', 'updated')
        ->latest('id')
        ->first();

    expect($audit)->not->toBeNull()
        ->and($audit->tags)->toBe('masterdata_record')
        ->and($audit->old_values['label'])->toBe('Line A')
        ->and($audit->new_values['label'])->toBe('Line B')
        ->and(json_decode($audit->old_values['values'], true))->toBe(['common' => ['name' => 'Line A']])
        ->and(json_decode($audit->new_values['values'], true))->toBe(['common' => ['name' => 'Line B']]);
});

it('dispatches created and updated events', function () {
    Event::fake([RecordCreated::class, RecordUpdated::class]);

    $type = $this->entityTypes->create(['code' => $this->uniqueCode('line')]);
    $source = new SourceContext('import', externalId: 'ERP-1');

    $record = $this->writer->create($type, ['label' => 'Line A'], null, $source);
    $this->writer->update($record, ['label' => 'Line B'], 1, null);

    Event::assertDispatched(RecordCreated::class, fn (RecordCreated $event): bool => $event->record->is($record)
        && $event->changeSet->source === $source);
    Event::assertDispatched(RecordUpdated::class, fn (RecordUpdated $event): bool => $event->record->is($record)
        && $event->changeSet->before['label'] === 'Line A'
        && $event->changeSet->after['label'] === 'Line B'
        && $event->changeSet->baseRevision === 1);
});

it('rejects an update with a stale revision', function () {
    $type = $this->entityTypes->create(['code' => $this->uniqueCode('line')]);

    $record = $this->writer->create($type, ['label' => 'Original'], null);
    $copy = Record::query()->findOrFail($record->id);

    $this->writer->update($record, ['label' => 'First writer'], 1, null);

    expect(fn () => $this->writer->update($copy, ['label' => 'Second writer'], 1, null))
        ->toThrow(StaleRecordException::class);

    $fresh = $record->fresh();

    expect($fresh->label)->toBe('First writer')
        ->and($fresh->revision)->toBe(2);
});

it('retires a record and dispatches the retired event', function () {
    Event::fake([RecordRetired::class]);

    $type = $this->entityTypes->create(['code' => $this->uniqueCode('line')]);
    $record = $this->writer->create($type, ['label' => 'Line A'], null);
    $copy = Record::query()->findOrFail($record->id);

    $record = $this->writer->retire($record, 1, null);

    expect($record->status)->toBe(RecordStatus::Retired)
        ->and($record->revision)->toBe(2);

    Event::assertDispatched(RecordRetired::class, fn (RecordRetired $event): bool => $event->record->is($record));

    expect(fn () => $this->writer->retire($copy, 1, null))->toThrow(StaleRecordException::class);
});

it('leaves the record untouched while the gate holds the change', function () {
    $type = $this->entityTypes->create(['code' => $this->uniqueCode('line')]);
    $existing = $this->writer->create($type, ['label' => 'Current'], null);

    app()->instance(ChangeGate::class, new class implements ChangeGate
    {
        public function submit(ChangeSet $set): GateResult
        {
            return GateResult::pending('cr-1');
        }
    });

    $writer = app(RecordWriter::class);
    $proposed = $writer->create($type, ['label' => 'Proposed'], null);
    $writer->update($existing, ['label' => 'Changed'], 1, null);
    $writer->retire($existing, 1, null);

    $fresh = $existing->fresh();

    expect($proposed->exists)->toBeFalse()
        ->and($proposed->label)->toBe('Proposed')
        ->and(Record::query()->where('entity_type_id', $type->id)->count())->toBe(1)
        ->and($fresh->label)->toBe('Current')
        ->and($fresh->status)->toBe(RecordStatus::Active)
        ->and($fresh->revision)->toBe(1);
});

it('finds a record by uuid and follows a merge one hop', function () {
    $type = $this->entityTypes->create(['code' => $this->uniqueCode('line')]);
    $survivor = $this->writer->create($type, ['label' => 'Survivor'], null);
    $loser = $this->writer->create($type, ['label' => 'Loser'], null);

    DB::table('mdm_records')->where('id', $loser->id)->update([
        'status'         => RecordStatus::Merged->value,
        'merged_into_id' => $survivor->id,
    ]);

    $repository = app(RecordRepository::class);

    expect($repository->findByUuid($survivor->uuid)?->id)->toBe($survivor->id)
        ->and($repository->findByUuid($loser->uuid)?->id)->toBe($survivor->id)
        ->and($repository->findByUuid('00000000-0000-0000-0000-000000000000'))->toBeNull();
});

it('applies the record scope on reads when an admin is present', function () {
    $type = $this->entityTypes->create(['code' => $this->uniqueCode('line')]);
    $record = $this->writer->create($type, ['label' => 'Scoped'], null);
    $admin = Admin::factory()->create();

    $scope = Mockery::mock(RecordScope::class);
    $scope->shouldReceive('apply')
        ->once()
        ->withArgs(fn (Builder $query, Admin $given, int $entityTypeId): bool => $given->is($admin) && $entityTypeId === $type->id)
        ->andReturnUsing(fn (Builder $query): Builder => $query->whereRaw('1 = 0'));

    app()->instance(RecordScope::class, $scope);

    expect(app(RecordRepository::class)->findByUuid($record->uuid, $admin))->toBeNull();
});

it('routes id lookups and listings through the record scope', function () {
    $type = $this->entityTypes->create(['code' => $this->uniqueCode('line')]);
    $record = $this->writer->create($type, ['label' => 'Scoped'], null);
    $admin = Admin::factory()->create();

    $scope = Mockery::mock(RecordScope::class);
    $scope->shouldReceive('apply')
        ->times(3)
        ->withArgs(fn (Builder $query, Admin $given, int $entityTypeId): bool => $given->is($admin) && $entityTypeId === $type->id)
        ->andReturnUsing(fn (Builder $query): Builder => $query->whereRaw('1 = 0'));

    app()->instance(RecordScope::class, $scope);
    $this->actingAs($admin, 'admin');

    $repository = app(RecordRepository::class);

    expect($repository->find($record->id))->toBeNull()
        ->and(fn () => $repository->findOrFail($record->id))->toThrow(ModelNotFoundException::class)
        ->and($repository->paginateForEntityType($type->id)->items())->toBe([]);
});

it('refuses unscoped repository reads', function (string $method, array $arguments) {
    expect(fn () => app(RecordRepository::class)->{$method}(...$arguments))->toThrow(LogicException::class);
})->with([
    'all'          => ['all', []],
    'paginate'     => ['paginate', []],
    'findWhere'    => ['findWhere', [['code' => 'X']]],
    'findByField'  => ['findByField', ['code', 'X']],
    'first'        => ['first', []],
    'count'        => ['count', []],
    'magic where'  => ['where', ['code', 'X']],
]);

it('refuses to change a retired, merged or erased record', function (RecordStatus $status) {
    $type = $this->entityTypes->create(['code' => $this->uniqueCode('line')]);
    $record = $this->writer->create($type, ['label' => 'Line A'], null);

    DB::table('mdm_records')->where('id', $record->id)->update(['status' => $status->value]);
    $record->refresh();

    expect(fn () => $this->writer->update($record, ['status' => 'active'], 1, null))
        ->toThrow(ValidationException::class, trans('masterdata::app.records.not-editable'))
        ->and(fn () => $this->writer->retire($record, 1, null))
        ->toThrow(ValidationException::class, trans('masterdata::app.records.not-editable'));

    expect($record->fresh()->status)->toBe($status)
        ->and($record->fresh()->revision)->toBe(1);
})->with([RecordStatus::Retired, RecordStatus::Merged, RecordStatus::Erased]);

it('reports a code taken by a concurrent create as a validation error', function () {
    $type = $this->entityTypes->create(['code' => $this->uniqueCode('equipment'), 'code_strategy' => 'source_id']);

    app()->instance(ChangeGate::class, new class implements ChangeGate
    {
        public function submit(ChangeSet $set): GateResult
        {
            DB::table('mdm_records')->insert([
                'uuid'           => (string) Str::uuid(),
                'entity_type_id' => $set->entityType->id,
                'code'           => $set->after['code'],
                'values'         => '[]',
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);

            return GateResult::apply();
        }
    });

    try {
        app(RecordWriter::class)->create($type, ['code' => 'PUMP-9', 'label' => 'Pump'], null);
        $this->fail('Expected a validation error on code.');
    } catch (ValidationException $exception) {
        expect($exception->errors())->toHaveKey('code');
    }

    expect(Record::query()->where('entity_type_id', $type->id)->count())->toBe(1);
});
