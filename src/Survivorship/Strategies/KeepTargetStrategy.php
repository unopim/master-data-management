<?php

namespace Webkul\MasterDataManagement\Survivorship\Strategies;

use Webkul\MasterDataManagement\Survivorship\Contracts\SurvivorshipStrategy;
use Webkul\MasterDataManagement\Survivorship\SurvivorshipCandidate;

/**
 * Keeps the survivor's value, even when it is empty.
 */
class KeepTargetStrategy implements SurvivorshipStrategy
{
    /**
     * {@inheritdoc}
     */
    public function pick(array $candidates, array $options): ?SurvivorshipCandidate
    {
        return $candidates[0] ?? null;
    }
}
