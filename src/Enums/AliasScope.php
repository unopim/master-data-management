<?php

namespace Webkul\MasterDataManagement\Enums;

enum AliasScope: string
{
    case CodeList = 'code_list';
    case Field = 'field';
    case Global = 'global';
}
