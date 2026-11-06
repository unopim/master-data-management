<?php

namespace Webkul\MasterDataManagement\Standardization\Contracts;

/**
 * Rewrites a field value into its standard form before the value is validated and stored; registered under
 * `masterdata.standardization.transforms`.
 */
interface ValueTransform
{
    /**
     * The standardized form of the value.
     */
    public function apply(string $value): string;
}
