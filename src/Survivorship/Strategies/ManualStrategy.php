<?php

namespace Webkul\MasterDataManagement\Survivorship\Strategies;

use Webkul\MasterDataManagement\Survivorship\Contracts\SurvivorshipStrategy;
use Webkul\MasterDataManagement\Survivorship\SurvivorshipCandidate;

/**
 * Picks nothing, so the steward decides on the merge screen; a merge nobody decides keeps the most complete value.
 */
class ManualStrategy implements SurvivorshipStrategy
{
    /**
     * {@inheritdoc}
     */
    public function pick(array $candidates, array $options): ?SurvivorshipCandidate
    {
        return null;
    }
}
