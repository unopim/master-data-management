<?php

namespace Webkul\MasterDataManagement\Matching\Comparators;

use Webkul\MasterDataManagement\Matching\Contracts\Comparator;

/**
 * Numbers within `options.tolerance` of each other: 1 when equal, falling linearly to 0 at the tolerance; without a
 * tolerance only equal numbers match. A value that is not a number scores 0.
 */
class NumericComparator implements Comparator
{
    /**
     * {@inheritdoc}
     */
    public function compare(string $left, string $right, array $options): float
    {
        $left = trim($left);

        $right = trim($right);

        if (! is_numeric($left) || ! is_numeric($right)) {
            return 0.0;
        }

        $difference = abs((float) $left - (float) $right);

        $tolerance = max(0.0, (float) ($options['tolerance'] ?? 0));

        if ($tolerance <= 0.0) {
            return $difference < PHP_FLOAT_EPSILON ? 1.0 : 0.0;
        }

        return round(max(0.0, 1 - $difference / $tolerance), 4);
    }
}
