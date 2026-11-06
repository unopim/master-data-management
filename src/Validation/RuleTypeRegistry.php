<?php

namespace Webkul\MasterDataManagement\Validation;

use Webkul\MasterDataManagement\Support\ConfigRegistry;
use Webkul\MasterDataManagement\Validation\Contracts\ValueRule;

/**
 * The validation rule types of `masterdata.validation.rule_types`, labelled from `masterdata::app.validation-rules.types`.
 *
 * @extends ConfigRegistry<ValueRule>
 */
class RuleTypeRegistry extends ConfigRegistry
{
    /**
     * {@inheritdoc}
     */
    protected function configKey(): string
    {
        return 'masterdata.validation.rule_types';
    }

    /**
     * {@inheritdoc}
     */
    protected function contract(): string
    {
        return ValueRule::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function labelPrefix(): string
    {
        return 'masterdata::app.validation-rules.types';
    }
}
