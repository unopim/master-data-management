<?php

namespace Webkul\MasterDataManagement\Http\Requests\Api\Concerns;

use Closure;

/**
 * Bounds the `values` payload (`common` and `locale_specific` sections) an API caller sends, before the record writer
 * checks each value against its field. Field rules stay in the writer, the one place that validates them.
 */
trait ValidatesValuePayload
{
    /**
     * The most value keys one section, and the most locales the payload, may carry.
     */
    public const int MAX_KEYS = 100;

    /**
     * The longest text one value, or one item of a list value, may hold.
     */
    public const int MAX_VALUE_LENGTH = 2000;

    /**
     * The shape rules of a `values` payload.
     *
     * @return array<string, array<int, mixed>>
     */
    protected function valuePayloadRules(): array
    {
        return [
            'values'                     => ['sometimes', 'array:common,locale_specific'],
            'values.common'              => ['sometimes', 'array', $this->maxKeys()],
            'values.common.*'            => [$this->boundedValue()],
            'values.locale_specific'     => ['sometimes', 'array', $this->maxKeys()],
            'values.locale_specific.*'   => ['array', $this->maxKeys()],
            'values.locale_specific.*.*' => [$this->boundedValue()],
        ];
    }

    /**
     * Refuse an array with more keys than one payload section may carry.
     */
    protected function maxKeys(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if (is_array($value) && count($value) > self::MAX_KEYS) {
                $fail(trans('masterdata::app.api.validation.too-many-values', ['attribute' => $attribute, 'max' => self::MAX_KEYS]));
            }
        };
    }

    /**
     * Accept a scalar or a flat list of scalars, each no longer than the value length limit.
     */
    protected function boundedValue(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            $items = is_array($value) ? $value : [$value];

            if (count($items) > self::MAX_KEYS) {
                $fail(trans('masterdata::app.api.validation.too-many-values', ['attribute' => $attribute, 'max' => self::MAX_KEYS]));

                return;
            }

            foreach ($items as $item) {
                if ($item !== null && ! is_scalar($item)) {
                    $fail(trans('masterdata::app.api.validation.invalid-value', ['attribute' => $attribute]));

                    return;
                }

                if (mb_strlen((string) $item) > self::MAX_VALUE_LENGTH) {
                    $fail(trans('masterdata::app.api.validation.value-too-long', ['attribute' => $attribute, 'max' => self::MAX_VALUE_LENGTH]));

                    return;
                }
            }
        };
    }
}
