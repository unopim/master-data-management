<?php

namespace Webkul\MasterDataManagement\Standardization\Transforms;

use Webkul\MasterDataManagement\Standardization\Contracts\ValueTransform;

/**
 * Trims an e-mail address and lower-cases it, so addresses differing only in case compare equal.
 */
class EmailNormalizeTransform implements ValueTransform
{
    /**
     * {@inheritdoc}
     */
    public function apply(string $value): string
    {
        return mb_strtolower(trim($value), 'UTF-8');
    }
}
