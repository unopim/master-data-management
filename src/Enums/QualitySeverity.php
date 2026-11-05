<?php

namespace Webkul\MasterDataManagement\Enums;

enum QualitySeverity: string
{
    case Error = 'error';
    case Warning = 'warning';
    case Info = 'info';
}
