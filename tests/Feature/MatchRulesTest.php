<?php

use Illuminate\Support\Facades\Queue;
use Webkul\MasterDataManagement\Enums\QualityIssueStatus;
use Webkul\MasterDataManagement\Jobs\MatchRecordOnWrite;
use Webkul\MasterDataManagement\Matching\MatchSuggester;
use Webkul\MasterDataManagement\Matching\MatchSuggestion;
use Webkul\MasterDataManagement\Models\QualityIssue;
use Webkul\MasterDataManagement\Quality\QualityEvaluator;
use Webkul\MasterDataManagement\Quality\Rules\DuplicateRule;
use Webkul\MasterDataManagement\Repositories\EntityTypeRepository;
use Webkul\MasterDataManagement\Repositories\MatchRuleRepository;
use Webkul\MasterDataManagement\Repositories\RecordRepository;
use Webkul\MasterDataManagement\Services\FieldService;
use Webkul\MasterDataManagement\Services\RecordWriter;
use Webkul\MasterDataManagement\ValueObjects\SourceContext;

beforeEach(function () {
    $this->writer = app(RecordWriter::class);
    $this->suggester = app(MatchSuggester::class);
    $this->rules = app(MatchRuleRepository::class);

    $this->makeType = fn (array $attributes = []) => app(EntityTypeRepository::class)->create($attributes + ['code' => $this->uniqueCode('supplier'), 'code_strategy' => 'source_id']);

    $this->duplicateIssues = fn (int $recordId) => QualityIssue::query()
        ->where('record_id', $recordId)
        ->where('rule', DuplicateRule::CODE)
        ->where('status', QualityIssueStatus::Open->value)
        ->get();
});

it('keeps scoring code and label as before when the type has no match rules', function () {
    $type = ($this->makeType)();
    $record = $this->writer->create($type, ['code' => 'SUP-1', 'label' => 'Acme Industrial Supplies'], null);

    $suggestions = $this->suggester->suggest($type, ['label' => 'Acme Industrial Supplies', 'values' => ['common' => ['vat' => 'X']]]);

    expect($suggestions)->toHaveCount(1)
        ->and($suggestions[0]->record->id)->toBe($record->id)
        ->and($suggestions[0]->score)->toBe(1.0)
        ->and($suggestions[0]->reason)->toBe('exact_label');

    expect($this->suggester->suggest($type, ['code' => 'SUP-12'])[0]->reason)->toBe('similar_code');
});

it('pulls a record through a blocking key even when its code and label share no prefix', function () {
    $type = ($this->makeType)();
    app(FieldService::class)->create($type, ['code' => 'vat', 'type' => 'text', 'is_searchable' => true]);

    $record = $this->writer->create($type, ['code' => 'ZX-900', 'label' => 'Zenith Holdings', 'values' => ['common' => ['vat' => 'DE-123 456']]], null);

    $this->rules->create(['entity_type_id' => $type->id, 'target' => 'vat', 'comparator' => 'exact', 'weight' => 1, 'is_blocking' => true]);

    $suggestions = $this->suggester->suggest($type, ['code' => 'NEW-1', 'label' => 'Acme', 'values' => ['common' => ['vat' => 'de 123-456']]]);

    expect($suggestions)->toHaveCount(1)
        ->and($suggestions[0]->record->id)->toBe($record->id)
        ->and($suggestions[0]->score)->toBe(1.0)
        ->and($suggestions[0]->reason)->toBe('rule_vat');
});

it('leaves a record out when no prefix and no blocking key reaches it', function () {
    $type = ($this->makeType)();
    app(FieldService::class)->create($type, ['code' => 'vat', 'type' => 'text', 'is_searchable' => true]);

    $this->writer->create($type, ['code' => 'ZX-900', 'label' => 'Zenith Holdings', 'values' => ['common' => ['vat' => 'DE-123']]], null);

    $this->rules->create(['entity_type_id' => $type->id, 'target' => 'vat', 'comparator' => 'exact', 'weight' => 1, 'is_blocking' => false]);

    expect($this->suggester->suggest($type, ['code' => 'NEW-1', 'label' => 'Acme', 'values' => ['common' => ['vat' => 'DE-123']]]))->toBe([]);
});

