<?php

use Illuminate\Validation\ValidationException;
use Webkul\Core\RequestMemo;
use Webkul\MasterDataManagement\Models\Hierarchy;
use Webkul\MasterDataManagement\Models\HierarchyNode;
use Webkul\MasterDataManagement\Models\Record;
use Webkul\MasterDataManagement\Repositories\EntityTypeRepository;
use Webkul\MasterDataManagement\Repositories\HierarchyRepository;
use Webkul\MasterDataManagement\Services\FieldService;
use Webkul\MasterDataManagement\Services\HierarchyService;
use Webkul\MasterDataManagement\Services\RecordEraser;
use Webkul\MasterDataManagement\Services\RecordMerger;
use Webkul\MasterDataManagement\Services\RecordWriter;

beforeEach(function () {
    $this->type = app(EntityTypeRepository::class)->create(['code' => $this->uniqueCode('site'), 'label_field_code' => 'name', 'is_hierarchical' => true]);

    app(FieldService::class)->create($this->type, ['code' => 'name', 'type' => 'text']);

    $this->type = $this->type->fresh();
    $this->writer = app(RecordWriter::class);
    $this->service = app(HierarchyService::class);
    $this->hierarchies = app(HierarchyRepository::class);

    $this->record = fn (string $name, ?Record $parent = null): Record => $this->writer->create(
        $this->type,
        ['values' => ['common' => ['name' => $name]]] + ($parent instanceof Record ? ['parent' => $parent->uuid] : []),
        null,
    );

    $this->alternate = function (string $code = 'sales'): Hierarchy {
        $hierarchy = $this->hierarchies->create(['entity_type_id' => $this->type->id, 'code' => $code]);

        return $hierarchy->fresh();
    };

    $this->node = fn (Hierarchy $hierarchy, Record $record): ?HierarchyNode => HierarchyNode::query()
        ->where('hierarchy_id', $hierarchy->id)
        ->where('record_id', $record->id)
        ->first();
});

it('mirrors the records parent into the primary hierarchy', function () {
    $root = ($this->record)('Root');
    $child = ($this->record)('Child', $root);

    $primary = $this->hierarchies->primaryFor($this->type->id);

    expect($primary)->toBeInstanceOf(Hierarchy::class)
        ->and($primary->is_primary)->toBeTrue()
        ->and($primary->code)->toBe(Hierarchy::PRIMARY_CODE);

    $node = ($this->node)($primary, $child);

    expect($node->parent_id)->toBe($root->id)
        ->and($node->path)->toBe($child->fresh()->path)
        ->and($node->depth)->toBe(1);
});

it('keeps the primary nodes in step when a record moves', function () {
    $left = ($this->record)('Left');
    $right = ($this->record)('Right');
    $child = ($this->record)('Child', $left);
    $grandchild = ($this->record)('Grandchild', $child);

    $this->service->move($child->fresh(), $right->fresh(), $child->fresh()->revision, null);

    $primary = $this->hierarchies->primaryFor($this->type->id);

    expect(($this->node)($primary, $child)->parent_id)->toBe($right->id)
        ->and(($this->node)($primary, $child)->path)->toBe('/'.$right->id.'/'.$child->id.'/')
        ->and(($this->node)($primary, $grandchild)->path)->toBe($grandchild->fresh()->path)
        ->and(($this->node)($primary, $grandchild)->path)->toBe('/'.$right->id.'/'.$child->id.'/'.$grandchild->id.'/')
        ->and(($this->node)($primary, $grandchild)->depth)->toBe(2);
});

it('copies existing parent placement into a primary hierarchy through the data migration', function () {
    $root = ($this->record)('Root');
    $child = ($this->record)('Child', $root);

    Hierarchy::query()->where('entity_type_id', $this->type->id)->delete();

    app(RequestMemo::class)->forget('masterdata.primary_hierarchy.');

    $migration = require base_path('packages/Webkul/MasterDataManagement/src/Database/Migrations/2026_11_13_000002_create_primary_mdm_hierarchies.php');

    $migration->up();

    $primary = Hierarchy::query()->where('entity_type_id', $this->type->id)->sole();

    expect($primary->is_primary)->toBeTrue()
        ->and(HierarchyNode::query()->where('hierarchy_id', $primary->id)->count())->toBe(2)
        ->and(($this->node)($primary, $child)->parent_id)->toBe($root->id)
        ->and(($this->node)($primary, $child)->path)->toBe($child->fresh()->path);

    $migration->up();

    expect(HierarchyNode::query()->where('hierarchy_id', $primary->id)->count())->toBe(2);
});

