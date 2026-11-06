<?php

namespace Webkul\MasterDataManagement\Repositories;

use Webkul\Core\Eloquent\Repository;
use Webkul\MasterDataManagement\Contracts\FieldTransform;
use Webkul\MasterDataManagement\Repositories\Concerns\MemoisesFieldRules;

class FieldTransformRepository extends Repository
{
    use MemoisesFieldRules;

    /**
     * The most field transforms one entity type holds.
     */
    public const int MAX_ROWS = 500;

    /**
     * Get model class name.
     */
    public function model(): string
    {
        return FieldTransform::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function memoPrefix(): string
    {
        return 'masterdata.field_transforms.';
    }
}
