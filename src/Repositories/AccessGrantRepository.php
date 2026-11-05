<?php

namespace Webkul\MasterDataManagement\Repositories;

use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Database\Eloquent\Collection;
use Webkul\Core\Eloquent\Repository;
use Webkul\MasterDataManagement\Contracts\AccessGrant;
use Webkul\MasterDataManagement\Enums\GrantAction;
use Webkul\MasterDataManagement\Enums\GrantPrincipal;
use Webkul\MasterDataManagement\Models\AccessGrant as AccessGrantModel;

class AccessGrantRepository extends Repository
{
    /**
     * Get model class name.
     */
    public function model(): string
    {
        return AccessGrant::class;
    }

    /**
     * The subtree grants of the given principals with the current path of each subtree root, in one query.
     *
     * @return list<array{entity_type_id: ?int, path: string, actions: list<string>}>
     */
    public function subtreesFor(?int $roleId, int $adminId): array
    {
        return $this->model->newQuery()
            ->toBase()
            ->join('mdm_records', 'mdm_records.id', '=', 'mdm_access_grants.record_id')
            ->whereNotNull('mdm_records.path')
            ->where(function (Builder $principals) use ($roleId, $adminId): void {
                $principals->where(fn (Builder $user) => $user
                    ->where('mdm_access_grants.principal_type', GrantPrincipal::User->value)
                    ->where('mdm_access_grants.principal_id', $adminId));

                if ($roleId !== null) {
                    $principals->orWhere(fn (Builder $role) => $role
                        ->where('mdm_access_grants.principal_type', GrantPrincipal::Role->value)
                        ->where('mdm_access_grants.principal_id', $roleId));
                }
            })
            ->get(['mdm_access_grants.entity_type_id', 'mdm_access_grants.actions', 'mdm_records.path'])
            ->map(fn (object $row): array => [
                'entity_type_id' => $row->entity_type_id === null ? null : (int) $row->entity_type_id,
                'path'           => (string) $row->path,
                'actions'        => self::normalizeActions((array) json_decode((string) $row->actions, true)),
            ])
            ->all();
    }

    /**
     * The grants of one principal with their subtree root and entity type, oldest first.
     *
     * @return Collection<int, AccessGrantModel>
     */
    public function forPrincipal(GrantPrincipal $type, int $principalId): Collection
    {
        return $this->model->newQuery()
            ->where('principal_type', $type->value)
            ->where('principal_id', $principalId)
            ->with(['record:id,uuid,code,label,entity_type_id', 'entityType.translations'])
            ->orderBy('id')
            ->get();
    }

    /**
     * Delete the given grants of one principal, ignoring ids that belong to another.
     *
     * @param  list<int>  $ids
     */
    public function deleteForPrincipalByIds(GrantPrincipal $type, int $principalId, array $ids): void
    {
        if ($ids === []) {
            return;
        }

        $this->model->newQuery()
            ->where('principal_type', $type->value)
            ->where('principal_id', $principalId)
            ->whereKey($ids)
            ->delete();
    }

    /**
     * Delete every grant of one principal.
     */
    public function deleteForPrincipal(GrantPrincipal $type, int $principalId): void
    {
        $this->model->newQuery()
            ->where('principal_type', $type->value)
            ->where('principal_id', $principalId)
            ->delete();
    }

    /**
     * Keep only known grant actions, once each, in enum order.
     *
     * @param  array<int, mixed>  $actions
     * @return list<string>
     */
    public static function normalizeActions(array $actions): array
    {
        return array_values(array_map(
            fn (GrantAction $action): string => $action->value,
            array_filter(GrantAction::cases(), fn (GrantAction $action): bool => in_array($action->value, $actions, true)),
        ));
    }
}
