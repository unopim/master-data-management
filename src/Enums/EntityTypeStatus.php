<?php

namespace Webkul\MasterDataManagement\Enums;

enum EntityTypeStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Archived = 'archived';

    /**
     * Admin label class of the status badge: draft as pending, active as active and archived as neutral.
     */
    public function badgeClass(): string
    {
        return match ($this) {
            self::Active   => 'label-active',
            self::Draft    => 'label-pending',
            self::Archived => 'label-info',
        };
    }
}
