<?php

namespace Webkul\MasterDataManagement\FieldTypes;

use Webkul\MasterDataManagement\Models\Field;

class EmailType extends TextType
{
    /**
     * {@inheritdoc}
     */
    public function code(): string
    {
        return 'email';
    }

    /**
     * {@inheritdoc}
     */
    public function rules(Field $field): array
    {
        return ['email', ...parent::rules($field)];
    }

    /**
     * {@inheritdoc}
     */
    public function cast(mixed $raw, Field $field): mixed
    {
        return is_string($raw) && trim($raw) !== '' ? trim($raw) : null;
    }
}
