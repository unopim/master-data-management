<?php

namespace Webkul\MasterDataManagement\Standardization\Transforms;

use Webkul\MasterDataManagement\Standardization\Contracts\ValueTransform;

/**
 * Reduces a phone number to the E.164 shape: a leading `+` (an international `00` prefix counts as one) followed
 * by its digits only. Numbers without a country prefix keep their digits, as no country can be inferred.
 */
class PhoneE164Transform implements ValueTransform
{
    /**
     * {@inheritdoc}
     */
    public function apply(string $value): string
    {
        $value = trim($value);

        $international = str_starts_with($value, '+') || str_starts_with($value, '00');

        $digits = preg_replace('/\D+/', '', $value) ?? '';

        if ($digits === '') {
            return '';
        }

        return $international ? '+'.(str_starts_with($value, '00') ? substr($digits, 2) : $digits) : $digits;
    }
}
