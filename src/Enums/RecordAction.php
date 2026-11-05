<?php

namespace Webkul\MasterDataManagement\Enums;

enum RecordAction: string
{
    case View = 'view';
    case Create = 'create';
    case Edit = 'edit';
    case Delete = 'delete';
    case Merge = 'merge';
    case Approve = 'approve';

    /**
     * The static ACL key that guards this action.
     */
    public function aclKey(): string
    {
        return 'masterdata.records.'.$this->value;
    }
}
