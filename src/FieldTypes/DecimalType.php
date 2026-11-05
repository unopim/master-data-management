<?php

namespace Webkul\MasterDataManagement\FieldTypes;

use Webkul\MasterDataManagement\Models\Field;
use Webkul\MasterDataManagement\ValueObjects\IndexValue;

class DecimalType extends AbstractFieldType
{
    /**
     * {@inheritdoc}
     */
    public function code(): string
    {
        return 'decimal';
    }

    /**
     * {@inheritdoc}
     */
    public function rules(Field $field): array
    {
        return ['numeric', ...$this->configuredRules($field)];
    }

    /**
     * Stored as a numeric string so no precision is lost to floats.
     *
     * {@inheritdoc}
     */
    public function cast(mixed $raw, Field $field): mixed
    {
        return is_bool($raw) ? null : $this->numericOrNull($raw);
    }

    /**
     * {@inheritdoc}
     */
    public function indexValue(mixed $value, Field $field): IndexValue
    {
        return new IndexValue(numeric: $this->numericOrNull($value));
    }
}
