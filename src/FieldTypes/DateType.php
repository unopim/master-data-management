<?php

namespace Webkul\MasterDataManagement\FieldTypes;

use DateTimeImmutable;
use Webkul\MasterDataManagement\Models\Field;
use Webkul\MasterDataManagement\ValueObjects\IndexValue;

class DateType extends AbstractFieldType
{
    /**
     * {@inheritdoc}
     */
    public function code(): string
    {
        return 'date';
    }

    /**
     * {@inheritdoc}
     */
    public function rules(Field $field): array
    {
        return ['date_format:Y-m-d'];
    }

    /**
     * {@inheritdoc}
     */
    public function cast(mixed $raw, Field $field): mixed
    {
        if (! is_string($raw)) {
            return null;
        }

        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $raw);

        return $date !== false && $date->format('Y-m-d') === $raw ? $raw : null;
    }

    /**
     * {@inheritdoc}
     */
    public function indexValue(mixed $value, Field $field): IndexValue
    {
        return new IndexValue(date: $this->cast($value, $field));
    }
}
