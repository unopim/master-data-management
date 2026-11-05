<?php

namespace Webkul\MasterDataManagement\Matching\Comparators;

use Webkul\MasterDataManagement\Matching\Contracts\Comparator;
use Webkul\MasterDataManagement\Services\Normalizer;

/**
 * The share of distinct words both values have (Jaccard), so word order does not matter.
 */
class TokenComparator implements Comparator
{
    public function __construct(protected Normalizer $normalizer) {}

    /**
     * {@inheritdoc}
     */
    public function compare(string $left, string $right, array $options): float
    {
        return round($this->overlap($this->normalizer->normalize($left), $this->normalizer->normalize($right)), 4);
    }

    /**
     * The Jaccard overlap of the space separated tokens of two normalised values.
     */
    public function overlap(string $left, string $right): float
    {
        $leftTokens = array_unique(array_filter(explode(' ', $left), fn (string $token): bool => $token !== ''));

        $rightTokens = array_unique(array_filter(explode(' ', $right), fn (string $token): bool => $token !== ''));

        $union = count(array_unique([...$leftTokens, ...$rightTokens]));

        return $union === 0 ? 0.0 : count(array_intersect($leftTokens, $rightTokens)) / $union;
    }
}
