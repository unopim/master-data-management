<?php

namespace Webkul\MasterDataManagement\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Webkul\MasterDataManagement\Models\CodeListVersion;

class CodeListPublished implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(public readonly CodeListVersion $version) {}
}
