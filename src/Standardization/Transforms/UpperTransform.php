<?php

namespace Webkul\MasterDataManagement\Standardization\Transforms;

use Webkul\MasterDataManagement\Standardization\Contracts\ValueTransform;

/**
 * Upper-cases the value.
 */
class UpperTransform implements ValueTransform
{
    /**
     * {@inheritdoc}
     */
    public function apply(string $value): string
    {
        return mb_strtoupper($value, 'UTF-8');
    }
}
