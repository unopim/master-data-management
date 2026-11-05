<?php

namespace Webkul\MasterDataManagement\Survivorship\Strategies;

use Webkul\MasterDataManagement\Survivorship\SurvivorshipCandidate;

/**
 * Keeps the fullest value: the longest text or the list with the most items.
 */
class MostCompleteStrategy extends RanksFilledCandidates
{
    protected function score(SurvivorshipCandidate $candidate, array $options): int
    {
        return $candidate->size();
    }
}
