<?php

namespace Webkul\MasterDataManagement\Matching\Comparators;

use Webkul\MasterDataManagement\Matching\Contracts\Comparator;
use Webkul\MasterDataManagement\Services\Normalizer;

/**
 * Words that sound alike compare equal: the word overlap of the metaphone keys of the normalised values.
 *
 * Words without a key, such as digits or non-Latin script, keep their normalised form.
 */
class PhoneticComparator implements Comparator
{
    public function __construct(
        protected Normalizer $normalizer,
        protected TokenComparator $tokenComparator,
    ) {}

    /**
     * {@inheritdoc}
     */
    public function compare(string $left, string $right, array $options): float
    {
        return round($this->tokenComparator->overlap($this->key($left), $this->key($right)), 4);
    }

    /**
     * The space separated metaphone keys of the value's words.
     */
    protected function key(string $value): string
    {
        $words = array_filter(explode(' ', $this->normalizer->normalize($value)), fn (string $word): bool => $word !== '');

        return implode(' ', array_map(fn (string $word): string => metaphone($word) ?: $word, $words));
    }
}
