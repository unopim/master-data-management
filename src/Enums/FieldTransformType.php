<?php

namespace Webkul\MasterDataManagement\Enums;

/**
 * The built-in field transforms; packages register more under `masterdata.standardization.transforms`.
 */
enum FieldTransformType: string
{
    case Trim = 'trim';
    case Lower = 'lower';
    case Upper = 'upper';
    case Title = 'title';
    case CollapseSpaces = 'collapse_spaces';
    case EmailNormalize = 'email_normalize';
    case PhoneE164 = 'phone_e164';
    case StripNonAlnum = 'strip_non_alnum';
}
