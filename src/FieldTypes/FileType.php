<?php

namespace Webkul\MasterDataManagement\FieldTypes;

use Webkul\MasterDataManagement\Models\Field;

class FileType extends AbstractFieldType
{
    /**
     * {@inheritdoc}
     */
    public function code(): string
    {
        return 'file';
    }

    /**
     * The value is the stored path of an uploaded file.
     *
     * {@inheritdoc}
     */
    public function rules(Field $field): array
    {
        return ['string', 'max:2048'];
    }

    /**
     * {@inheritdoc}
     */
    public function cast(mixed $raw, Field $field): mixed
    {
        return is_string($raw) && $raw !== '' ? $raw : null;
    }
}
