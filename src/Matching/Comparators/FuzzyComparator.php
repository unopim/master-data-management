<?php

namespace Webkul\MasterDataManagement\Matching\Comparators;

use Webkul\MasterDataManagement\Matching\Contracts\Comparator;
use Webkul\MasterDataManagement\Services\Normalizer;

/**
 * Edit distance, common characters and word overlap blended after normalisation: equal is 1, equal apart from
 * spacing 0.97, anything else at most 0.95.
 */
class FuzzyComparator implements Comparator
{
    /**
     * The values compared equal after normalisation.
     */
    public const string EXACT = 'exact';

    /**
     * The values compared equal once spaces are removed.
     */
    public const string COMPACT = 'compact';

    /**
     * The values compared only similar.
     */
    public const string SIMILAR = 'similar';

    public function __construct(
        protected Normalizer $normalizer,
        protected TokenComparator $tokenComparator,
    ) {}

    /**
     * {@inheritdoc}
     */
    public function compare(string $left, string $right, array $options): float
    {
        return $this->assess($left, $right)[0];
    }

    /**
     * The similarity and how it was reached: exact, compact or similar.
     *
     * @return array{0: float, 1: string}
     */
    public function assess(string $left, string $right): array
    {
        $left = $this->normalizer->normalize($left);

        $right = $this->normalizer->normalize($right);

        if ($left === '' || $right === '') {
            return [0.0, self::SIMILAR];
        }

        if ($left === $right) {
            return [1.0, self::EXACT];
        }

        $compactLeft = str_replace(' ', '', $left);

        $compactRight = str_replace(' ', '', $right);

        if ($compactLeft === $compactRight) {
            return [0.97, self::COMPACT];
        }

        $length = max(mb_strlen($compactLeft), mb_strlen($compactRight));

        $distance = 1 - levenshtein(mb_substr($compactLeft, 0, 255), mb_substr($compactRight, 0, 255)) / max(1, min($length, 255));

        similar_text($compactLeft, $compactRight, $percent);

        $score = 0.4 * max(0.0, $distance) + 0.3 * ($percent / 100) + 0.3 * $this->tokenComparator->overlap($left, $right);

        return [round(min($score, 0.95), 4), self::SIMILAR];
    }
}
