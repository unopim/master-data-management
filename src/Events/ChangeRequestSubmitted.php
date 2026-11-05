<?php

namespace Webkul\MasterDataManagement\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Webkul\MasterDataManagement\Models\ChangeRequest;

/**
 * A record change was held for approval.
 */
class ChangeRequestSubmitted implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly ChangeRequest $changeRequest) {}
}
