<?php

namespace Webkul\MasterDataManagement\Enums;

enum CodeStrategy: string
{
    case Pattern = 'pattern';
    case IdentifierFields = 'identifier_fields';
    case SourceId = 'source_id';
}
