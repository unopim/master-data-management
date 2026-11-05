<?php

namespace Webkul\MasterDataManagement\Http\ViewComposers;

use Illuminate\View\View;
use Webkul\MasterDataManagement\Enums\GrantPrincipal;
use Webkul\MasterDataManagement\Repositories\AccessGrantRepository;
use Webkul\MasterDataManagement\Services\AccessGrantFormOptions;

/**
 * Feeds the "Master data access" card of the core role form with the edited role's grants and the select options.
 */
class RoleAccessCardComposer
{
    public function __construct(
        protected AccessGrantRepository $accessGrantRepository,
        protected AccessGrantFormOptions $formOptions,
    ) {}

    /**
     * Bind the card data to the view.
     */
    public function compose(View $view): void
    {
        $roleId = request()->route('id');

        $view->with([
            'accessGrants'      => is_numeric($roleId) ? $this->accessGrantRepository->forPrincipal(GrantPrincipal::Role, (int) $roleId) : collect(),
            'entityTypeOptions' => $this->formOptions->entityTypes(),
            'actionOptions'     => $this->formOptions->actions(),
            'canAddGrant'       => bouncer()->hasPermission('masterdata.access.create'),
            'canRemoveGrant'    => bouncer()->hasPermission('masterdata.access.delete'),
        ]);
    }
}
