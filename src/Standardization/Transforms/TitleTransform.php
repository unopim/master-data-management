<?php

namespace Webkul\MasterDataManagement\Standardization\Transforms;

use Webkul\MasterDataManagement\Standardization\Contracts\ValueTransform;

/**
 * Capitalises the first letter of every word and lower-cases the rest.
 */
class TitleTransform implements ValueTransform
{
    /**
     * {@inheritdoc}
     */
    public function apply(string $value): string
    {
        return mb_convert_case(mb_strtolower($value, 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
    }
}
