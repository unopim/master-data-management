<?php

namespace Webkul\MasterDataManagement\Survivorship\Strategies;

use Webkul\MasterDataManagement\Models\SurvivorshipRule;
use Webkul\MasterDataManagement\Survivorship\SurvivorshipCandidate;

/**
 * Keeps the value of the record linked to the most trusted source system.
 *
 * Trust follows the rule's `source_order` option, a list of source system ids, when it names a system; every other
 * system ranks after those by its priority, lower first. A record linked to no source system ranks last.
 */
class SourcePriorityStrategy extends RanksFilledCandidates
{
    /**
     * The code the strategy is registered under; its rules take a source order.
     */
    public const string CODE = 'source_priority';

    protected function score(SurvivorshipCandidate $candidate, array $options): int|float
    {
        $order = array_map(intval(...), array_values((array) ($options[SurvivorshipRule::OPTION_SOURCE_ORDER] ?? [])));

        $best = null;

        foreach ($candidate->sources as $systemId => $priority) {
            $position = array_search((int) $systemId, $order, true);

            $rank = $position === false ? count($order) + max(0, $priority) : $position;

            $best = $best === null ? $rank : min($best, $rank);
        }

        return $best === null ? -INF : -$best;
    }
}
