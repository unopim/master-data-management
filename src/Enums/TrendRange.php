<?php

namespace Webkul\MasterDataManagement\Enums;

/**
 * The number of days the quality dashboard trend covers.
 */
enum TrendRange: int
{
    case Week = 7;
    case Month = 30;
    case Quarter = 90;

    /**
     * The translated name of the range.
     */
    public function label(): string
    {
        return trans('masterdata::app.quality-dashboard.ranges.'.$this->value);
    }
}
