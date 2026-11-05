<?php

namespace Webkul\MasterDataManagement\Repositories;

use Illuminate\Support\Facades\DB;
use Webkul\Core\Eloquent\Repository;
use Webkul\MasterDataManagement\Contracts\RoleEntityPermission;
use Webkul\MasterDataManagement\Enums\RecordAction;

class RoleEntityPermissionRepository extends Repository
{
    /**
     * Get model class name.
     */
    public function model(): string
    {
        return RoleEntityPermission::class;
    }

    /**
     * The record actions a role is granted on one entity type.
     *
     * @return list<string>
     */
    public function actionsFor(int $roleId, int $entityTypeId): array
    {
        $grant = $this->model->newQuery()
            ->where('role_id', $roleId)
            ->where('entity_type_id', $entityTypeId)
            ->first(['actions']);

        return $grant ? $this->normalize($grant->actions ?? []) : [];
    }

    /**
     * Every grant of a role in one query.
     *
     * @return array<int, list<string>> Granted actions keyed by entity type id.
     */
    public function grantsForRole(int $roleId): array
    {
        return $this->model->newQuery()
            ->where('role_id', $roleId)
            ->get(['entity_type_id', 'actions'])
            ->mapWithKeys(fn ($grant): array => [(int) $grant->entity_type_id => $this->normalize($grant->actions ?? [])])
            ->all();
    }

    /**
     * The grants of every role on one entity type.
     *
     * @return array<int, list<string>> Granted actions keyed by role id.
     */
    public function grantsForEntityType(int $entityTypeId): array
    {
        return $this->model->newQuery()
            ->where('entity_type_id', $entityTypeId)
            ->get(['role_id', 'actions'])
            ->mapWithKeys(fn ($grant): array => [(int) $grant->role_id => $this->normalize($grant->actions ?? [])])
            ->all();
    }

    /**
     * Replace the grants of the given roles on one entity type; an empty action list removes the role's grant.
     *
     * Roles missing from the map keep their grant. Unknown actions are dropped.
     *
     * @param  array<int, list<string>>  $actionsByRoleId
     */
    public function syncForEntityType(int $entityTypeId, array $actionsByRoleId): void
    {
        $upserts = [];
        $revoked = [];

        foreach ($actionsByRoleId as $roleId => $actions) {
            $actions = $this->normalize($actions);

            if ($actions === []) {
                $revoked[] = (int) $roleId;

                continue;
            }

            $upserts[] = [
                'role_id'        => (int) $roleId,
                'entity_type_id' => $entityTypeId,
                'actions'        => json_encode($actions),
            ];
        }

        DB::transaction(function () use ($entityTypeId, $upserts, $revoked): void {
            if ($revoked !== []) {
                $this->model->newQuery()
                    ->where('entity_type_id', $entityTypeId)
                    ->whereIn('role_id', $revoked)
                    ->delete();
            }

            if ($upserts !== []) {
                $this->model->newQuery()->upsert($upserts, ['role_id', 'entity_type_id'], ['actions']);
            }
        });
    }

    /**
     * Keep only known record actions, once each, in enum order.
     *
     * @param  array<int, mixed>  $actions
     * @return list<string>
     */
    protected function normalize(array $actions): array
    {
        return array_values(array_map(
            fn (RecordAction $action): string => $action->value,
            array_filter(RecordAction::cases(), fn (RecordAction $action): bool => in_array($action->value, $actions, true))
        ));
    }
}
