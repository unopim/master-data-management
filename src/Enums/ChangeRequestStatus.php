<?php

namespace Webkul\MasterDataManagement\Enums;

enum ChangeRequestStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Stale = 'stale';

    /**
     * The `x-admin::badge` variant the status shows with.
     */
    public function badgeVariant(): string
    {
        return match ($this) {
            self::Pending  => 'warning',
            self::Approved => 'success',
            self::Rejected => 'danger',
            self::Stale    => 'neutral',
        };
    }
}
