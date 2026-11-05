<?php

namespace Webkul\MasterDataManagement\Matching\Comparators;

use DateTimeInterface;
use Illuminate\Support\Facades\Date;
use Throwable;
use Webkul\MasterDataManagement\Matching\Contracts\Comparator;

/**
 * Dates within `options.days` of each other: 1 on the same day, falling linearly to 0 at the window; without a window
 * only the same day matches. A value that is not a date scores 0.
 */
class DateComparator implements Comparator
{
    /**
     * {@inheritdoc}
     */
    public function compare(string $left, string $right, array $options): float
    {
        $left = $this->parse($left);

        $right = $this->parse($right);

        if (! $left instanceof DateTimeInterface || ! $right instanceof DateTimeInterface) {
            return 0.0;
        }

        $days = abs((int) $left->diff($right)->days);

        $window = max(0.0, (float) ($options['days'] ?? 0));

        if ($window <= 0.0) {
            return $days === 0 ? 1.0 : 0.0;
        }

        return round(max(0.0, 1 - $days / $window), 4);
    }

    /**
     * The calendar day of the value, or null when it is not a date.
     */
    protected function parse(string $value): ?DateTimeInterface
    {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        try {
            return Date::parse($value)->startOfDay();
        } catch (Throwable) {
            return null;
        }
    }
}
