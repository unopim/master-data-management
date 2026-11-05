<?php

namespace Webkul\MasterDataManagement\Support;

use Carbon\CarbonInterface;

/**
 * Formats a stored date for the admin pages in the signed-in admin's time zone.
 */
final class AdminDate
{
    public const string FORMAT = 'Y-m-d H:i:s';

    /**
     * Format the date, or return the fallback when there is none; a Carbon date is copied because the core
     * formatter moves it into the admin's time zone.
     */
    public static function format(CarbonInterface|string|null $date, string $empty = ''): string
    {
        if ($date === null || $date === '') {
            return $empty;
        }

        return core()->formatDateWithTimeZone($date instanceof CarbonInterface ? $date->copy() : $date, self::FORMAT);
    }
}
