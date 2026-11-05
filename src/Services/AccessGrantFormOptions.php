<?php

namespace Webkul\MasterDataManagement\Services;

use Webkul\MasterDataManagement\Enums\GrantAction;
use Webkul\MasterDataManagement\Enums\GrantPrincipal;
use Webkul\MasterDataManagement\Repositories\EntityTypeRepository;
use Webkul\User\Models\Role;

/**
 * Builds the select options of the data access forms: on the Data access screen and on the role page card.
 */
class AccessGrantFormOptions
{
    public function __construct(
        protected EntityTypeFormOptions $entityTypeFormOptions,
        protected EntityTypeRepository $entityTypeRepository,
    ) {}

    /**
     * @return list<array{id: string, label: string}>
     */
    public function principalTypes(): array
    {
        return array_map(
            fn (GrantPrincipal $type): array => ['id' => $type->value, 'label' => trans('masterdata::app.access.principals.'.$type->value)],
            GrantPrincipal::cases(),
        );
    }

    /**
     * The custom roles a grant can name; a full access role reaches every record already.
     *
     * @return list<array{id: int, label: string}>
     */
    public function roles(): array
    {
        return $this->entityTypeFormOptions->customRoles()
            ->map(fn (Role $role): array => ['id' => (int) $role->id, 'label' => (string) $role->name])
            ->values()
            ->all();
    }

    /**
     * @return list<array{id: int, label: string}>
     */
    public function users(): array
    {
        return $this->entityTypeFormOptions->adminOptions();
    }

    /**
     * The entity types a grant can be limited to, led by "every scoped type".
     *
     * @return list<array{id: int|string, label: string}>
     */
    public function entityTypes(): array
    {
        return [['id' => '', 'label' => trans('masterdata::app.access.form.any-entity-type')], ...$this->entityTypeRepository->options()];
    }

    /**
     * @return list<array{id: string, label: string}>
     */
    public function actions(): array
    {
        return array_map(
            fn (GrantAction $action): array => ['id' => $action->value, 'label' => trans('masterdata::app.access.actions.'.$action->value)],
            GrantAction::cases(),
        );
    }
}
