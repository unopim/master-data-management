<?php

use Webkul\MasterDataManagement\Enums\RecordAction;
use Webkul\MasterDataManagement\Listeners\NotifyChangeRequestApprovers;
use Webkul\MasterDataManagement\Matching\MatchInbox;
use Webkul\MasterDataManagement\Models\ChangeRequest;
use Webkul\MasterDataManagement\Repositories\EntityTypeRepository;
use Webkul\MasterDataManagement\Repositories\RoleEntityPermissionRepository;
use Webkul\MasterDataManagement\Repositories\SourceSystemRepository;
use Webkul\MasterDataManagement\Security\ChangeApprovals;
use Webkul\MasterDataManagement\Services\RecordWriter;
use Webkul\Notification\Models\Notification;
use Webkul\User\Models\Admin;
use Webkul\User\Models\Role;

beforeEach(function () {
    $this->type = app(EntityTypeRepository::class)->create(['code' => $this->uniqueCode('line'), 'approval_required' => true]);
    $this->writer = app(RecordWriter::class);

    $this->customAdmin = fn (array $permissions = [], array $attributes = []) => Admin::factory()->create($attributes + [
        'role_id' => Role::factory()->create(['permission_type' => 'custom', 'permissions' => $permissions])->id,
    ]);

    $this->notifiedIds = function (ChangeRequest $changeRequest): array {
        $notification = Notification::query()
            ->where('type', NotifyChangeRequestApprovers::TYPE)
            ->get()
            ->first(fn (Notification $notification): bool => (int) ($notification->route_params['id'] ?? 0) === (int) $changeRequest->id);

        return $notification?->userNotifications()->pluck('admin_id')->map(fn ($id): int => (int) $id)->all() ?? [];
    };
});

it('notifies the admins who may decide a held change and links them to it', function () {
    $requester = ($this->customAdmin)();
    $fullAccess = Admin::factory()->create(['role_id' => Role::factory()->create(['permission_type' => 'all'])->id]);

    $this->writer->create($this->type, ['label' => 'Filler Line North'], $requester);

    $changeRequest = ChangeRequest::query()->where('entity_type_id', $this->type->id)->sole();

    $notification = Notification::query()->where('type', NotifyChangeRequestApprovers::TYPE)->latest('id')->first();

    expect(($this->notifiedIds)($changeRequest))->toContain($fullAccess->id)
        ->not->toContain($requester->id)
        ->and($notification->route)->toBe('admin.masterdata.approvals.show')
        ->and($notification->title)->toBe(trans('masterdata::app.approvals.notification.title', ['id' => $changeRequest->id]));
});

it('notifies a custom role only with the approve key and the approve grant or data ownership', function () {
    $key = RecordAction::Approve->aclKey();
    $granted = ($this->customAdmin)([$key]);
    $keyOnly = ($this->customAdmin)([$key]);
    $grantOnly = ($this->customAdmin)();
    $owner = ($this->customAdmin)([$key]);
    $inactive = ($this->customAdmin)([$key], ['status' => 0]);

    app(RoleEntityPermissionRepository::class)->syncForEntityType($this->type->id, [
        $granted->role_id   => [RecordAction::Approve->value],
        $grantOnly->role_id => [RecordAction::Approve->value],
        $inactive->role_id  => [RecordAction::Approve->value],
    ]);

    $this->type->update(['data_owner_id' => $owner->id]);

    $this->writer->create($this->type, ['label' => 'Filler Line North'], ($this->customAdmin)());

    $changeRequest = ChangeRequest::query()->where('entity_type_id', $this->type->id)->sole();

    $notified = ($this->notifiedIds)($changeRequest);

    foreach ($notified as $adminId) {
        expect(app(ChangeApprovals::class)->canDecide(Admin::query()->findOrFail($adminId), $changeRequest))->toBeTrue();
    }

    expect($notified)->toContain($granted->id, $owner->id)
        ->not->toContain($keyOnly->id)
        ->not->toContain($grantOnly->id)
        ->not->toContain($inactive->id);
});

it('sends no notification when the change applies directly', function () {
    $this->type->update(['approval_required' => false]);

    $before = Notification::query()->where('type', NotifyChangeRequestApprovers::TYPE)->count();

    $this->writer->create($this->type, ['label' => 'Filler Line North'], ($this->customAdmin)());

    expect(Notification::query()->where('type', NotifyChangeRequestApprovers::TYPE)->count())->toBe($before);
});

it('describes an automated change held without an author', function () {
    $this->type->update(['settings' => ['auto_match_threshold' => 0.95]]);
    $this->writer->create($this->type, ['label' => 'Filler Line North'], null);
    $system = app(SourceSystemRepository::class)->create(['code' => $this->uniqueCode('erp'), 'name' => 'ERP', 'type' => 'erp']);

    app(MatchInbox::class)->submit($system, $this->type, 'ERP-1', ['label' => 'Filler Line North'], null);

    $changeRequest = ChangeRequest::query()->where('entity_type_id', $this->type->id)->sole();

    $notification = Notification::query()->where('type', NotifyChangeRequestApprovers::TYPE)->latest('id')->first();

    expect((int) $notification->route_params['id'])->toBe($changeRequest->id)
        ->and($notification->description)->toBe(trans('masterdata::app.approvals.notification.description-automated', [
            'operation' => trans('masterdata::app.approvals.operations.link_xref'),
            'type'      => $this->type->code,
        ]));
});
