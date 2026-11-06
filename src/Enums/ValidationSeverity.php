<?php

namespace Webkul\MasterDataManagement\Enums;

/**
 * Whether a failing validation rule refuses the write or only reports a data quality warning.
 */
enum ValidationSeverity: string
{
    case Block = 'block';
    case Warn = 'warn';
}
