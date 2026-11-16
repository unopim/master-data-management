<?php

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Webkul\DataTransfer\Helpers\Import;
use Webkul\DataTransfer\Jobs\Import\ImportTrackBatch;
use Webkul\DataTransfer\Models\JobInstances;
use Webkul\DataTransfer\Models\JobTrack;
use Webkul\MasterDataManagement\Enums\GrantPrincipal;
use Webkul\MasterDataManagement\Enums\RecordAction;
use Webkul\MasterDataManagement\Events\ChangeRequestBatchSubmitted;
use Webkul\MasterDataManagement\Listeners\NotifyChangeRequestApprovers;
use Webkul\MasterDataManagement\Listeners\NotifyChangeRequestBatchApprovers;
use Webkul\MasterDataManagement\Listeners\NotifyChangeRequestRequester;
use Webkul\MasterDataManagement\Models\ChangeRequest;
use Webkul\MasterDataManagement\Repositories\AccessGrantRepository;
use Webkul\MasterDataManagement\Repositories\EntityTypeRepository;
use Webkul\MasterDataManagement\Repositories\RoleEntityPermissionRepository;
use Webkul\MasterDataManagement\Security\AccessGrantResolver;
use Webkul\MasterDataManagement\Security\EntityTypePermissions;
use Webkul\MasterDataManagement\Services\ChangeRequestService;
use Webkul\MasterDataManagement\Services\FieldService;
use Webkul\MasterDataManagement\Services\RecordWriter;
use Webkul\MasterDataManagement\ValueObjects\SourceContext;
use Webkul\Notification\Mail\UserNotify;
use Webkul\Notification\Models\Notification;
use Webkul\User\Models\Admin;
use Webkul\User\Models\Role;

beforeEach(function () {
    Mail::fake();

    config([
        'mail.default'              => 'smtp',
        'mail.mailers.smtp.host'    => 'smtp.example.test',
        'mail.mailers.smtp.port'    => 2525,
        'masterdata.notifications'  => ['email' => true],
    ]);

    $this->type = app(EntityTypeRepository::class)->create(['code' => $this->uniqueCode('line'), 'status' => 'active', 'code_strategy' => 'source_id', 'approval_required' => true]);
    $this->writer = app(RecordWriter::class);

    $this->admin = function (array $types, bool $approves): Admin {
        $role = Role::factory()->create(['permission_type' => 'custom', 'permissions' => array_merge(
            ['masterdata', 'masterdata.records', 'masterdata.records.view', 'masterdata.records.create', 'masterdata.records.edit', 'masterdata.records.delete', 'masterdata.approvals'],
            $approves ? [RecordAction::Approve->aclKey()] : [],
        )]);

        foreach ($types as $type) {
            app(RoleEntityPermissionRepository::class)->syncForEntityType($type->id, [
                $role->id => array_merge(['view', 'create', 'edit', 'delete'], $approves ? ['approve'] : []),
            ]);
        }

        app(EntityTypePermissions::class)->flush();

        return Admin::factory()->create(['role_id' => $role->id]);
    };

    $this->requester = ($this->admin)([$this->type], false);
    $this->approver = ($this->admin)([$this->type], true);

    $this->noticesFor = fn (Admin $admin, string $type) => Notification::query()
        ->where('type', $type)
        ->whereHas('userNotifications', fn ($query) => $query->where('admin_id', $admin->id))
        ->get();

    $this->importTrack = function (string $csv): array {
        Storage::fake('private');

        if (! $this->type->fields()->where('code', 'name')->exists()) {
            app(FieldService::class)->create($this->type, ['code' => 'name', 'type' => 'text']);
        }

        $path = 'imports/'.$this->uniqueCode('file').'.csv';
        Storage::disk('private')->put($path, $csv);

        $instance = JobInstances::query()->create([
            'code'                => $this->uniqueCode('lines'),
            'entity_type'         => 'masterdata_records',
            'type'                => 'import',
            'action'              => Import::ACTION_APPEND,
            'validation_strategy' => Import::VALIDATION_STRATEGY_SKIP_ERRORS,
            'allowed_errors'      => 0,
            'field_separator'     => ',',
            'file_path'           => $path,
            'filters'             => ['entity_type' => $this->type->code],
        ]);

        $track = JobTrack::query()->create([
            'state'               => Import::STATE_PENDING,
            'type'                => 'import',
            'action'              => Import::ACTION_APPEND,
            'validation_strategy' => Import::VALIDATION_STRATEGY_SKIP_ERRORS,
            'allowed_errors'      => 0,
            'field_separator'     => ',',
            'file_path'           => $path,
            'meta'                => $instance->toArray(),
            'job_instances_id'    => $instance->id,
            'user_id'             => $this->requester->id,
        ]);

        return [$instance, $track];
    };

    $this->mailedTo = fn (Admin $admin): int => Mail::queued(UserNotify::class, fn (UserNotify $mail): bool => $mail->hasTo($admin->email))->count();
});

