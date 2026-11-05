<?php

use Webkul\MasterDataManagement\Enums\ChangeOperation;
use Webkul\MasterDataManagement\Enums\ChangeRequestStatus;
use Webkul\MasterDataManagement\Enums\MatchCandidateStatus;
use Webkul\MasterDataManagement\Enums\RecordAction;
use Webkul\MasterDataManagement\Matching\MatchInbox;
use Webkul\MasterDataManagement\Models\ChangeRequest;
use Webkul\MasterDataManagement\Repositories\EntityTypeRepository;
use Webkul\MasterDataManagement\Repositories\RoleEntityPermissionRepository;
use Webkul\MasterDataManagement\Repositories\SourceSystemRepository;
use Webkul\MasterDataManagement\Security\EntityTypePermissions;
use Webkul\MasterDataManagement\Services\ChangeRequestService;
use Webkul\MasterDataManagement\Services\RecordWriter;
use Webkul\MasterDataManagement\Services\XrefService;
use Webkul\User\Models\Admin;

beforeEach(function () {
    $this->inbox = app(MatchInbox::class);
    $this->writer = app(RecordWriter::class);
    $this->xrefs = app(XrefService::class);
    $this->system = app(SourceSystemRepository::class)->create(['code' => $this->uniqueCode('erp'), 'name' => 'ERP', 'type' => 'erp']);

    $this->makeType = fn (array $attributes = []) => app(EntityTypeRepository::class)->create($attributes + ['code' => $this->uniqueCode('line')]);
});

it('leaves an exact match pending when the type has no threshold', function () {
    $type = ($this->makeType)();
    $this->writer->create($type, ['label' => 'Filler Line North'], null);

    $candidate = $this->inbox->submit($this->system, $type, 'ERP-1', ['label' => 'Filler Line North'], Admin::factory()->create());

    expect($candidate->status)->toBe(MatchCandidateStatus::Pending)
        ->and($candidate->auto_matched)->toBeFalse()
        ->and($candidate->top_score)->toBe(1.0)
        ->and($this->xrefs->resolve($this->system, $type->id, 'ERP-1'))->toBeNull();
});

it('accepts the best suggestion automatically when it reaches the threshold', function () {
    $type = ($this->makeType)(['settings' => ['auto_match_threshold' => 0.95]]);
    $record = $this->writer->create($type, ['label' => 'Filler Line North'], null);
    $admin = Admin::factory()->create();

    $candidate = $this->inbox->submit($this->system, $type, 'ERP-1', ['label' => 'Filler Line North'], $admin);

    expect($candidate->status)->toBe(MatchCandidateStatus::Matched)
        ->and($candidate->auto_matched)->toBeTrue()
        ->and($candidate->matched_record_id)->toBe($record->id)
        ->and($candidate->resolved_by)->toBe($admin->id)
        ->and($this->xrefs->resolve($this->system, $type->id, 'ERP-1')?->id)->toBe($record->id);
});

it('leaves an ambiguous row pending when two suggestions reach the threshold', function () {
    $type = ($this->makeType)(['settings' => ['auto_match_threshold' => 0.9]]);
    $this->writer->create($type, ['label' => 'Filler Line North'], null);
    $this->writer->create($type, ['label' => 'Filler Line North'], null);

    $candidate = $this->inbox->submit($this->system, $type, 'ERP-1', ['label' => 'Filler Line North'], Admin::factory()->create());

    expect($candidate->status)->toBe(MatchCandidateStatus::Pending)
        ->and($candidate->auto_matched)->toBeFalse()
        ->and($this->xrefs->resolve($this->system, $type->id, 'ERP-1'))->toBeNull();
});

it('leaves a row below the threshold pending', function () {
    $type = ($this->makeType)(['settings' => ['auto_match_threshold' => 0.99]]);
    $this->writer->create($type, ['label' => 'Filler Line North'], null);

    $candidate = $this->inbox->submit($this->system, $type, 'ERP-1', ['label' => 'Filler Line Nrth'], Admin::factory()->create());

    expect($candidate->top_score)->toBeLessThan(0.99)
        ->and($candidate->status)->toBe(MatchCandidateStatus::Pending)
        ->and($candidate->auto_matched)->toBeFalse();
});