it('scores the weighted average of the rules the subject has a value for and names the strongest target', function () {
    $type = ($this->makeType)();
    app(FieldService::class)->create($type, ['code' => 'vat', 'type' => 'text', 'is_searchable' => true]);
    app(FieldService::class)->create($type, ['code' => 'city', 'type' => 'text']);

    $record = $this->writer->create($type, ['code' => 'SUP-1', 'label' => 'Acme Industrial', 'values' => ['common' => ['vat' => 'DE-1', 'city' => 'Berlin']]], null);

    $this->rules->create(['entity_type_id' => $type->id, 'target' => 'label', 'comparator' => 'token', 'weight' => 1]);
    $this->rules->create(['entity_type_id' => $type->id, 'target' => 'vat', 'comparator' => 'exact', 'weight' => 3, 'is_blocking' => true]);
    $this->rules->create(['entity_type_id' => $type->id, 'target' => 'city', 'comparator' => 'exact', 'weight' => 5]);

    $suggestions = $this->suggester->suggest($type, ['label' => 'Acme Logistics', 'values' => ['common' => ['vat' => 'DE-1']]]);

    expect($suggestions)->toHaveCount(1)
        ->and($suggestions[0]->record->id)->toBe($record->id)
        ->and($suggestions[0]->score)->toBe(round((1 * (1 / 3) + 3 * 1.0) / 4, 4))
        ->and($suggestions[0]->reason)->toBe('rule_vat')
        ->and($suggestions[0]->reasonLabel())->toBe(trans('masterdata::app.matching.reasons.rule', ['target' => 'vat']));
});

it('compares a per-locale field in the locale the subject gives', function () {
    $type = ($this->makeType)();
    app(FieldService::class)->create($type, ['code' => 'name', 'type' => 'text', 'value_per_locale' => true, 'is_searchable' => true]);

    $record = $this->writer->create($type, ['code' => 'SUP-1', 'label' => 'Unrelated', 'values' => ['locale_specific' => [
        'en_US' => ['name' => 'Blue Valve'],
        'fr_FR' => ['name' => 'Vanne Bleue'],
    ]]], null);

    $this->rules->create(['entity_type_id' => $type->id, 'target' => 'name', 'comparator' => 'exact', 'weight' => 1, 'is_blocking' => true]);

    $suggestions = $this->suggester->suggest($type, ['values' => ['locale_specific' => ['fr_FR' => ['name' => 'vanne bleue']]]]);

    expect($suggestions)->toHaveCount(1)
        ->and($suggestions[0]->record->id)->toBe($record->id)
        ->and($suggestions[0]->score)->toBe(1.0);
});

it('flags duplicates by field rules even when the labels differ', function () {
    $type = ($this->makeType)(['settings' => ['match_on_write' => false]]);
    app(FieldService::class)->create($type, ['code' => 'vat', 'type' => 'text', 'is_searchable' => true]);

    $this->rules->create(['entity_type_id' => $type->id, 'target' => 'vat', 'comparator' => 'exact', 'weight' => 1, 'is_blocking' => true]);

    $older = $this->writer->create($type, ['code' => 'SUP-1', 'label' => 'Acme GmbH', 'values' => ['common' => ['vat' => 'DE-777']]], null);
    $newer = $this->writer->create($type, ['code' => 'SUP-2', 'label' => 'Zenith Holdings', 'values' => ['common' => ['vat' => 'DE-777']]], null);
    $empty = $this->writer->create($type, ['code' => 'SUP-3'], null);

    $records = app(RecordRepository::class)->forQualityScan([$older->id, $newer->id, $empty->id]);

    $issues = app(DuplicateRule::class)->evaluate($type, $records);

    expect($issues)->not->toBeEmpty()
        ->and(collect($issues)->pluck('recordId')->unique()->values()->all())->toBe([$newer->id])
        ->and($issues[0]->relatedRecordId)->toBe($older->id);
});

