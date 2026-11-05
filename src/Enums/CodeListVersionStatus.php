<?php

namespace Webkul\MasterDataManagement\Enums;

enum CodeListVersionStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Retired = 'retired';

    /**
     * The `x-admin::badge` variant the status shows with.
     */
    public function badgeVariant(): string
    {
        return match ($this) {
            self::Draft     => 'warning',
            self::Published => 'success',
            self::Retired   => 'neutral',
        };
    }
}
