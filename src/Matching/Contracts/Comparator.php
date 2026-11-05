<?php

namespace Webkul\MasterDataManagement\Matching\Contracts;

/**
 * Scores how alike two values are for a match rule; registered under `masterdata.matching.comparators`.
 */
interface Comparator
{
    /**
     * The similarity of the two values from 0 (unrelated) to 1 (the same).
     *
     * @param  array<string, mixed>  $options  The rule's options, such as a numeric tolerance or a date window.
     */
    public function compare(string $left, string $right, array $options): float;
}