it('holds an automatic accept for approval on a governed type even when the submitter may approve', function () {
    $type = ($this->makeType)(['approval_required' => true, 'settings' => ['auto_match_threshold' => 0.95]]);
    $record = $this->writer->create($type, ['label' => 'Filler Line North'], null);
    $submitter = Admin::factory()->create();

    $candidate = $this->inbox->submit($this->system, $type, 'ERP-1', ['label' => 'Filler Line North'], $submitter);

    $changeRequest = ChangeRequest::query()->where('record_id', $record->id)->sole();

    expect($candidate->status)->toBe(MatchCandidateStatus::Pending)
        ->and($candidate->auto_matched)->toBeTrue()
        ->and($this->xrefs->resolve($this->system, $type->id, 'ERP-1'))->toBeNull()
        ->and($changeRequest->operation)->toBe(ChangeOperation::LinkXref)
        ->and($changeRequest->status)->toBe(ChangeRequestStatus::Pending)
        ->and($changeRequest->requested_by)->toBe($submitter->id)
        ->and($changeRequest->context[MatchInbox::CONTEXT_KEY])->toBe($candidate->id)
        ->and($changeRequest->context[MatchInbox::AUTO_CONTEXT_KEY])->toEqual(1.0);

    $approver = Admin::factory()->create();

    app(ChangeRequestService::class)->approve($changeRequest, $approver, null);

    $candidate->refresh();

    expect($candidate->status)->toBe(MatchCandidateStatus::Matched)
        ->and($candidate->auto_matched)->toBeTrue()
        ->and($candidate->resolved_by)->toBe($approver->id)
        ->and($this->xrefs->resolve($this->system, $type->id, 'ERP-1')?->id)->toBe($record->id);
});

it('holds an automatic accept without an author on a governed type', function () {
    $type = ($this->makeType)(['approval_required' => true, 'settings' => ['auto_match_threshold' => 0.95]]);
    $record = $this->writer->create($type, ['label' => 'Filler Line North'], null);

    $candidate = $this->inbox->submit($this->system, $type, 'ERP-1', ['label' => 'Filler Line North'], null);

    expect($candidate->status)->toBe(MatchCandidateStatus::Pending)
        ->and($candidate->auto_matched)->toBeTrue()
        ->and(ChangeRequest::query()->where('record_id', $record->id)->where('status', ChangeRequestStatus::Pending->value)->count())->toBe(1)
        ->and($this->xrefs->resolve($this->system, $type->id, 'ERP-1'))->toBeNull();
});

it('does not accept automatically for a submitter who may not edit the record', function () {
    $type = ($this->makeType)(['settings' => ['auto_match_threshold' => 0.95]]);
    $this->writer->create($type, ['label' => 'Filler Line North'], null);

    $viewer = $this->loginWithPermissions('custom', ['dashboard', 'masterdata.records']);

    app(RoleEntityPermissionRepository::class)->syncForEntityType($type->id, [$viewer->role_id => [RecordAction::View->value]]);
    app(EntityTypePermissions::class)->flush();

    expect($this->inbox->suggestionsFor($this->inbox->submit($this->system, $type, 'ERP-0', ['label' => 'Filler Line North'], $viewer), 1, $viewer))->toHaveCount(1);

    $candidate = $this->inbox->submit($this->system, $type, 'ERP-1', ['label' => 'Filler Line North'], null);
    $other = $this->inbox->submit($this->system, $type, 'ERP-2', ['label' => 'Filler Line North'], $viewer);

    expect($candidate->status)->toBe(MatchCandidateStatus::Matched)
        ->and($other->status)->toBe(MatchCandidateStatus::Pending)
        ->and($other->auto_matched)->toBeFalse();
});

it('stores and validates the threshold on the entity type form', function () {
    $this->loginAsAdmin();

    $type = ($this->makeType)(['settings' => ['completeness_scope' => 'required']]);

    $this->putJson(route('admin.masterdata.entity_types.update', $type->id), ['settings' => ['auto_match_threshold' => '1.5']])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('settings.auto_match_threshold');

    $this->putJson(route('admin.masterdata.entity_types.update', $type->id), ['settings' => ['auto_match_threshold' => '0.92']])
        ->assertOk();

    expect($type->refresh()->settings)->toMatchArray(['completeness_scope' => 'required', 'auto_match_threshold' => 0.92])
        ->and($this->inbox->threshold($type))->toBe(0.92);

    $this->putJson(route('admin.masterdata.entity_types.update', $type->id), ['settings' => ['auto_match_threshold' => '']])
        ->assertOk();

    expect($this->inbox->threshold($type->refresh()))->toBeNull();
});

it('shows the automatic decision in the match inbox grid', function () {
    $this->loginAsAdmin();

    $type = ($this->makeType)(['settings' => ['auto_match_threshold' => 0.95]]);
    $this->writer->create($type, ['label' => 'Filler Line North'], null);

    $candidate = $this->inbox->submit($this->system, $type, 'ERP-1', ['label' => 'Filler Line North'], null);

    $row = collect($this->ajaxGrid(route('admin.masterdata.matching.index', ['filters' => ['entity_type_code' => [$type->code]]]))->assertOk()->json('records'))
        ->firstWhere('id', $candidate->id);

    expect($row['auto_matched'])->toContain(trans('masterdata::app.matching.datagrid.auto'));

    $this->get(route('admin.masterdata.matching.show', $candidate->id))
        ->assertOk()
        ->assertSeeText(trans('masterdata::app.matching.show.auto-matched'));
});
