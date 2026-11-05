<?php

use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use Webkul\MasterDataManagement\Contracts\ChangeGate;
use Webkul\MasterDataManagement\Enums\RecordStatus;
use Webkul\MasterDataManagement\Enums\XrefStatus;
use Webkul\MasterDataManagement\Events\RecordMerged;
use Webkul\MasterDataManagement\Events\RecordUnmerged;
use Webkul\MasterDataManagement\Exceptions\StaleRecordException;
use Webkul\MasterDataManagement\Models\RecordMerge;
use Webkul\MasterDataManagement\Models\Xref;
use Webkul\MasterDataManagement\Repositories\EntityTypeRepository;
use Webkul\MasterDataManagement\Repositories\SourceSystemRepository;
use Webkul\MasterDataManagement\Services\FieldService;
use Webkul\MasterDataManagement\Services\RecordMerger;
use Webkul\MasterDataManagement\Services\RecordWriter;
use Webkul\MasterDataManagement\Services\XrefService;
use Webkul\MasterDataManagement\ValueObjects\ChangeSet;
use Webkul\MasterDataManagement\ValueObjects\GateResult;

beforeEach(function () {
    $this->type = app(EntityTypeRepository::class)->create(['code' => $this->uniqueCode('supplier'), 'label_field_code' => 'name', 'is_hierarchical' => true]);

    foreach (['name' => 'text', 'city' => 'text', 'phone' => 'text'] as $code => $fieldType) {
        app(FieldService::class)->create($this->type, ['code' => $code, 'type' => $fieldType]);
    }

    $this->type = $this->type->fresh();
    $this->writer = app(RecordWriter::class);
    $this->merger = app(RecordMerger::class);
    $this->xrefs = app(XrefService::class);
    $this->erp = app(SourceSystemRepository::class)->create(['code' => $this->uniqueCode('erp'), 'name' => 'ERP', 'type' => 'erp']);

    $this->record = fn (array $common, array $extra = []) => $this->writer->create($this->type, ['values' => ['common' => $common]] + $extra, null);
});

it('folds the merged record into the survivor and keeps the most complete values', function () {
    Event::fake([RecordMerged::class]);

    $survivor = ($this->record)(['name' => 'Acme Steel', 'city' => null]);
    $duplicate = ($this->record)(['name' => 'ACME Steel Ltd', 'city' => 'Pune', 'phone' => '020-1234']);

    $this->xrefs->attach($duplicate, $this->erp, 'S-77');

    $this->merger->merge($survivor, $duplicate, [], $survivor->revision, $duplicate->revision, null);

    $survivor->refresh();
    $duplicate->refresh();

    expect($survivor->values['common'])->toEqual(['name' => 'Acme Steel', 'city' => 'Pune', 'phone' => '020-1234'])
        ->and($survivor->label)->toBe('Acme Steel')
        ->and($duplicate->status)->toBe(RecordStatus::Merged)
        ->and($duplicate->merged_into_id)->toBe($survivor->id)
        ->and($this->xrefs->resolve($this->erp, $this->type->id, 'S-77')?->id)->toBe($survivor->id)
        ->and(Xref::query()->where('external_id', 'S-77')->value('record_id'))->toBe($survivor->id)
        ->and(RecordMerge::query()->where('merged_id', $duplicate->id)->whereNull('unmerged_at')->exists())->toBeTrue();

    Event::assertDispatched(RecordMerged::class, fn (RecordMerged $event): bool => $event->survivor->is($survivor) && $event->merged->is($duplicate));
});

it('takes a field from the merged record when the steward chooses it', function () {
    $survivor = ($this->record)(['name' => 'Acme Steel', 'city' => 'Mumbai']);
    $duplicate = ($this->record)(['name' => 'Acme Steel Pvt', 'city' => 'Pune']);

    $this->merger->merge($survivor, $duplicate, ['name' => 'merged', 'city' => 'survivor', 'unknown' => 'other'], $survivor->revision, $duplicate->revision, null);

    expect($survivor->fresh()->values['common'])->toEqual(['name' => 'Acme Steel Pvt', 'city' => 'Mumbai'])
        ->and($survivor->fresh()->label)->toBe('Acme Steel Pvt');
});

it('previews each field with both sides and the suggested winner', function () {
    $survivor = ($this->record)(['name' => 'Acme', 'city' => null]);
    $duplicate = ($this->record)(['name' => 'Acme Ltd', 'city' => 'Pune']);

    $preview = $this->merger->preview($survivor, $duplicate);

    expect($preview['name'])->toBe(['survivor' => ['common' => 'Acme'], 'merged' => ['common' => 'Acme Ltd'], 'suggested' => 'survivor'])
        ->and($preview['city']['suggested'])->toBe('merged');
});

it('moves the merged record children to the survivor and back on unmerge', function () {
    $survivor = ($this->record)(['name' => 'Plant A']);
    $duplicate = ($this->record)(['name' => 'Plant A2']);
    $child = $this->writer->create($this->type, ['values' => ['common' => ['name' => 'Line 1']], 'parent' => $duplicate->uuid], null);

    $this->merger->merge($survivor, $duplicate, [], $survivor->revision, $duplicate->revision, null);

    expect($child->fresh()->parent_id)->toBe($survivor->id);

    $this->merger->unmerge($duplicate->fresh(), null);

    expect($child->fresh()->parent_id)->toBe($duplicate->id);
});

