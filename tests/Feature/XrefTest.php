<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Webkul\MasterDataManagement\Enums\RecordStatus;
use Webkul\MasterDataManagement\Enums\XrefStatus;
use Webkul\MasterDataManagement\Exceptions\XrefConflictException;
use Webkul\MasterDataManagement\Models\Xref;
use Webkul\MasterDataManagement\Repositories\EntityTypeRepository;
use Webkul\MasterDataManagement\Repositories\RecordRepository;
use Webkul\MasterDataManagement\Repositories\SourceSystemRepository;
use Webkul\MasterDataManagement\Repositories\XrefRepository;
use Webkul\MasterDataManagement\Services\RecordWriter;
use Webkul\MasterDataManagement\Services\XrefService;

beforeEach(function () {
    $this->type = app(EntityTypeRepository::class)->create(['code' => $this->uniqueCode('line')]);
    $this->writer = app(RecordWriter::class);
    $this->xrefs = app(XrefService::class);
    $this->erp = app(SourceSystemRepository::class)->create(['code' => $this->uniqueCode('erp'), 'name' => 'ERP', 'type' => 'erp']);
    $this->mes = app(SourceSystemRepository::class)->create(['code' => $this->uniqueCode('mes'), 'name' => 'MES', 'type' => 'mes']);
});

it('attaches and resolves an external id', function () {
    $record = $this->writer->create($this->type, ['label' => 'Line A'], null);

    $xref = $this->xrefs->attach($record, $this->erp, 'L-100');

    expect($xref->entity_type_id)->toBe($this->type->id)
        ->and($xref->status)->toBe(XrefStatus::Active)
        ->and($xref->first_seen_at)->not->toBeNull()
        ->and($this->xrefs->resolve($this->erp, $this->type->id, 'L-100')?->id)->toBe($record->id)
        ->and($this->xrefs->resolve($this->mes, $this->type->id, 'L-100'))->toBeNull()
        ->and($this->xrefs->resolve($this->erp, $this->type->id, 'unknown'))->toBeNull();
});

it('re-attaching the same id to the same record is idempotent', function () {
    $record = $this->writer->create($this->type, ['label' => 'Line A'], null);

    $first = $this->xrefs->attach($record, $this->erp, 'L-100');
    Xref::query()->whereKey($first->id)->update(['last_seen_at' => now()->subDay()]);

    $second = $this->xrefs->attach($record, $this->erp, 'L-100');

    expect($second->id)->toBe($first->id)
        ->and(Xref::query()->where('entity_type_id', $this->type->id)->count())->toBe(1)
        ->and($second->last_seen_at->isAfter(now()->subMinute()))->toBeTrue();
});

it('rejects the same external id on two records', function () {
    $a = $this->writer->create($this->type, ['label' => 'Line A'], null);
    $b = $this->writer->create($this->type, ['label' => 'Line B'], null);

    $this->xrefs->attach($a, $this->erp, 'L-100');

    expect(fn () => $this->xrefs->attach($b, $this->erp, 'L-100'))->toThrow(XrefConflictException::class);
    expect($this->xrefs->resolve($this->erp, $this->type->id, 'L-100')->id)->toBe($a->id);
});

it('allows the same external id in different systems and entity types', function () {
    $a = $this->writer->create($this->type, ['label' => 'Line A'], null);
    $b = $this->writer->create($this->type, ['label' => 'Line B'], null);
    $otherType = app(EntityTypeRepository::class)->create(['code' => $this->uniqueCode('site')]);
    $site = $this->writer->create($otherType, ['label' => 'Site'], null);

    $this->xrefs->attach($a, $this->erp, 'X1');
    $this->xrefs->attach($b, $this->mes, 'X1');
    $this->xrefs->attach($site, $this->erp, 'X1');

    expect($this->xrefs->resolve($this->erp, $this->type->id, 'X1')->id)->toBe($a->id)
        ->and($this->xrefs->resolve($this->mes, $this->type->id, 'X1')->id)->toBe($b->id)
        ->and($this->xrefs->resolve($this->erp, $otherType->id, 'X1')->id)->toBe($site->id);
});

