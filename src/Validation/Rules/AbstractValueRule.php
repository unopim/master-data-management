<?php

namespace Webkul\MasterDataManagement\Validation\Rules;

use Webkul\MasterDataManagement\Validation\Contracts\ValueRule;

/**
 * Checks a list value, such as a multiselect's codes, element by element and leaves the params as submitted.
 */
abstract class AbstractValueRule implements ValueRule
{
    /**
     * Whether one scalar value passes the rule.
     *
     * @param  array<string, mixed>  $params
     * @param  array<array-key, mixed>  $siblings
     */
    abstract protected function passesOne(mixed $value, array $params, array $siblings): bool;

    /**
     * {@inheritdoc}
     */
    public function prepare(array $params): array
    {
        return $params;
    }

    /**
     * {@inheritdoc}
     */
    public function passes(mixed $value, array $params, array $siblings): bool
    {
        return array_all(is_array($value) ? $value : [$value], fn ($one): bool => $this->passesOne($one, $params, $siblings));
    }

    /**
     * {@inheritdoc}
     */
    public function placeholders(array $params): array
    {
        return array_map(
            fn (mixed $param): string => is_array($param) ? implode(', ', array_map(strval(...), $param)) : (string) $param,
            array_filter($params, fn (mixed $param): bool => is_scalar($param) || is_array($param)),
        );
    }

    /**
     * Read a numeric param submitted as a string as the number it is.
     *
     * @param  array<string, mixed>  $params
     * @param  list<string>  $keys
     * @return array<string, mixed>
     */
    protected function numericParams(array $params, array $keys): array
    {
        foreach ($keys as $key) {
            if (is_string($params[$key] ?? null) && is_numeric(trim($params[$key]))) {
                $params[$key] = trim($params[$key]) + 0;
            }
        }

        return $params;
    }
}
