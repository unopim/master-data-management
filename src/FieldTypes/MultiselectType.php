<?php

namespace Webkul\MasterDataManagement\FieldTypes;

use Webkul\MasterDataManagement\Models\Field;

class MultiselectType extends SelectType
{
    /**
     * {@inheritdoc}
     */
    public function code(): string
    {
        return 'multiselect';
    }

    /**
     * {@inheritdoc}
     */
    public function rules(Field $field): array
    {
        return ['bail', 'array', $this->codeRule($field)];
    }

    /**
     * Stored as a list of distinct, non-empty codes.
     *
     * {@inheritdoc}
     */
    public function cast(mixed $raw, Field $field): mixed
    {
        if (! is_array($raw)) {
            return null;
        }

        $codes = array_values(array_unique(array_filter(
            $raw,
            fn (mixed $code): bool => is_string($code) && $code !== ''
        )));

        return $codes === [] ? null : $codes;
    }
}