it('emails the approvers of a held change when mail is configured', function () {
    $this->writer->create($this->type, ['code' => 'L-1', 'label' => 'Filler Line North'], $this->requester);

    $changeRequest = ChangeRequest::query()->where('entity_type_id', $this->type->id)->sole();

    $mail = Mail::queued(UserNotify::class, fn (UserNotify $mail): bool => $mail->hasTo($this->approver->email))->sole();

    expect(($this->noticesFor)($this->approver, NotifyChangeRequestApprovers::TYPE))->toHaveCount(1)
        ->and($mail->hasTo($this->requester->email))->toBeFalse()
        ->and($mail->render())->toContain(route('admin.masterdata.approvals.show', $changeRequest->id));
});

it('sends no approver email when mail is not configured or master data emails are off', function () {
    config(['mail.mailers.smtp.host' => null]);

    $this->writer->create($this->type, ['code' => 'L-1', 'label' => 'Filler Line North'], $this->requester);

    config(['mail.mailers.smtp.host' => 'smtp.example.test', 'masterdata.notifications.email' => false]);

    $this->writer->create($this->type, ['code' => 'L-2', 'label' => 'Filler Line South'], $this->requester);

    expect(($this->noticesFor)($this->approver, NotifyChangeRequestApprovers::TYPE))->toHaveCount(2);

    Mail::assertNothingQueued();
});

it('notifies the requester of an approval and of a rejection with the comment', function () {
    $this->writer->create($this->type, ['code' => 'L-1', 'label' => 'Filler Line North'], $this->requester);
    $this->writer->create($this->type, ['code' => 'L-2', 'label' => 'Filler Line South'], $this->requester);

    [$first, $second] = ChangeRequest::query()->where('entity_type_id', $this->type->id)->orderBy('id')->get()->all();

    $service = app(ChangeRequestService::class);
    $service->approve($first, $this->approver, null);
    $service->reject($second, $this->approver, 'Duplicate of line L-1');

    $notices = ($this->noticesFor)($this->requester, NotifyChangeRequestRequester::TYPE)->sortBy('id')->values();

    expect($notices)->toHaveCount(2)
        ->and($notices[0]->title)->toBe(trans('masterdata::app.approvals.notification.approved-title', ['id' => $first->id]))
        ->and($notices[0]->route)->toBe('admin.masterdata.approvals.show')
        ->and((int) $notices[0]->route_params['id'])->toBe($first->id)
        ->and($notices[1]->title)->toBe(trans('masterdata::app.approvals.notification.rejected-title', ['id' => $second->id]))
        ->and($notices[1]->description)->toContain('Duplicate of line L-1')
        ->and(($this->mailedTo)($this->requester))->toBe(2);

    $rejection = Mail::queued(UserNotify::class, fn (UserNotify $mail): bool => $mail->hasTo($this->requester->email))->last();

    expect($rejection->render())->toContain('Duplicate of line L-1')
        ->toContain(route('admin.masterdata.approvals.show', $second->id));
});

it('does not notify anyone of the decision on an automated change without a requester', function () {
    $this->writer->create($this->type, ['code' => 'L-1', 'label' => 'Filler Line North'], $this->requester);

    $automated = ChangeRequest::query()->where('entity_type_id', $this->type->id)->sole();
    $automated->update(['requested_by' => null]);

    $before = Notification::query()->where('type', NotifyChangeRequestRequester::TYPE)->count();

    app(ChangeRequestService::class)->approve($automated->fresh(), $this->approver, null);

    expect(Notification::query()->where('type', NotifyChangeRequestRequester::TYPE)->count())->toBe($before)
        ->and(($this->mailedTo)($this->requester))->toBe(0);
});

