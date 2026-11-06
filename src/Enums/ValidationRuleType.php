<?php

namespace Webkul\MasterDataManagement\Enums;

/**
 * The built-in validation rule types; packages register more under `masterdata.validation.rule_types`.
 */
enum ValidationRuleType: string
{
    case Regex = 'regex';
    case Min = 'min';
    case Max = 'max';
    case Length = 'length';
    case CrossField = 'cross_field';
    case OneOfCodes = 'one_of_codes';
    case DateRange = 'date_range';
}
