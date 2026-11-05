<?php

namespace Webkul\MasterDataManagement\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Webkul\MasterDataManagement\Models\ChangeRequest;

/**
 * A held record change was approved, rejected or found stale.
 */
class ChangeRequestDecided implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(public readonly ChangeRequest $changeRequest) {}
}
