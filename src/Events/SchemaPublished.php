<?php

namespace Webkul\MasterDataManagement\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Webkul\MasterDataManagement\Models\EntityType;
use Webkul\MasterDataManagement\Models\EntityTypeVersion;

class SchemaPublished implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(
        public readonly EntityType $entityType,
        public readonly EntityTypeVersion $version,
    ) {}
}
