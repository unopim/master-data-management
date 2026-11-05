<?php

namespace Webkul\MasterDataManagement\FieldTypes;

use Closure;
use Webkul\MasterDataManagement\Models\Field;
use Webkul\MasterDataManagement\ValueObjects\IndexValue;

class MeasurementType extends AbstractFieldType
{
    /**
     * {@inheritdoc}
     */
    public function code(): string
    {
        return 'measurement';
    }

    /**
     * {@inheritdoc}
     */
    public function rules(Field $field): array
    {
        return [
            'bail',
            'array:value,unit',
            function (string $attribute, mixed $value, Closure $fail): void {
                if ($this->normalize($value) === null) {
                    $fail('masterdata::app.fields.validation.invalid-measurement')->translate();
                }
            },
        ];
    }

    /**
     * Stored as `{value, unit}` with the value as a numeric string.
     *
     * {@inheritdoc}
     */
    public function cast(mixed $raw, Field $field): mixed
    {
        return $this->normalize($raw);
    }

    /**
     * {@inheritdoc}
     */
    public function indexValue(mixed $value, Field $field): IndexValue
    {
        return new IndexValue(numeric: $this->normalize($value)['value'] ?? null);
    }

    /**
     * The value as `{value, unit}`, or null when either part is missing or invalid.
     *
     * @return array{value: string, unit: string}|null
     */
    protected function normalize(mixed $raw): ?array
    {
        if (! is_array($raw)) {
            return null;
        }

        $value = is_bool($raw['value'] ?? null) ? null : $this->numericOrNull($raw['value'] ?? null);

        $unit = $raw['unit'] ?? null;

        if ($value === null || ! is_string($unit) || trim($unit) === '') {
            return null;
        }

        return ['value' => $value, 'unit' => trim($unit)];
    }
}
