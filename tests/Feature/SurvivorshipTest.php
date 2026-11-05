<?php

use Illuminate\Support\Carbon;
use Webkul\MasterDataManagement\Models\Record;
use Webkul\MasterDataManagement\Repositories\EntityTypeRepository;
use Webkul\MasterDataManagement\Repositories\SourceSystemRepository;
use Webkul\MasterDataManagement\Repositories\SurvivorshipRuleRepository;
use Webkul\MasterDataManagement\Services\FieldService;
use Webkul\MasterDataManagement\Services\RecordMerger;
use Webkul\MasterDataManagement\Services\RecordWriter;
use Webkul\MasterDataManagement\Services\SurvivorshipResolver;
use Webkul\MasterDataManagement\Services\XrefService;
use Webkul\MasterDataManagement\Survivorship\Strategies\KeepTargetStrategy;
use Webkul\MasterDataManagement\Survivorship\Strategies\ManualStrategy;
use Webkul\MasterDataManagement\Survivorship\Strategies\MostCompleteStrategy;
use Webkul\MasterDataManagement\Survivorship\Strategies\MostRecentStrategy;
use Webkul\MasterDataManagement\Survivorship\Strategies\SourcePriorityStrategy;
use Webkul\MasterDataManagement\Survivorship\StrategyRegistry;
use Webkul\MasterDataManagement\Survivorship\SurvivorshipCandidate;

beforeEach(function () {
    $this->type = app(EntityTypeRepository::class)->create(['code' => $this->uniqueCode('supplier')]);

    foreach (['name', 'city', 'phone'] as $code) {
        app(FieldService::class)->create($this->type, ['code' => $code, 'type' => 'text']);
    }

    $this->type = $this->type->fresh();
    $this->writer = app(RecordWriter::class);
    $this->merger = app(RecordMerger::class);
    $this->resolver = app(SurvivorshipResolver::class);
    $this->rules = app(SurvivorshipRuleRepository::class);
    $this->xrefs = app(XrefService::class);
    $this->systems = app(SourceSystemRepository::class);

    $this->record = fn (array $common, array $extra = []) => $this->writer->create($this->type, ['values' => ['common' => $common]] + $extra, null);

    $this->rule = fn (?string $target, string $strategy, ?array $options = null) => $this->rules->create([
        'entity_type_id' => $this->type->id,
        'target'         => $target,
        'strategy'       => $strategy,
        'options'        => $options,
    ]);

    $this->candidate = fn (int $id, array $value, array $sources = [], ?string $updatedAt = null) => new SurvivorshipCandidate(
        (new Record)->forceFill(['id' => $id, 'updated_at' => $updatedAt ?? '2026-01-01 00:00:00']),
        $value,
        $sources,
    );
});

it('registers every strategy in the config', function () {
    expect(app(StrategyRegistry::class)->codes())->toBe(['source_priority', 'most_recent', 'most_complete', 'keep_target', 'manual'])
        ->and(app(StrategyRegistry::class)->get('manual'))->toBeInstanceOf(ManualStrategy::class)
        ->and(app(StrategyRegistry::class)->label('most_recent'))->toBe(trans('masterdata::app.survivorship-rules.strategies.most_recent'));
});

it('picks the value of the most trusted source, by source order then priority', function () {
    $strategy = app(SourcePriorityStrategy::class);

    $survivor = ($this->candidate)(1, ['common' => 'A'], [10 => 50]);
    $erp = ($this->candidate)(2, ['common' => 'B'], [20 => 5]);
    $crm = ($this->candidate)(3, ['common' => 'C'], [30 => 1]);
    $unlinked = ($this->candidate)(4, ['common' => 'D']);
    $emptyCrm = ($this->candidate)(5, ['common' => ''], [30 => 1]);

    expect($strategy->pick([$survivor, $erp, $crm, $unlinked], [])->record->id)->toBe(3)
        ->and($strategy->pick([$survivor, $erp, $crm], ['source_order' => [20]])->record->id)->toBe(2)
        ->and($strategy->pick([$survivor, $emptyCrm], [])->record->id)->toBe(1)
        ->and($strategy->pick([$unlinked, ($this->candidate)(6, ['common' => 'E'])], [])->record->id)->toBe(4);
});

it('picks the value of the record updated last', function () {
    $strategy = app(MostRecentStrategy::class);

    $survivor = ($this->candidate)(1, ['common' => 'A'], [], '2026-01-01 00:00:00');
    $newer = ($this->candidate)(2, ['common' => 'B'], [], '2026-02-01 00:00:00');
    $newerEmpty = ($this->candidate)(3, ['common' => null], [], '2026-03-01 00:00:00');
    $tie = ($this->candidate)(4, ['common' => 'C'], [], '2026-01-01 00:00:00');

    expect($strategy->pick([$survivor, $newer, $newerEmpty], [])->record->id)->toBe(2)
        ->and($strategy->pick([$survivor, $tie], [])->record->id)->toBe(1);
});

