<?php

namespace Webkul\MasterDataManagement\Contracts;

use DateTimeInterface;

interface CodeLookup
{
    /**
     * Whether the code belongs to the code list, optionally as of the given date.
     */
    public function isValid(int $codeListId, string $code, ?DateTimeInterface $on = null): bool;
}
