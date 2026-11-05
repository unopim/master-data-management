<?php

namespace Webkul\MasterDataManagement\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Webkul\MasterDataManagement\Models\Record;

/**
 * An external id of the record was linked or retired.
 */
class RecordXrefsChanged implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(public readonly Record $record) {}
}
