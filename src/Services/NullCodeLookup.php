<?php

namespace Webkul\MasterDataManagement\Services;

use DateTimeInterface;
use Webkul\MasterDataManagement\Contracts\CodeLookup;

/**
 * Rejects every code until a code list store is bound.
 */
class NullCodeLookup implements CodeLookup
{
    /**
     * {@inheritdoc}
     */
    public function isValid(int $codeListId, string $code, ?DateTimeInterface $on = null): bool
    {
        return false;
    }
}
