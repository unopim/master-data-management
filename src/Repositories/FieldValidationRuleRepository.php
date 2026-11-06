<?php

namespace Webkul\MasterDataManagement\Repositories;

use Webkul\Core\Eloquent\Repository;
use Webkul\MasterDataManagement\Contracts\FieldValidationRule;
use Webkul\MasterDataManagement\Repositories\Concerns\MemoisesFieldRules;

class FieldValidationRuleRepository extends Repository
{
    use MemoisesFieldRules;

    /**
     * The most validation rules one entity type holds.
     */
    public const int MAX_ROWS = 500;

    /**
     * Get model class name.
     */
    public function model(): string
    {
        return FieldValidationRule::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function memoPrefix(): string
    {
        return 'masterdata.validation_rules.';
    }
}
