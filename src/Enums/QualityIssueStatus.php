<?php

namespace Webkul\MasterDataManagement\Enums;

enum QualityIssueStatus: string
{
    case Open = 'open';
    case Resolved = 'resolved';
    case Ignored = 'ignored';
}
