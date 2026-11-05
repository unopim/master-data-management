<?php

namespace Webkul\MasterDataManagement\FieldTypes;

use Closure;
use Webkul\MasterDataManagement\Contracts\CodeLookup;
use Webkul\MasterDataManagement\Models\Field;

class SelectType extends AbstractFieldType
{
    public function __construct(protected CodeLookup $codeLookup) {}

    /**
     * {@inheritdoc}
     */
    public function code(): string
    {
        return 'select';
    }

    /**
     * {@inheritdoc}
     */
    public function rules(Field $field): array
    {
        return ['bail', 'string', $this->codeRule($field)];
    }

    /**
     * {@inheritdoc}
     */
    public function cast(mixed $raw, Field $field): mixed
    {
        return is_string($raw) && $raw !== '' ? $raw : null;
    }

    /**
     * A rule that fails unless every code of the value is in the field's code list.
     */
    protected function codeRule(Field $field): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($field): void {
            foreach ((array) $value as $code) {
                if (! $this->isValidCode($field, $code)) {
                    $fail('masterdata::app.fields.validation.invalid-code')->translate();

                    return;
                }
            }
        };
    }

    /**
     * Whether the code is a string in the field's code list.
     */
    protected function isValidCode(Field $field, mixed $code): bool
    {
        return is_string($code)
            && $field->code_list_id !== null
            && $this->codeLookup->isValid($field->code_list_id, $code);
    }
}
