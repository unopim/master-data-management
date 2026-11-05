<?php

namespace Webkul\MasterDataManagement\FieldTypes;

use Webkul\MasterDataManagement\Models\Field;
use Webkul\MasterDataManagement\ValueObjects\IndexValue;

class BooleanType extends AbstractFieldType
{
    /**
     * {@inheritdoc}
     */
    public function code(): string
    {
        return 'boolean';
    }

    /**
     * {@inheritdoc}
     */
    public function rules(Field $field): array
    {
        return ['boolean'];
    }

    /**
     * Accepts what the `boolean` rule accepts: true, false, 1, 0, "1" and "0".
     *
     * {@inheritdoc}
     */
    public function cast(mixed $raw, Field $field): mixed
    {
        return match (true) {
            in_array($raw, [true, 1, '1'], true)  => true,
            in_array($raw, [false, 0, '0'], true) => false,
            default                               => null,
        };
    }

    /**
     * {@inheritdoc}
     */
    public function indexValue(mixed $value, Field $field): IndexValue
    {
        if (! is_bool($value)) {
            return new IndexValue;
        }

        $flag = $value ? '1' : '0';

        return new IndexValue(text: $flag, numeric: $flag);
    }
}
