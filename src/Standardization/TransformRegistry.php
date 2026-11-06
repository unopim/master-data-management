<?php

namespace Webkul\MasterDataManagement\Standardization;

use Webkul\MasterDataManagement\Standardization\Contracts\ValueTransform;
use Webkul\MasterDataManagement\Support\ConfigRegistry;

/**
 * The field transforms of `masterdata.standardization.transforms`, labelled from `masterdata::app.field-transforms.types`.
 *
 * @extends ConfigRegistry<ValueTransform>
 */
class TransformRegistry extends ConfigRegistry
{
    /**
     * {@inheritdoc}
     */
    protected function configKey(): string
    {
        return 'masterdata.standardization.transforms';
    }

    /**
     * {@inheritdoc}
     */
    protected function contract(): string
    {
        return ValueTransform::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function labelPrefix(): string
    {
        return 'masterdata::app.field-transforms.types';
    }
}
