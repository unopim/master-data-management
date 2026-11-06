<?php

namespace Webkul\MasterDataManagement\Validation\Rules;

use Illuminate\Validation\Rule;
use Webkul\MasterDataManagement\Enums\CrossFieldOperator;
use Webkul\MasterDataManagement\Models\Field;
use Webkul\MasterDataManagement\Repositories\FieldRepository;

/**
 * Compares the value with the value of another field of the same record: as numbers when both are numeric, as dates
 * when both parse as dates, else as text. The rule passes while the other field holds no value.
 */
class CrossFieldRule extends AbstractValueRule
{
    public function __construct(protected FieldRepository $fieldRepository) {}

    /**
     * {@inheritdoc}
     */
    public function paramRules(Field $field, array $params): array
    {
        $others = $this->fieldRepository->activeFor((int) $field->entity_type_id)
            ->reject(fn (Field $other): bool => $other->id === $field->id)
            ->pluck('code')
            ->map(strval(...))
            ->values()
            ->all();

        return [
            'params.field'    => ['required', 'string', Rule::in($others)],
            'params.operator' => ['required', 'string', Rule::enum(CrossFieldOperator::class)],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function placeholders(array $params): array
    {
        $operator = CrossFieldOperator::tryFrom((string) ($params['operator'] ?? ''));

        return [
            'field'    => (string) ($params['field'] ?? ''),
            'operator' => $operator === null ? '' : trans('masterdata::app.validation-rules.operators.'.$operator->value),
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function passesOne(mixed $value, array $params, array $siblings): bool
    {
        $operator = CrossFieldOperator::tryFrom((string) ($params['operator'] ?? ''));

        $other = $siblings[(string) ($params['field'] ?? '')] ?? null;

        if ($operator === null) {
            return false;
        }

        if ($other === null || $other === '' || ! is_scalar($other)) {
            return true;
        }

        return is_scalar($value) && $operator->holds($this->compare($value, $other));
    }

    /**
     * Compare two scalar values the way their content suggests.
     */
    protected function compare(int|float|string|bool $left, int|float|string|bool $right): int
    {
        if (is_numeric($left) && is_numeric($right)) {
            return $left + 0 <=> $right + 0;
        }

        $leftTime = is_string($left) ? strtotime($left) : false;
        $rightTime = is_string($right) ? strtotime($right) : false;

        if ($leftTime !== false && $rightTime !== false) {
            return $leftTime <=> $rightTime;
        }

        return strcmp((string) $left, (string) $right);
    }
}
