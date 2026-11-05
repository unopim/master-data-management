<?php

namespace Webkul\MasterDataManagement\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Webkul\MasterDataManagement\Models\Record;
use Webkul\MasterDataManagement\ValueObjects\ChangeSet;

class RecordRetired implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(
        public readonly Record $record,
        public readonly ChangeSet $changeSet,
    ) {}
}
