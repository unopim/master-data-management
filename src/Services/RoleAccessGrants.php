<?php

namespace Webkul\MasterDataManagement\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Webkul\MasterDataManagement\Enums\GrantAction;
use Webkul\MasterDataManagement\Enums\GrantPrincipal;
use Webkul\MasterDataManagement\Models\EntityTypeProxy;
use Webkul\MasterDataManagement\Models\Record;
use Webkul\MasterDataManagement\Repositories\AccessGrantRepository;
use Webkul\MasterDataManagement\Repositories\RecordRepository;
use Webkul\User\Models\Role;

/**
 * The "Master data access" card of the core role form: lists the role's grants, adds one and removes the ticked ones.
 *
 * The card submits under `masterdata_access`, flagged by `masterdata_access.present`, so a role saved from anywhere
 * else keeps its grants. Adding needs `masterdata.access.create` and removing `masterdata.access.delete`.
 */
class RoleAccessGrants
{
    public const string INPUT = 'masterdata_access';

    public function __construct(
        protected AccessGrantRepository $accessGrantRepository,
        protected RecordRepository $recordRepository,
    ) {}

    /**
     * Whether the request carries the card.
     */
    public function submitted(Request $request): bool
    {
        return $request->boolean(self::INPUT.'.present');
    }

    /**
     * Validate the grant the card adds, before the role is saved.
     *
     * @throws ValidationException
     */
    public function validate(Request $request): void
    {
        if (! $this->submitted($request) || ! $this->adds($request)) {
            return;
        }

        $key = self::INPUT;

        Validator::make($request->all(), [
            $key.'.record'         => ['required', 'uuid'],
            $key.'.entity_type_id' => ['nullable', 'integer', Rule::exists(EntityTypeProxy::modelClass(), 'id')],
            $key.'.actions'        => ['required', 'array', 'min:1'],
            $key.'.actions.*'      => [Rule::enum(GrantAction::class)],
        ], [], [
            $key.'.record'  => trans('masterdata::app.access.form.root'),
            $key.'.actions' => trans('masterdata::app.access.form.actions'),
        ])->validate();

        if (! $this->root($request) instanceof Record) {
            throw ValidationException::withMessages([$key.'.record' => trans('masterdata::app.access.validation.record-not-found')]);
        }
    }

    /**
     * Apply the card to the saved role: remove the ticked grants, then add the new one.
     */
    public function sync(Role $role, Request $request): void
    {
        if (! $this->submitted($request)) {
            return;
        }

        if (bouncer()->hasPermission('masterdata.access.delete')) {
            $this->accessGrantRepository->deleteForPrincipalByIds(
                GrantPrincipal::Role,
                (int) $role->id,
                array_values(array_map(intval(...), (array) $request->input(self::INPUT.'.remove', []))),
            );
        }

        if (! $this->adds($request)) {
            return;
        }

        $root = $this->root($request);

        if (! $root instanceof Record) {
            return;
        }

        $this->accessGrantRepository->create([
            'principal_type' => GrantPrincipal::Role,
            'principal_id'   => (int) $role->id,
            'entity_type_id' => $request->filled(self::INPUT.'.entity_type_id') ? (int) $request->input(self::INPUT.'.entity_type_id') : null,
            'record_id'      => (int) $root->id,
            'actions'        => AccessGrantRepository::normalizeActions((array) $request->input(self::INPUT.'.actions', [])),
        ]);
    }

    /**
     * Whether the card asks for a new grant and the admin may add one.
     */
    protected function adds(Request $request): bool
    {
        return $request->filled(self::INPUT.'.record') && bouncer()->hasPermission('masterdata.access.create');
    }

    /**
     * The chosen subtree root among the records the admin may see.
     */
    protected function root(Request $request): ?Record
    {
        return $this->recordRepository->findByUuid((string) $request->input(self::INPUT.'.record'));
    }
}
