<?php

namespace Webkul\MasterDataManagement\Validation\Contracts;

use Webkul\MasterDataManagement\Models\Field;

/**
 * A configurable check of one field value; registered under `masterdata.validation.rule_types`.
 */
interface ValueRule
{
    /**
     * Coerce the submitted params before they are validated, such as a comma-separated list into an array.
     *
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    public function prepare(array $params): array;

    /**
     * The validation rules of the params, keyed by full attribute (`params.pattern`); their top-level keys are the
     * params stored on the rule.
     *
     * @param  array<string, mixed>  $params  The prepared params.
     * @return array<string, array<int, mixed>>
     */
    public function paramRules(Field $field, array $params): array;

    /**
     * Whether the value, never empty, passes the rule.
     *
     * @param  array<string, mixed>  $params  The params stored on the rule.
     * @param  array<array-key, mixed>  $siblings  The other values of the record the value is checked with.
     */
    public function passes(mixed $value, array $params, array $siblings): bool;

    /**
     * The placeholders of the rule's default message.
     *
     * @param  array<string, mixed>  $params
     * @return array<string, string>
     */
    public function placeholders(array $params): array;
}