it('allows only one primary hierarchy per entity type', function () {
    ($this->record)('Root');

    [$first] = $this->hierarchies->firstOrCreatePrimary($this->type->id);
    [$second, $created] = $this->hierarchies->firstOrCreatePrimary($this->type->id);

    expect($second->id)->toBe($first->id)
        ->and($created)->toBeFalse()
        ->and(Hierarchy::query()->where('entity_type_id', $this->type->id)->where('is_primary', true)->count())->toBe(1);
});

it('places records in an alternate hierarchy without touching the primary parent', function () {
    $hierarchy = ($this->alternate)();
    $north = ($this->record)('North');
    $site = ($this->record)('Site');

    $this->service->place($hierarchy, $site, $north);

    expect(($this->node)($hierarchy, $north)->path)->toBe('/'.$north->id.'/')
        ->and(($this->node)($hierarchy, $site)->parent_id)->toBe($north->id)
        ->and(($this->node)($hierarchy, $site)->path)->toBe('/'.$north->id.'/'.$site->id.'/')
        ->and(($this->node)($hierarchy, $site)->depth)->toBe(1)
        ->and($site->fresh()->parent_id)->toBeNull();
});

it('moves an alternate subtree and rewrites its paths and depths', function () {
    config(['masterdata.batch_size' => 1]);

    $hierarchy = ($this->alternate)();
    $north = ($this->record)('North');
    $south = ($this->record)('South');
    $region = ($this->record)('Region');
    $site = ($this->record)('Site');
    $line = ($this->record)('Line');

    $this->service->place($hierarchy, $region, $north);
    $this->service->place($hierarchy, $site, $region);
    $this->service->place($hierarchy, $line, $site);

    $this->service->place($hierarchy, $region, $south);

    expect(($this->node)($hierarchy, $region)->path)->toBe('/'.$south->id.'/'.$region->id.'/')
        ->and(($this->node)($hierarchy, $site)->path)->toBe('/'.$south->id.'/'.$region->id.'/'.$site->id.'/')
        ->and(($this->node)($hierarchy, $line)->path)->toBe('/'.$south->id.'/'.$region->id.'/'.$site->id.'/'.$line->id.'/')
        ->and(($this->node)($hierarchy, $line)->depth)->toBe(3);

    $this->service->place($hierarchy, $site, null);

    expect(($this->node)($hierarchy, $site)->path)->toBe('/'.$site->id.'/')
        ->and(($this->node)($hierarchy, $site)->depth)->toBe(0)
        ->and(($this->node)($hierarchy, $line)->path)->toBe('/'.$site->id.'/'.$line->id.'/')
        ->and(($this->node)($hierarchy, $line)->depth)->toBe(1);
});

it('refuses moving an alternate subtree larger than the configured cap', function () {
    config(['masterdata.hierarchies.max_subtree' => 1]);

    $hierarchy = ($this->alternate)();
    $top = ($this->record)('Top');
    $middle = ($this->record)('Middle');
    $other = ($this->record)('Other');

    $this->service->place($hierarchy, $middle, $top);
    $this->service->place($hierarchy, ($this->record)('Bottom A'), $middle);
    $this->service->place($hierarchy, ($this->record)('Bottom B'), $middle);

    expect(fn () => $this->service->place($hierarchy, $middle, $other))->toThrow(ValidationException::class, trans('masterdata::app.hierarchies.validation.subtree-too-large', ['count' => 1]))
        ->and(($this->node)($hierarchy, $middle)->parent_id)->toBe($top->id);
});

it('refuses a cycle in an alternate hierarchy', function () {
    $hierarchy = ($this->alternate)();
    $top = ($this->record)('Top');
    $middle = ($this->record)('Middle');
    $bottom = ($this->record)('Bottom');

    $this->service->place($hierarchy, $middle, $top);
    $this->service->place($hierarchy, $bottom, $middle);

    expect(fn () => $this->service->place($hierarchy, $top, $bottom))->toThrow(ValidationException::class)
        ->and(fn () => $this->service->place($hierarchy, $top, $top))->toThrow(ValidationException::class)
        ->and(($this->node)($hierarchy, $top)->path)->toBe('/'.$top->id.'/');
});

it('refuses records of another type and direct writes to the primary hierarchy', function () {
    $hierarchy = ($this->alternate)();
    $site = ($this->record)('Site');

    $other = app(EntityTypeRepository::class)->create(['code' => $this->uniqueCode('area'), 'code_strategy' => 'source_id']);
    $foreign = $this->writer->create($other, ['code' => 'A-1'], null);

    expect(fn () => $this->service->place($hierarchy, $site, $foreign))->toThrow(ValidationException::class)
        ->and(fn () => $this->service->place($this->hierarchies->primaryFor($this->type->id), $site, null))->toThrow(ValidationException::class);
});