it('picks the fullest value', function () {
    $strategy = app(MostCompleteStrategy::class);

    expect($strategy->pick([($this->candidate)(1, ['common' => 'Acme']), ($this->candidate)(2, ['common' => 'Acme Steel'])], [])->record->id)->toBe(2)
        ->and($strategy->pick([($this->candidate)(1, ['common' => ['a']]), ($this->candidate)(2, ['common' => ['a', 'b']])], [])->record->id)->toBe(2)
        ->and($strategy->pick([($this->candidate)(1, ['common' => 'Acme']), ($this->candidate)(2, ['common' => 'Acme'])], [])->record->id)->toBe(1)
        ->and($strategy->pick([($this->candidate)(1, ['common' => '']), ($this->candidate)(2, ['common' => ''])], [])->record->id)->toBe(1);
});

it('keeps the survivor or leaves the pick to the steward', function () {
    $candidates = [($this->candidate)(1, ['common' => '']), ($this->candidate)(2, ['common' => 'Pune'])];

    expect(app(KeepTargetStrategy::class)->pick($candidates, [])->record->id)->toBe(1)
        ->and(app(ManualStrategy::class)->pick($candidates, []))->toBeNull();
});

it('decides each field by its own rule, then the default rule, then the most complete value', function () {
    $survivor = ($this->record)(['name' => 'Acme', 'city' => null, 'phone' => '111']);
    $duplicate = ($this->record)(['name' => 'Acme Steel Ltd', 'city' => 'Pune', 'phone' => '222']);

    expect($this->resolver->decide($survivor, $duplicate))->toBe(['name' => 'survivor', 'city' => 'merged', 'phone' => 'survivor']);

    ($this->rule)(null, 'most_complete');
    ($this->rule)('city', 'keep_target');
    ($this->rule)('phone', 'manual');

    expect($this->resolver->decide($survivor, $duplicate))->toBe(['name' => 'merged', 'city' => 'survivor', 'phone' => null]);
});

it('ignores a rule whose strategy is no longer registered', function () {
    $survivor = ($this->record)(['name' => 'Acme']);
    $duplicate = ($this->record)(['name' => 'Acme Steel Ltd']);

    ($this->rule)('name', 'telepathy');

    expect($this->resolver->decide($survivor, $duplicate))->toBe(['name' => 'survivor']);
});

it('pre-fills the merge preview from the rules and leaves manual fields unpicked', function () {
    $erp = $this->systems->create(['code' => $this->uniqueCode('erp'), 'name' => 'ERP', 'type' => 'erp', 'priority' => 1]);
    $crm = $this->systems->create(['code' => $this->uniqueCode('crm'), 'name' => 'CRM', 'type' => 'crm', 'priority' => 50]);

    $survivor = ($this->record)(['name' => 'Acme', 'city' => 'Mumbai', 'phone' => '111'], ['label' => 'Acme']);
    $duplicate = ($this->record)(['name' => 'Acme Ltd', 'city' => 'Pune', 'phone' => '222'], ['label' => 'Acme Limited']);

    $this->xrefs->attach($survivor, $crm, 'C-1');
    $this->xrefs->attach($duplicate, $erp, 'E-1');

    ($this->rule)(null, 'source_priority');
    ($this->rule)('phone', 'manual');
    ($this->rule)('label', 'most_complete');

    $preview = $this->merger->preview($survivor, $duplicate);

    expect($preview['name']['suggested'])->toBe('merged')
        ->and($preview['city']['suggested'])->toBe('merged')
        ->and($preview['phone']['suggested'])->toBeNull()
        ->and($preview['label'])->toBe(['survivor' => ['common' => 'Acme'], 'merged' => ['common' => 'Acme Limited'], 'suggested' => 'merged']);
});

it('merges by the rules while the steward choices still win, and unmerges back', function () {
    $erp = $this->systems->create(['code' => $this->uniqueCode('erp'), 'name' => 'ERP', 'type' => 'erp', 'priority' => 1]);

    $survivor = ($this->record)(['name' => 'Acme', 'city' => 'Mumbai', 'phone' => '111']);
    $duplicate = ($this->record)(['name' => 'Acme Ltd', 'city' => 'Pune', 'phone' => '222']);

    $this->xrefs->attach($duplicate, $erp, 'E-9');

    ($this->rule)(null, 'source_priority', ['source_order' => [$erp->id]]);
    ($this->rule)('phone', 'manual');

    $this->merger->merge($survivor, $duplicate, ['city' => 'survivor'], $survivor->revision, $duplicate->revision, null);

    expect($survivor->fresh()->values['common'])->toEqual(['name' => 'Acme Ltd', 'city' => 'Mumbai', 'phone' => '111']);

    $this->merger->unmerge($duplicate->fresh(), null);

    expect($survivor->fresh()->values['common'])->toEqual(['name' => 'Acme', 'city' => 'Mumbai', 'phone' => '111'])
        ->and($duplicate->fresh()->values['common'])->toEqual(['name' => 'Acme Ltd', 'city' => 'Pune', 'phone' => '222']);
});

it('merges without a steward by the rules alone', function () {
    $survivor = ($this->record)(['name' => 'Acme', 'city' => 'Mumbai']);

    Carbon::setTestNow(now()->addMinute());

    $duplicate = ($this->record)(['name' => 'Acme Ltd', 'city' => 'Pune']);

    Carbon::setTestNow();

    ($this->rule)(null, 'most_recent');

    $this->merger->merge($survivor, $duplicate, [], $survivor->revision, $duplicate->revision, null);

    expect($survivor->fresh()->values['common'])->toEqual(['name' => 'Acme Ltd', 'city' => 'Pune']);
});
