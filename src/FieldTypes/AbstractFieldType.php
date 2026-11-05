<?php

namespace Webkul\MasterDataManagement\FieldTypes;

use Webkul\Attribute\Rules\ValidationTypes;
use Webkul\Core\Rules\Decimal;
use Webkul\MasterDataManagement\Models\Field;
use Webkul\MasterDataManagement\ValueObjects\IndexValue;

abstract class AbstractFieldType implements FieldType
{
    /**
     * {@inheritdoc}
     */
    public function storesInValues(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function indexValue(mixed $value, Field $field): IndexValue
    {
        return new IndexValue(text: is_scalar($value) && $value !== '' ? (string) $value : null);
    }

    /**
     * The rules the field's `validation` JSON adds: type, regex, min and max.
     *
     * @return array<int, mixed>
     */
    protected function configuredRules(Field $field): array
    {
        $validation = $field->validation ?? [];

        $rules = [];

        $type = $validation['type'] ?? null;

        if (in_array($type, ValidationTypes::VALIDATION_TYPES, true)) {
            $rule = match ($type) {
                'number'  => $this->numberRule(),
                'decimal' => new Decimal,
                'regex'   => null,
                default   => $type,
            };

            if ($rule !== null) {
                $rules[] = $rule;
            }
        }

        if (is_string($validation['regex'] ?? null) && $validation['regex'] !== '') {
            $rules[] = 'regex:'.$validation['regex'];
        }

        foreach (['min', 'max'] as $bound) {
            if (is_numeric($validation[$bound] ?? null)) {
                $rules[] = $bound.':'.$validation[$bound];
            }
        }

        return $rules;
    }

    /**
     * The rule for `validation.type = number`.
     */
    protected function numberRule(): mixed
    {
        return 'numeric';
    }

    /**
     * The `max_length` rule of the field's `validation` JSON, for string types.
     *
     * @return array<int, string>
     */
    protected function maxLengthRule(Field $field): array
    {
        $maxLength = filter_var($field->validation['max_length'] ?? null, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);

        return $maxLength === false ? [] : ['max:'.$maxLength];
    }

    /**
     * The value as a string, or null when it is not a non-empty scalar.
     */
    protected function stringOrNull(mixed $raw): ?string
    {
        if (! is_scalar($raw) || is_bool($raw)) {
            return null;
        }

        $value = (string) $raw;

        return $value === '' ? null : $value;
    }

    /**
     * The value as a canonical numeric string, or null when it is not numeric.
     */
    protected function numericOrNull(mixed $raw): ?string
    {
        if (is_string($raw)) {
            $raw = trim($raw);
        }

        if (! is_numeric($raw)) {
            return null;
        }

        return is_string($raw) ? $raw : (string) $raw;
    }
}
