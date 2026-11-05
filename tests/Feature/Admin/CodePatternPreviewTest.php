<?php

use Webkul\MasterDataManagement\Repositories\EntityTypeRepository;

it('peeks the next sequence number without allocating it', function () {
    $repository = app(EntityTypeRepository::class);
    $type = $repository->create(['code' => $this->uniqueCode('peek')]);

    expect($repository->nextSequenceValue($type))->toBe(1);

    $repository->nextCode($type);
    $repository->nextCode($type);

    expect($repository->nextSequenceValue($type))->toBe(3)
        ->and($repository->nextSequenceValue($type))->toBe(3)
        ->and($repository->nextCode($type))->toBe(strtoupper($type->code).'-000003');
});

it('renders the live code pattern preview in the quick-create modal starting at one', function () {
    $this->loginAsAdmin();

    $this->get(route('admin.masterdata.entity_types.index'))
        ->assertOk()
        ->assertSee('<v-code-pattern-preview', false)
        ->assertSee(':start="1"', false)
        ->assertSee('v-code-pattern-preview-template', false);
});

it('renders the code pattern preview on the edit page from the next unallocated number', function () {
    $this->loginAsAdmin();

    $repository = app(EntityTypeRepository::class);
    $type = $repository->create(['code' => $this->uniqueCode('preview')]);

    foreach (range(1, 4) as $ignored) {
        $repository->nextCode($type);
    }

    $this->get(route('admin.masterdata.entity_types.edit', $type->id))
        ->assertOk()
        ->assertSee('<v-code-pattern-preview', false)
        ->assertSee('type-code="'.$type->code.'"', false)
        ->assertSee(':start="5"', false);
});

it('renders the code pattern preview on the full create page starting at one', function () {
    $this->loginAsAdmin();

    $this->get(route('admin.masterdata.entity_types.create'))
        ->assertOk()
        ->assertSee('<v-code-pattern-preview', false)
        ->assertSee(':start="1"', false);
});
