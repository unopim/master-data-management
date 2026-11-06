<?php

namespace Webkul\MasterDataManagement\Standardization\Transforms;

use Webkul\MasterDataManagement\Standardization\Contracts\ValueTransform;

/**
 * Removes leading and trailing whitespace, Unicode spaces such as the no-break space included.
 */
class TrimTransform implements ValueTransform
{
    /**
     * {@inheritdoc}
     */
    public function apply(string $value): string
    {
        return preg_replace('/^[\s\p{Z}]+|[\s\p{Z}]+$/u', '', $value) ?? $value;
    }
}
