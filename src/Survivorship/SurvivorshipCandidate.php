<?php

namespace Webkul\MasterDataManagement\Survivorship;

use Webkul\MasterDataManagement\Models\Record;

/**
 * One record's value of a merge target, with the source systems the record holds active external ids in.
 */
final readonly class SurvivorshipCandidate
{
    /**
     * @param  array<string, mixed>  $value  The target's common value and per-locale values, keyed `common` and by locale.
     * @param  array<int, int>  $sources  Priority of each source system the record is linked to, keyed by system id.
     */
    public function __construct(
        public Record $record,
        public array $value,
        public array $sources = [],
    ) {}

    /**
     * Whether any of the values is filled.
     */
    public function isFilled(): bool
    {
        return array_any($this->value, fn (mixed $value): bool => ! in_array($value, [null, '', []], true));
    }

    /**
     * How much the value holds: characters of text, items of a list, one for any other filled value.
     */
    public function size(): int
    {
        $size = 0;

        foreach ($this->value as $value) {
            $size += match (true) {
                is_string($value) => mb_strlen(trim($value)),
                is_array($value)  => count(array_filter($value, fn (mixed $item): bool => $item !== null && $item !== '')),
                $value === null   => 0,
                default           => 1,
            };
        }

        return $size;
    }
}
