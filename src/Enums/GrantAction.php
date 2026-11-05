<?php

namespace Webkul\MasterDataManagement\Enums;

enum GrantAction: string
{
    case View = 'view';
    case Edit = 'edit';
    case Approve = 'approve';

    /**
     * The actions a grant of this action also allows; edit and approve both need to read the record.
     *
     * @return list<self>
     */
    public function implied(): array
    {
        return match ($this) {
            self::View    => [self::View],
            self::Edit    => [self::Edit, self::View],
            self::Approve => [self::Approve, self::View],
        };
    }
}
