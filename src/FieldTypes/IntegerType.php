<?php

namespace Webkul\MasterDataManagement\FieldTypes;

use Webkul\MasterDataManagement\Models\Field;
use Webkul\MasterDataManagement\ValueObjects\IndexValue;

class IntegerType extends AbstractFieldType
{
    /**
     * {@inheritdoc}
     */
    public function code(): string
    {
        return 'integer';
    }

    /**
     * {@inheritdoc}
     */
    public function rules(Field $field): array
    {
        return ['integer', ...$this->configuredRules($field)];
    }

    /**
     * {@inheritdoc}
     */
    public function cast(mixed $raw, Field $field): mixed
    {
        $value = filter_var(is_string($raw) ? trim($raw) : $raw, FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE);

        return is_bool($raw) ? null : $value;
    }

    /**
     * {@inheritdoc}
     */
    public function indexValue(mixed $value, Field $field): IndexValue
    {
        return new IndexValue(numeric: $this->numericOrNull($value));
    }
}
