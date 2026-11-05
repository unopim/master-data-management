<?php

namespace Webkul\MasterDataManagement\Survivorship\Strategies;

use Webkul\MasterDataManagement\Survivorship\SurvivorshipCandidate;

/**
 * Keeps the value of the record updated last.
 */
class MostRecentStrategy extends RanksFilledCandidates
{
    protected function score(SurvivorshipCandidate $candidate, array $options): int
    {
        return (int) $candidate->record->updated_at?->getTimestamp();
    }
}
