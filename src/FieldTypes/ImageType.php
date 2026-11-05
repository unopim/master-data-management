<?php

namespace Webkul\MasterDataManagement\FieldTypes;

class ImageType extends FileType
{
    /**
     * {@inheritdoc}
     */
    public function code(): string
    {
        return 'image';
    }
}
