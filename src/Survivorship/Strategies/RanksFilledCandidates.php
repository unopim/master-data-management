<?php

namespace Webkul\MasterDataManagement\Survivorship\Strategies;

use Webkul\MasterDataManagement\Survivorship\Contracts\SurvivorshipStrategy;
use Webkul\MasterDataManagement\Survivorship\SurvivorshipCandidate;

/**
 * Picks the filled candidate with the highest score; the first in candidate order wins a tie, so the survivor and
 * then the lowest record id, and the survivor keeps its value when no candidate has one.
 */
abstract class RanksFilledCandidates implements SurvivorshipStrategy
{
    /**
     * {@inheritdoc}
     */
    public function pick(array $candidates, array $options): ?SurvivorshipCandidate
    {
        $best = null;

        $bestScore = null;

        foreach ($candidates as $candidate) {
            if (! $candidate->isFilled()) {
                continue;
            }

            $score = $this->score($candidate, $options);

            if ($bestScore === null || $score > $bestScore) {
                $best = $candidate;

                $bestScore = $score;
            }
        }

        return $best ?? $candidates[0] ?? null;
    }

    /**
     * The candidate's score; the highest wins.
     *
     * @param  array<string, mixed>  $options
     */
    abstract protected function score(SurvivorshipCandidate $candidate, array $options): int|float;
}
