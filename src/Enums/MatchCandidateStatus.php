<?php

namespace Webkul\MasterDataManagement\Enums;

enum MatchCandidateStatus: string
{
    case Pending = 'pending';
    case Matched = 'matched';
    case Created = 'created';
    case Rejected = 'rejected';

    /**
     * The `x-admin::badge` variant the status shows with.
     */
    public function badgeVariant(): string
    {
        return match ($this) {
            self::Pending                => 'warning',
            self::Rejected               => 'neutral',
            self::Matched, self::Created => 'success',
        };
    }
}
