<?php

namespace Webkul\MasterDataManagement\Validation\Rules;

use Webkul\MasterDataManagement\Models\Field;
use Webkul\MasterDataManagement\Rules\CompilableRegex;

/**
 * Passes values matching a PCRE pattern, delimiters included (`/^[A-Z]{2}\d+$/`).
 */
class RegexRule extends AbstractValueRule
{
    /**
     * {@inheritdoc}
     */
    public function paramRules(Field $field, array $params): array
    {
        return [
            'params.pattern' => ['required', 'string', 'max:255', new CompilableRegex],
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function passesOne(mixed $value, array $params, array $siblings): bool
    {
        $pattern = $params['pattern'] ?? null;

        return is_scalar($value) && is_string($pattern) && @preg_match($pattern, (string) $value) === 1;
    }
}
