<?php

namespace Webkul\MasterDataManagement\Matching;

use Illuminate\Support\Facades\Lang;
use Webkul\MasterDataManagement\Models\Record;

/**
 * One scored record proposed for an incoming source row.
 */
final readonly class MatchSuggestion
{
    /**
     * @param  float  $score  Similarity from 0 to 1.
     * @param  string  $reason  What scored best: exact_code, exact_label, compact_label, similar_label or similar_code, or
     *                          rule_{target} for the match rule target that contributed most.
     */
    public function __construct(
        public Record $record,
        public float $score,
        public string $reason,
    ) {}

    /**
     * The reason in words for the steward.
     */
    public function reasonLabel(): string
    {
        if (! str_starts_with($this->reason, MatchSuggester::RULE_REASON_PREFIX)) {
            return trans('masterdata::app.matching.reasons.'.$this->reason);
        }

        $target = substr($this->reason, strlen(MatchSuggester::RULE_REASON_PREFIX));

        $key = 'masterdata::app.match-rules.targets.'.$target;

        return trans('masterdata::app.matching.reasons.rule', ['target' => Lang::has($key) ? trans($key) : $target]);
    }
}