it('undoes a merge but keeps changes made on the survivor since', function () {
    Event::fake([RecordUnmerged::class]);

    $survivor = ($this->record)(['name' => 'Acme', 'city' => null]);
    $duplicate = ($this->record)(['name' => 'Acme Ltd', 'city' => 'Pune', 'phone' => '020']);

    $this->xrefs->attach($duplicate, $this->erp, 'S-1');

    $this->merger->merge($survivor, $duplicate, [], $survivor->revision, $duplicate->revision, null);

    $survivor->refresh();

    $this->writer->update($survivor, ['values' => ['common' => ['phone' => '021']]], $survivor->revision, null);

    $this->merger->unmerge($duplicate->fresh(), null);

    $survivor->refresh();
    $duplicate->refresh();

    expect($survivor->values['common'])->toEqual(['name' => 'Acme', 'city' => null, 'phone' => '021'])
        ->and($duplicate->status)->toBe(RecordStatus::Active)
        ->and($duplicate->merged_into_id)->toBeNull()
        ->and($this->xrefs->resolve($this->erp, $this->type->id, 'S-1')?->id)->toBe($duplicate->id)
        ->and(RecordMerge::query()->where('merged_id', $duplicate->id)->value('unmerged_at'))->not->toBeNull();

    Event::assertDispatched(RecordUnmerged::class);
});

it('repoints records merged into the merged record and restores them on unmerge', function () {
    $survivor = ($this->record)(['name' => 'A']);
    $middle = ($this->record)(['name' => 'B']);
    $oldest = ($this->record)(['name' => 'C']);

    $this->merger->merge($middle, $oldest, [], $middle->revision, $oldest->revision, null);
    $middle->refresh();
    $this->merger->merge($survivor, $middle, [], $survivor->revision, $middle->revision, null);

    expect($oldest->fresh()->merged_into_id)->toBe($survivor->id);

    $this->merger->unmerge($middle->fresh(), null);

    expect($oldest->fresh()->merged_into_id)->toBe($middle->id);
});

it('refuses merges that cannot be applied', function (string $case) {
    $survivor = ($this->record)(['name' => 'A']);
    $merged = match ($case) {
        'same'      => $survivor,
        'type'      => $this->writer->create(app(EntityTypeRepository::class)->create(['code' => $this->uniqueCode('other')]), ['label' => 'X'], null),
        'retired'   => tap(($this->record)(['name' => 'B']), fn ($record) => $this->writer->retire($record, $record->revision, null))->fresh(),
        'ancestor'  => tap(($this->record)(['name' => 'Parent']), function ($parent) use (&$survivor): void {
            $survivor = $this->writer->create($this->type, ['values' => ['common' => ['name' => 'Kid']], 'parent' => $parent->uuid], null);
        }),
    };

    expect(fn () => $this->merger->merge($survivor, $merged, [], $survivor->revision, $merged->revision, null))
        ->toThrow(ValidationException::class);
})->with(['same', 'type', 'retired', 'ancestor']);

it('refuses a merge at a stale revision and changes nothing', function () {
    $survivor = ($this->record)(['name' => 'A', 'city' => null]);
    $duplicate = ($this->record)(['name' => 'B', 'city' => 'Pune']);

    expect(fn () => $this->merger->merge($survivor, $duplicate, [], $survivor->revision, $duplicate->revision + 5, null))
        ->toThrow(StaleRecordException::class);

    expect($survivor->fresh()->values['common']['city'])->toBeNull()
        ->and($duplicate->fresh()->status)->toBe(RecordStatus::Active)
        ->and(RecordMerge::query()->count())->toBe(0);
});

it('refuses to unmerge a record that is not merged', function () {
    $record = ($this->record)(['name' => 'A']);

    expect(fn () => $this->merger->unmerge($record, null))->toThrow(ValidationException::class);
});

it('leaves both records untouched while the gate holds the merge', function () {
    $survivor = ($this->record)(['name' => 'A', 'city' => null]);
    $duplicate = ($this->record)(['name' => 'B', 'city' => 'Pune']);

    app()->instance(ChangeGate::class, new class implements ChangeGate
    {
        public function submit(ChangeSet $set): GateResult
        {
            return GateResult::pending('cr-1');
        }
    });

    $result = app(RecordMerger::class)->merge($survivor, $duplicate, [], $survivor->revision, $duplicate->revision, null);

    expect($result->isPending())->toBeTrue()
        ->and($duplicate->fresh()->status)->toBe(RecordStatus::Active)
        ->and($survivor->fresh()->values['common']['city'])->toBeNull();
});

it('keeps unrelated xrefs of the survivor and only moves active ones', function () {
    $survivor = ($this->record)(['name' => 'A']);
    $duplicate = ($this->record)(['name' => 'B']);

    $this->xrefs->attach($survivor, $this->erp, 'KEEP');
    $retired = $this->xrefs->attach($duplicate, $this->erp, 'OLD');
    $this->xrefs->detach($duplicate, $retired->id);

    $this->merger->merge($survivor, $duplicate, [], $survivor->revision, $duplicate->revision, null);

    expect(Xref::query()->where('external_id', 'OLD')->value('record_id'))->toBe($duplicate->id)
        ->and(Xref::query()->where('external_id', 'OLD')->first()->status)->toBe(XrefStatus::Retired)
        ->and(Xref::query()->where('external_id', 'KEEP')->value('record_id'))->toBe($survivor->id);
});
