<?php

namespace Webkul\MasterDataManagement\Matching\Comparators;

use Webkul\MasterDataManagement\Matching\Contracts\Comparator;
use Webkul\MasterDataManagement\Services\Normalizer;

/**
 * Equal after normalisation scores 1, anything else 0.
 */
class ExactComparator implements Comparator
{
    public function __construct(protected Normalizer $normalizer) {}

    /**
     * {@inheritdoc}
     */
    public function compare(string $left, string $right, array $options): float
    {
        $left = $this->normalizer->normalize($left);

        return $left !== '' && $left === $this->normalizer->normalize($right) ? 1.0 : 0.0;
    }
}
