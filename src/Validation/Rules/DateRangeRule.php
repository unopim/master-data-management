<?php

namespace Webkul\MasterDataManagement\Validation\Rules;

use Closure;
use Webkul\MasterDataManagement\Models\Field;

/**
 * Passes dates on or after `after` and on or before `before`; either bound is a date (`2026-01-01`) or a relative
 * expression PHP understands (`today`, `-18 years`), read when the value is checked.
 */
class DateRangeRule extends AbstractValueRule
{
    /**
     * {@inheritdoc}
     */
    public function prepare(array $params): array
    {
        return array_filter($params, fn (mixed $param): bool => $param !== null && $param !== '');
    }

    /**
     * {@inheritdoc}
     */
    public function paramRules(Field $field, array $params): array
    {
        $parses = function (string $attribute, mixed $value, Closure $fail): void {
            if (! is_string($value) || strtotime($value) === false) {
                $fail('masterdata::app.validation-rules.validation.invalid-date')->translate();
            }
        };

        return [
            'params.after'  => ['nullable', 'string', 'max:64', 'required_without:params.before', $parses],
            'params.before' => ['nullable', 'string', 'max:64', $parses],
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function passesOne(mixed $value, array $params, array $siblings): bool
    {
        $time = is_string($value) ? strtotime($value) : false;

        if ($time === false) {
            return false;
        }

        $after = is_string($params['after'] ?? null) ? strtotime($params['after']) : false;
        $before = is_string($params['before'] ?? null) ? strtotime($params['before']) : false;

        return ($after === false || $time >= $after) && ($before === false || $time <= $before);
    }
}
