<?php

namespace Webkul\MasterDataManagement\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Fails unless the value compiles as a PCRE pattern, delimiters included.
 */
class CompilableRegex implements ValidationRule
{
    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (is_string($value) && @preg_match($value, '') === false) {
            $fail('masterdata::app.fields.validation.invalid-regex')->translate();
        }
    }
}
