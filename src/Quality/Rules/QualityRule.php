<?php

namespace Webkul\MasterDataManagement\Quality\Rules;

use Illuminate\Database\Eloquent\Collection;
use Webkul\MasterDataManagement\Models\EntityType;
use Webkul\MasterDataManagement\Models\Record;
use Webkul\MasterDataManagement\Quality\DetectedIssue;

/**
 * A data quality check over a batch of records of one entity type.
 *
 * Rules are listed in `masterdata.quality.rules`; configurable rule types join the same list.
 */
interface QualityRule
{
    /**
     * The code stored on the rule's issues.
     */
    public function code(): string;

    /**
     * Report the findings for the draft and active records of the type; an empty list means all pass.
     *
     * @param  Collection<int, Record>  $records
     * @return list<DetectedIssue>
     */
    public function evaluate(EntityType $type, Collection $records): array;
}
