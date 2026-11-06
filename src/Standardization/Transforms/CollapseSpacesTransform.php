<?php

namespace Webkul\MasterDataManagement\Standardization\Transforms;

use Webkul\MasterDataManagement\Standardization\Contracts\ValueTransform;

/**
 * Turns every run of whitespace into a single space.
 */
class CollapseSpacesTransform implements ValueTransform
{
    /**
     * {@inheritdoc}
     */
    public function apply(string $value): string
    {
        return preg_replace('/\s+/u', ' ', $value) ?? $value;
    }
}