it('skips a requester whose own change they decided', function () {
    $this->writer->create($this->type, ['code' => 'L-1', 'label' => 'Filler Line North'], $this->requester);

    $changeRequest = ChangeRequest::query()->where('entity_type_id', $this->type->id)->sole();

    app(ChangeRequestService::class)->reject($changeRequest, $this->requester, 'Withdrawn');

    expect(($this->noticesFor)($this->requester, NotifyChangeRequestRequester::TYPE))->toHaveCount(0)
        ->and(($this->mailedTo)($this->requester))->toBe(0);
});

it('sends one notification per approver for the changes one import holds', function () {
    [$instance, $track] = ($this->importTrack)("code,name\nL-1,North\nL-2,South\nL-3,East\n");

    ImportTrackBatch::dispatchSync($track);

    expect($track->refresh()->state)->toBe(Import::STATE_COMPLETED)
        ->and(ChangeRequest::query()->where('entity_type_id', $this->type->id)->count())->toBe(3);

    $notices = ($this->noticesFor)($this->approver, NotifyChangeRequestBatchApprovers::TYPE);

    expect($notices)->toHaveCount(1)
        ->and($notices[0]->description)->toBe(trans('masterdata::app.approvals.notification.batch.import', ['name' => $instance->code, 'count' => 3]))
        ->and($notices[0]->route)->toBe('admin.masterdata.approvals.index')
        ->and(($this->noticesFor)($this->approver, NotifyChangeRequestApprovers::TYPE))->toHaveCount(0)
        ->and(($this->mailedTo)($this->approver))->toBe(1);
});

it('announces the changes a failed or cancelled import held, once per batch', function (string $state) {
    [$instance, $track] = ($this->importTrack)("code,name\nL-1,North\n");

    $source = new SourceContext(SourceContext::IMPORT, context: [ChangeRequestBatchSubmitted::CONTEXT_KEY => ChangeRequestBatchSubmitted::importKey($track->id)]);

    $this->writer->create($this->type, ['code' => 'L-1', 'name' => 'North'], $this->requester, $source);
    $this->writer->create($this->type, ['code' => 'L-2', 'name' => 'South'], $this->requester, $source);

    expect(($this->noticesFor)($this->approver, NotifyChangeRequestApprovers::TYPE))->toHaveCount(0);

    $track->update(['state' => $state]);
    $track->update(['state' => Import::STATE_FAILED]);

    $notices = ($this->noticesFor)($this->approver, NotifyChangeRequestBatchApprovers::TYPE);

    expect($notices)->toHaveCount(1)
        ->and($notices[0]->description)->toBe(trans('masterdata::app.approvals.notification.batch.import', ['name' => $instance->code, 'count' => 2]))
        ->and(($this->mailedTo)($this->approver))->toBe(1);
})->with([Import::STATE_FAILED, Import::STATE_CANCELLED]);

it('does not announce a completed import again when it is marked failed afterwards', function () {
    [, $track] = ($this->importTrack)("code,name\nL-1,North\nL-2,South\n");

    ImportTrackBatch::dispatchSync($track);

    $track->refresh()->update(['state' => Import::STATE_FAILED]);

    expect(($this->noticesFor)($this->approver, NotifyChangeRequestBatchApprovers::TYPE))->toHaveCount(1)
        ->and(($this->mailedTo)($this->approver))->toBe(1);
});

it('sends no batch notice for a failed import that held nothing', function () {
    [, $track] = ($this->importTrack)("code,name\nL-1,North\n");

    $track->update(['state' => Import::STATE_FAILED]);

    expect(($this->noticesFor)($this->approver, NotifyChangeRequestBatchApprovers::TYPE))->toHaveCount(0);
});

