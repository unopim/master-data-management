<?php

namespace Webkul\MasterDataManagement\Enums;

/**
 * How a cross field rule compares a value with the value of another field of the record.
 */
enum CrossFieldOperator: string
{
    case Equal = 'eq';
    case NotEqual = 'neq';
    case Greater = 'gt';
    case GreaterOrEqual = 'gte';
    case Less = 'lt';
    case LessOrEqual = 'lte';

    /**
     * Whether a comparison result (negative, zero or positive, as from the spaceship operator) satisfies the operator.
     */
    public function holds(int $comparison): bool
    {
        return match ($this) {
            self::Equal          => $comparison === 0,
            self::NotEqual       => $comparison !== 0,
            self::Greater        => $comparison > 0,
            self::GreaterOrEqual => $comparison >= 0,
            self::Less           => $comparison < 0,
            self::LessOrEqual    => $comparison <= 0,
        };
    }
}
