<?php

namespace Webkul\MasterDataManagement\Enums;

enum SourceSystemType: string
{
    case Erp = 'erp';
    case Mes = 'mes';
    case Cmms = 'cmms';
    case Crm = 'crm';
    case Other = 'other';
}
