<?php

namespace Webkul\MasterDataManagement\Validation\Rules;

use Webkul\MasterDataManagement\Models\Field;

/**
 * Passes values found in the configured list of codes, compared exactly; a list value passes when every entry does.
 */
class OneOfCodesRule extends AbstractValueRule
{
    /**
     * The most codes one rule lists.
     */
    public const int MAX_CODES = 500;

    /**
     * Read codes sent as text, separated by commas or new lines, as a list without blanks.
     *
     * {@inheritdoc}
     */
    public function prepare(array $params): array
    {
        $codes = $params['codes'] ?? null;

        if (is_string($codes)) {
            $codes = preg_split('/[,\r\n]+/', $codes) ?: [];
        }

        if (is_array($codes)) {
            $params['codes'] = array_values(array_filter(
                array_map(fn (mixed $code): mixed => is_string($code) ? trim($code) : $code, $codes),
                fn (mixed $code): bool => $code !== '' && $code !== null,
            ));
        }

        return $params;
    }

    /**
     * {@inheritdoc}
     */
    public function paramRules(Field $field, array $params): array
    {
        return [
            'params.codes'   => ['required', 'array', 'min:1', 'max:'.self::MAX_CODES],
            'params.codes.*' => ['string', 'max:255', 'distinct'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function passesOne(mixed $value, array $params, array $siblings): bool
    {
        return is_scalar($value) && in_array((string) $value, array_map(strval(...), (array) ($params['codes'] ?? [])), true);
    }
}
