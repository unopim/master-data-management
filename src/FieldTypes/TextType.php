<?php

namespace Webkul\MasterDataManagement\FieldTypes;

use Closure;
use Webkul\MasterDataManagement\Models\Field;

class TextType extends AbstractFieldType
{
    /**
     * {@inheritdoc}
     */
    public function code(): string
    {
        return 'text';
    }

    /**
     * {@inheritdoc}
     */
    public function rules(Field $field): array
    {
        return ['string', 'max:'.$this->maxLength(), ...$this->maxLengthRule($field), ...$this->configuredRules($field)];
    }

    /**
     * {@inheritdoc}
     */
    public function cast(mixed $raw, Field $field): mixed
    {
        return is_string($raw) && $raw !== '' ? $raw : null;
    }

    /**
     * Checks the value is numeric without the `numeric` rule, which would make size rules compare values, not lengths.
     */
    protected function numberRule(): mixed
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if (! is_numeric($value)) {
                $fail('validation.numeric')->translate();
            }
        };
    }

    /**
     * The longest value the type accepts.
     */
    protected function maxLength(): int
    {
        return 255;
    }
}
