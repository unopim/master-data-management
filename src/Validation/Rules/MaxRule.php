<?php

namespace Webkul\MasterDataManagement\Validation\Rules;

use Webkul\MasterDataManagement\Models\Field;

/**
 * Passes numeric values of at most the configured value.
 */
class MaxRule extends AbstractValueRule
{
    /**
     * {@inheritdoc}
     */
    public function prepare(array $params): array
    {
        return $this->numericParams($params, ['value']);
    }

    /**
     * {@inheritdoc}
     */
    public function paramRules(Field $field, array $params): array
    {
        return [
            'params.value' => ['required', 'numeric'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function passesOne(mixed $value, array $params, array $siblings): bool
    {
        return is_numeric($value) && is_numeric($params['value'] ?? null) && $value + 0 <= $params['value'] + 0;
    }
}
