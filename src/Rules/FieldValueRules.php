<?php

namespace Webkul\MasterDataManagement\Rules;

use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Webkul\MasterDataManagement\Models\Field;
use Webkul\MasterDataManagement\Models\FieldValidationRule;
use Webkul\MasterDataManagement\Validation\ValueRuleChecker;

/**
 * Fails a value of `values.common.{code}` or `values.locale_specific.{locale}.{code}` with the message of every
 * blocking validation rule of its field it breaks, checked against the other values under validation.
 */
class FieldValueRules implements DataAwareRule, ValidationRule
{
    /**
     * @var array<array-key, mixed>
     */
    protected array $data = [];

    /**
     * @param  list<FieldValidationRule>  $rules
     */
    public function __construct(
        protected Field $field,
        protected array $rules,
        protected ValueRuleChecker $checker,
    ) {}

    /**
     * Set the data under validation.
     *
     * @param  array<array-key, mixed>  $data
     */
    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $segments = explode('.', $attribute);

        $locale = ($segments[1] ?? null) === 'locale_specific' ? ($segments[2] ?? null) : null;

        $siblings = $this->checker->siblings(is_array($this->data['values'] ?? null) ? $this->data['values'] : [], $locale);

        foreach ($this->rules as $rule) {
            $message = $this->checker->failure($rule, $this->field, $value, $siblings);

            if ($message !== null) {
                $fail($message);
            }
        }
    }
}