it('removes a record from an alternate hierarchy and lifts its children', function () {
    $hierarchy = ($this->alternate)();
    $top = ($this->record)('Top');
    $middle = ($this->record)('Middle');
    $bottom = ($this->record)('Bottom');

    $this->service->place($hierarchy, $middle, $top);
    $this->service->place($hierarchy, $bottom, $middle);

    $this->service->remove($hierarchy, $middle);

    expect(($this->node)($hierarchy, $middle))->toBeNull()
        ->and(($this->node)($hierarchy, $bottom)->parent_id)->toBe($top->id)
        ->and(($this->node)($hierarchy, $bottom)->path)->toBe('/'.$top->id.'/'.$bottom->id.'/')
        ->and(($this->node)($hierarchy, $bottom)->depth)->toBe(1);
});

it('hands the merged record alternate placement to an unplaced survivor', function () {
    $hierarchy = ($this->alternate)();
    $top = ($this->record)('Top');
    $duplicate = ($this->record)('Acme');
    $child = ($this->record)('Child');
    $survivor = ($this->record)('Acme Ltd');

    $this->service->place($hierarchy, $duplicate, $top);
    $this->service->place($hierarchy, $child, $duplicate);

    app(RecordMerger::class)->merge($survivor, $duplicate, [], $survivor->revision, $duplicate->fresh()->revision, null);

    expect(($this->node)($hierarchy, $duplicate))->toBeNull()
        ->and(($this->node)($hierarchy, $survivor)->path)->toBe('/'.$top->id.'/'.$survivor->id.'/')
        ->and(($this->node)($hierarchy, $child)->parent_id)->toBe($survivor->id)
        ->and(($this->node)($hierarchy, $child)->path)->toBe('/'.$top->id.'/'.$survivor->id.'/'.$child->id.'/');
});

it('moves the merged record children below a survivor placed inside its subtree', function () {
    $hierarchy = ($this->alternate)();
    $top = ($this->record)('Top');
    $duplicate = ($this->record)('Acme');
    $survivor = ($this->record)('Acme Ltd');
    $child = ($this->record)('Child');

    $this->service->place($hierarchy, $duplicate, $top);
    $this->service->place($hierarchy, $survivor, $duplicate);
    $this->service->place($hierarchy, $child, $duplicate);

    app(RecordMerger::class)->merge($survivor, $duplicate, [], $survivor->revision, $duplicate->fresh()->revision, null);

    expect(($this->node)($hierarchy, $duplicate))->toBeNull()
        ->and(($this->node)($hierarchy, $survivor)->parent_id)->toBe($top->id)
        ->and(($this->node)($hierarchy, $survivor)->path)->toBe('/'.$top->id.'/'.$survivor->id.'/')
        ->and(($this->node)($hierarchy, $child)->parent_id)->toBe($survivor->id)
        ->and(($this->node)($hierarchy, $child)->path)->toBe('/'.$top->id.'/'.$survivor->id.'/'.$child->id.'/')
        ->and(($this->node)($hierarchy, $child)->depth)->toBe(2);
});

it('drops erased records from alternate hierarchies and keeps the primary tombstone', function () {
    $hierarchy = ($this->alternate)();
    $top = ($this->record)('Top');
    $middle = ($this->record)('Middle', $top);
    $bottom = ($this->record)('Bottom');

    $this->service->place($hierarchy, $middle, $top);
    $this->service->place($hierarchy, $bottom, $middle);

    $retired = $this->writer->retire($middle->fresh(), $middle->fresh()->revision, null);

    app(RecordEraser::class)->erase($retired, $retired->revision, null);

    $primary = $this->hierarchies->primaryFor($this->type->id);

    expect(($this->node)($hierarchy, $middle))->toBeNull()
        ->and(($this->node)($hierarchy, $bottom)->parent_id)->toBe($top->id)
        ->and(($this->node)($hierarchy, $bottom)->path)->toBe('/'.$top->id.'/'.$bottom->id.'/')
        ->and(($this->node)($primary, $middle))->not->toBeNull();
});

it('keeps a retired record placed in alternate hierarchies', function () {
    $hierarchy = ($this->alternate)();
    $top = ($this->record)('Top');
    $site = ($this->record)('Site');

    $this->service->place($hierarchy, $site, $top);

    $this->writer->retire($site->fresh(), $site->fresh()->revision, null);

    expect(($this->node)($hierarchy, $site))->not->toBeNull()
        ->and(fn () => $this->service->place($hierarchy, ($this->record)('Other'), $site->fresh()))->toThrow(ValidationException::class);
});
