<?php

namespace Webkul\MasterDataManagement\Validation\Rules;

use Webkul\MasterDataManagement\Models\Field;

/**
 * Passes values whose length in characters, or a list's number of entries, lies within the configured bounds.
 */
class LengthRule extends AbstractValueRule
{
    /**
     * {@inheritdoc}
     */
    public function prepare(array $params): array
    {
        return array_filter($this->numericParams($params, ['min', 'max']), fn (mixed $param): bool => $param !== null && $param !== '');
    }

    /**
     * {@inheritdoc}
     */
    public function paramRules(Field $field, array $params): array
    {
        return [
            'params.min' => ['nullable', 'integer', 'min:0', 'max:65535', 'required_without:params.max'],
            'params.max' => ['nullable', 'integer', 'min:1', 'max:65535', ...(is_numeric($params['min'] ?? null) ? ['gte:params.min'] : [])],
        ];
    }

    /**
     * Measure a list by its entries rather than each entry by its characters.
     *
     * {@inheritdoc}
     */
    public function passes(mixed $value, array $params, array $siblings): bool
    {
        return $this->passesOne($value, $params, $siblings);
    }

    /**
     * {@inheritdoc}
     */
    public function placeholders(array $params): array
    {
        return parent::placeholders($params + ['min' => 0, 'max' => '∞']);
    }

    /**
     * {@inheritdoc}
     */
    protected function passesOne(mixed $value, array $params, array $siblings): bool
    {
        if (! is_array($value) && ! is_scalar($value)) {
            return false;
        }

        $length = is_array($value) ? count($value) : mb_strlen((string) $value);

        return (! is_numeric($params['min'] ?? null) || $length >= (int) $params['min'])
            && (! is_numeric($params['max'] ?? null) || $length <= (int) $params['max']);
    }
}
