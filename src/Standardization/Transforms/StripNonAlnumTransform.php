<?php

namespace Webkul\MasterDataManagement\Standardization\Transforms;

use Webkul\MasterDataManagement\Standardization\Contracts\ValueTransform;

/**
 * Removes every character that is neither a letter nor a digit, in any script.
 */
class StripNonAlnumTransform implements ValueTransform
{
    /**
     * {@inheritdoc}
     */
    public function apply(string $value): string
    {
        return preg_replace('/[^\p{L}\p{N}]+/u', '', $value) ?? $value;
    }
}