it('counts the changes of an import into an auto matching type in the one batch notice', function () {
    $this->type->update(['settings' => ['auto_match_threshold' => 0.5]]);
    $this->writer->create($this->type, ['code' => 'L-0', 'name' => 'North'], null);

    ImportTrackBatch::dispatchSync(($this->importTrack)("code,name\nL-1,North\nL-2,North\n")[1]);

    expect(($this->noticesFor)($this->approver, NotifyChangeRequestBatchApprovers::TYPE))->toHaveCount(1)
        ->and(($this->noticesFor)($this->approver, NotifyChangeRequestApprovers::TYPE))->toHaveCount(0)
        ->and(($this->mailedTo)($this->approver))->toBe(1);
});

it('sends each recipient their own e-mail so approvers do not see each other', function () {
    $second = ($this->admin)([$this->type], true);

    $this->writer->create($this->type, ['code' => 'L-1', 'label' => 'Filler Line North'], $this->requester);

    $mails = Mail::queued(UserNotify::class, fn (UserNotify $mail): bool => $mail->hasTo($this->approver->email) || $mail->hasTo($second->email));

    expect($mails)->toHaveCount(2);

    foreach ($mails as $mail) {
        expect($mail->envelope()->to)->toHaveCount(1);
    }
});

it('sends one notification per approver for the retirements one mass action holds', function () {
    $records = collect(['L-1', 'L-2'])->map(fn (string $code) => $this->writer->create($this->type, ['code' => $code, 'label' => $code], null));

    $this->actingAs($this->requester, 'admin');

    $this->postJson(route('admin.masterdata.records.mass_retire', $this->type->code), ['indices' => $records->pluck('id')->all()])
        ->assertOk()
        ->assertJsonPath('pending', 2);

    $notices = ($this->noticesFor)($this->approver, NotifyChangeRequestBatchApprovers::TYPE);

    expect($notices)->toHaveCount(1)
        ->and($notices[0]->description)->toBe(trans('masterdata::app.approvals.notification.batch.mass-retire', ['count' => 2]))
        ->and(($this->noticesFor)($this->approver, NotifyChangeRequestApprovers::TYPE))->toHaveCount(0)
        ->and(($this->mailedTo)($this->approver))->toBe(1);
});

it('does not notify an approver whose approve grant does not cover the record', function () {
    $site = app(EntityTypeRepository::class)->create(['code' => $this->uniqueCode('site'), 'status' => 'active', 'code_strategy' => 'source_id', 'is_hierarchical' => true]);
    $equipment = app(EntityTypeRepository::class)->create([
        'code'                    => $this->uniqueCode('equipment'),
        'status'                  => 'active',
        'code_strategy'           => 'source_id',
        'is_hierarchical'         => true,
        'is_scoped'               => true,
        'approval_required'       => true,
        'allowed_parent_type_ids' => [$site->id],
    ]);

    $siteA = $this->writer->create($site, ['code' => 'SITE-A'], null);
    $siteB = $this->writer->create($site, ['code' => 'SITE-B'], null);

    $requester = ($this->admin)([$site, $equipment], false);
    $inScope = ($this->admin)([$site, $equipment], true);
    $outOfScope = ($this->admin)([$site, $equipment], true);

    foreach ([[$requester, $siteA, ['view', 'create', 'edit']], [$inScope, $siteA, ['view', 'approve']], [$outOfScope, $siteB, ['view', 'approve']]] as [$admin, $root, $actions]) {
        app(AccessGrantRepository::class)->create([
            'principal_type' => GrantPrincipal::User,
            'principal_id'   => $admin->id,
            'entity_type_id' => null,
            'record_id'      => $root->id,
            'actions'        => $actions,
        ]);
    }

    app(AccessGrantResolver::class)->flush();

    $this->writer->submitCreate($equipment, ['code' => 'EQ-A9', 'parent' => $siteA->uuid], $requester);

    expect(($this->noticesFor)($inScope, NotifyChangeRequestApprovers::TYPE))->toHaveCount(1)
        ->and(($this->noticesFor)($outOfScope, NotifyChangeRequestApprovers::TYPE))->toHaveCount(0)
        ->and(($this->mailedTo)($inScope))->toBe(1)
        ->and(($this->mailedTo)($outOfScope))->toBe(0);
});
