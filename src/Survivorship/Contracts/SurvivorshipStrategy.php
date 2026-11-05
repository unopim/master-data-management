<?php

namespace Webkul\MasterDataManagement\Survivorship\Contracts;

use Webkul\MasterDataManagement\Survivorship\SurvivorshipCandidate;

/**
 * Picks which of the records being merged keeps its value of one target.
 */
interface SurvivorshipStrategy
{
    /**
     * The candidate whose value survives, or null to leave the pick to the steward.
     *
     * @param  list<SurvivorshipCandidate>  $candidates  The survivor first, then the other records by id.
     * @param  array<string, mixed>  $options  The options stored on the rule.
     */
    public function pick(array $candidates, array $options): ?SurvivorshipCandidate;
}