it('returns all active system ids for a record in a single query', function () {
    $record = $this->writer->create($this->type, ['label' => 'Line A'], null);
    $other = $this->writer->create($this->type, ['label' => 'Line B'], null);

    $this->xrefs->attach($record, $this->erp, 'L-100');
    $this->xrefs->attach($record, $this->erp, 'L-101');
    $this->xrefs->attach($record, $this->mes, 'M-7');
    $this->xrefs->attach($record, $this->mes, 'M-old');
    $this->xrefs->attach($other, $this->erp, 'L-999');
    Xref::query()->where('external_id', 'M-old')->update(['status' => 'retired']);

    DB::enableQueryLog();
    $ids = $this->xrefs->idsFor($record);
    $queries = DB::getQueryLog();
    DB::disableQueryLog();

    expect($ids)->toEqual([$this->erp->code => ['L-100', 'L-101'], $this->mes->code => ['M-7']])
        ->and($queries)->toHaveCount(1)
        ->and($this->xrefs->idsFor($this->writer->create($this->type, ['label' => 'C'], null)))->toBe([]);
});

it('follows a merged record to its survivor when resolving', function () {
    $loser = $this->writer->create($this->type, ['label' => 'Dup'], null);
    $survivor = $this->writer->create($this->type, ['label' => 'Golden'], null);

    $this->xrefs->attach($loser, $this->erp, 'DUP-1');
    $loser->forceFill(['status' => RecordStatus::Merged, 'merged_into_id' => $survivor->id])->save();

    expect($this->xrefs->resolve($this->erp, $this->type->id, 'DUP-1')->id)->toBe($survivor->id);
});

it('does not resolve retired crosswalk entries and lets another record claim them', function () {
    $a = $this->writer->create($this->type, ['label' => 'Line A'], null);
    $b = $this->writer->create($this->type, ['label' => 'Line B'], null);

    $this->xrefs->attach($a, $this->erp, 'L-100');
    Xref::query()->where('entity_type_id', $this->type->id)->update(['status' => 'retired']);

    expect($this->xrefs->resolve($this->erp, $this->type->id, 'L-100'))->toBeNull();

    $this->xrefs->attach($b, $this->erp, 'L-100');

    expect($this->xrefs->resolve($this->erp, $this->type->id, 'L-100')->id)->toBe($b->id)
        ->and(Xref::query()->where('entity_type_id', $this->type->id)->count())->toBe(1);
});

it('treats external ids as exact, case-sensitive values', function () {
    $a = $this->writer->create($this->type, ['label' => 'A'], null);
    $b = $this->writer->create($this->type, ['label' => 'B'], null);

    $this->xrefs->attach($a, $this->erp, 'A1');
    $this->xrefs->attach($b, $this->erp, 'a1');

    expect($this->xrefs->resolve($this->erp, $this->type->id, 'A1')->id)->toBe($a->id)
        ->and($this->xrefs->resolve($this->erp, $this->type->id, 'a1')->id)->toBe($b->id)
        ->and(Xref::query()->where('entity_type_id', $this->type->id)->count())->toBe(2);
});

it('trims external ids on attach and resolve', function () {
    $record = $this->writer->create($this->type, ['label' => 'A'], null);

    $xref = $this->xrefs->attach($record, $this->erp, ' A1 ');

    expect($xref->external_id)->toBe('A1')
        ->and($this->xrefs->resolve($this->erp, $this->type->id, ' A1 ')->id)->toBe($record->id);
});

it('rejects empty and over-long external ids', function (string $id) {
    $record = $this->writer->create($this->type, ['label' => 'A'], null);

    expect(fn () => $this->xrefs->attach($record, $this->erp, $id))->toThrow(ValidationException::class);
})->with(['empty' => '', 'blank' => '   ', 'too long' => str_repeat('x', 256)]);

it('recovers from a lost insert race inside an outer transaction', function () {
    $winner = $this->writer->create($this->type, ['label' => 'Winner'], null);
    $loser = $this->writer->create($this->type, ['label' => 'Loser'], null);

    DB::table('mdm_xrefs')->insert([
        'record_id'        => $winner->id,
        'entity_type_id'   => $this->type->id,
        'source_system_id' => $this->erp->id,
        'external_id'      => 'RACE-1',
        'status'           => 'active',
    ]);

    $repository = new class(app()) extends XrefRepository
    {
        public int $lookups = 0;

        public function findByExternalId(int $sourceSystemId, int $entityTypeId, string $externalId): ?Xref
        {
            return $this->lookups++ === 0 ? null : parent::findByExternalId($sourceSystemId, $entityTypeId, $externalId);
        }
    };

    $service = new XrefService($repository, app(RecordRepository::class), app(SourceSystemRepository::class));

    DB::transaction(function () use ($service, $loser, $winner) {
        expect(fn () => $service->attach($loser, $this->erp, 'RACE-1'))->toThrow(XrefConflictException::class)
            ->and(DB::table('mdm_xrefs')->where('record_id', $winner->id)->count())->toBe(1);
    });
});
