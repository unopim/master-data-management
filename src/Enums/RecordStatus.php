<?php

namespace Webkul\MasterDataManagement\Enums;

enum RecordStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Retired = 'retired';
    case Merged = 'merged';
    case Erased = 'erased';
}