it('queues the duplicate check after a write and stores the issue', function () {
    $type = ($this->makeType)(['settings' => ['match_on_write' => true]]);

    $older = $this->writer->create($type, ['code' => 'SUP-1', 'label' => 'Acme Industrial Supplies'], null);
    $newer = $this->writer->create($type, ['code' => 'SUP-2', 'label' => 'Acme Industrial Supplies'], null);

    $issues = ($this->duplicateIssues)($newer->id);

    expect($issues)->toHaveCount(1)
        ->and($issues[0]->related_record_id)->toBe($older->id);
});

it('checks for duplicates on write by default', function () {
    Queue::fake();

    $type = ($this->makeType)();

    $record = $this->writer->create($type, ['code' => 'SUP-1', 'label' => 'Acme'], null);

    Queue::assertPushed(MatchRecordOnWrite::class, fn (MatchRecordOnWrite $job): bool => $job->recordId === $record->id);
});

it('does not check for duplicates on write when the type turns it off', function () {
    $type = ($this->makeType)(['settings' => ['match_on_write' => false]]);

    $this->writer->create($type, ['code' => 'SUP-1', 'label' => 'Acme Industrial Supplies'], null);
    $newer = $this->writer->create($type, ['code' => 'SUP-2', 'label' => 'Acme Industrial Supplies'], null);

    expect(($this->duplicateIssues)($newer->id))->toHaveCount(0);

    (new MatchRecordOnWrite($newer->id))->handle(app(RecordRepository::class), app(QualityEvaluator::class));

    expect(($this->duplicateIssues)($newer->id))->toHaveCount(0);

    Queue::fake();

    $this->writer->create($type, ['code' => 'SUP-3', 'label' => 'Other'], null);

    Queue::assertNotPushed(MatchRecordOnWrite::class);
});

it('keeps the synchronous check after a write to the rules that look at the record alone', function () {
    $evaluator = app(QualityEvaluator::class);

    expect(collect($evaluator->recordRules())->contains(fn ($rule) => $rule instanceof DuplicateRule))->toBeFalse()
        ->and($evaluator->comparingRules())->toHaveCount(1)
        ->and($evaluator->comparingRules()[0])->toBeInstanceOf(DuplicateRule::class);
});

it('labels the classic reasons as before', function () {
    $type = ($this->makeType)();
    $record = $this->writer->create($type, ['code' => 'SUP-1', 'label' => 'Acme'], null);

    expect((new MatchSuggestion($record, 1.0, 'exact_label'))->reasonLabel())->toBe(trans('masterdata::app.matching.reasons.exact_label'))
        ->and((new MatchSuggestion($record, 1.0, 'rule_code'))->reasonLabel())
        ->toBe(trans('masterdata::app.matching.reasons.rule', ['target' => trans('masterdata::app.match-rules.targets.code')]));
});

it('skips the duplicate check after a write a bulk import makes', function () {
    Queue::fake([MatchRecordOnWrite::class]);

    $type = ($this->makeType)();

    $record = $this->writer->create($type, ['code' => 'SUP-1', 'label' => 'Acme'], null, new SourceContext(SourceContext::IMPORT));

    $this->writer->update($record, ['label' => 'Acme Ltd'], (int) $record->revision, null, new SourceContext(SourceContext::IMPORT));

    Queue::assertNotPushed(MatchRecordOnWrite::class);
});

it('still checks for duplicates after writes from the admin, the api and the match inbox', function (string $origin) {
    Queue::fake([MatchRecordOnWrite::class]);

    $type = ($this->makeType)();

    $record = $this->writer->create($type, ['code' => 'SUP-1', 'label' => 'Acme'], null, new SourceContext($origin));

    Queue::assertPushed(MatchRecordOnWrite::class, fn (MatchRecordOnWrite $job): bool => $job->recordId === $record->id);
})->with(['admin', 'api', 'matching', 'approval']);
