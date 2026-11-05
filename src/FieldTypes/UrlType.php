<?php

namespace Webkul\MasterDataManagement\FieldTypes;

use Webkul\MasterDataManagement\Models\Field;

class UrlType extends TextType
{
    /**
     * {@inheritdoc}
     */
    public function code(): string
    {
        return 'url';
    }

    /**
     * {@inheritdoc}
     */
    public function rules(Field $field): array
    {
        return ['url', ...parent::rules($field)];
    }

    /**
     * {@inheritdoc}
     */
    public function cast(mixed $raw, Field $field): mixed
    {
        return is_string($raw) && trim($raw) !== '' ? trim($raw) : null;
    }

    /**
     * {@inheritdoc}
     */
    protected function maxLength(): int
    {
        return 2048;
    }
}
