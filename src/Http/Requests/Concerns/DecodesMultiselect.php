<?php

namespace Webkul\MasterDataManagement\Http\Requests\Concerns;

/**
 * The admin multiselect control posts its value as a JSON list or a comma separated string, not as an array.
 */
trait DecodesMultiselect
{
    /**
     * Decode a submitted multiselect value into a list; arrays and other values pass through unchanged.
     */
    protected function decodeMultiselect(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        $decoded = json_decode($value, true);

        return is_array($decoded)
            ? array_values($decoded)
            : array_values(array_filter(array_map(trim(...), explode(',', $value)), fn (string $item): bool => $item !== ''));
    }
}
