<?php

namespace Webkul\MasterDataManagement\Standardization\Transforms;

use Webkul\MasterDataManagement\Standardization\Contracts\ValueTransform;

/**
 * Lower-cases the value.
 */
class LowerTransform implements ValueTransform
{
    /**
     * {@inheritdoc}
     */
    public function apply(string $value): string
    {
        return mb_strtolower($value, 'UTF-8');
    }
}
