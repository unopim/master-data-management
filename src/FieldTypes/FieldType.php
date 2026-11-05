<?php

namespace Webkul\MasterDataManagement\FieldTypes;

use Webkul\MasterDataManagement\Models\Field;
use Webkul\MasterDataManagement\ValueObjects\IndexValue;

interface FieldType
{
    /**
     * The code the type is registered under in `masterdata.field_types`.
     */
    public function code(): string;

    /**
     * The validation rules of one value of the field, without required or unique.
     *
     * @return array<int, mixed>
     */
    public function rules(Field $field): array;

    /**
     * Convert a validated raw value into its stored form; null when it cannot be converted.
     */
    public function cast(mixed $raw, Field $field): mixed;

    /**
     * Whether the value is kept in the record's `values` JSON.
     */
    public function storesInValues(): bool;

    /**
     * The searchable form of a stored value.
     */
    public function indexValue(mixed $value, Field $field): IndexValue;
}
