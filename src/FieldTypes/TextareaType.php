<?php

namespace Webkul\MasterDataManagement\FieldTypes;

class TextareaType extends TextType
{
    /**
     * {@inheritdoc}
     */
    public function code(): string
    {
        return 'textarea';
    }

    /**
     * {@inheritdoc}
     */
    protected function maxLength(): int
    {
        return 65535;
    }
}
