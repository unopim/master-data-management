<?php

namespace Webkul\MasterDataManagement\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Webkul\MasterDataManagement\Models\Record;
use Webkul\MasterDataManagement\Models\RecordMerge;
use Webkul\MasterDataManagement\ValueObjects\ChangeSet;

class RecordMerged implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(
        public readonly Record $survivor,
        public readonly Record $merged,
        public readonly RecordMerge $merge,
        public readonly ChangeSet $changeSet,
    ) {}
}
