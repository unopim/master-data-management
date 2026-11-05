<?php

namespace Webkul\MasterDataManagement\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Webkul\MasterDataManagement\Models\Record;
use Webkul\User\Models\Admin;

class RecordErased implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    /**
     * @param  list<int>  $erasedIds  The record and the duplicates merged into it, all erased with it.
     */
    public function __construct(
        public readonly Record $record,
        public readonly array $erasedIds,
        public readonly ?Admin $by = null,
    ) {}
}
