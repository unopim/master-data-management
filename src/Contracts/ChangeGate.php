<?php

namespace Webkul\MasterDataManagement\Contracts;

use Webkul\MasterDataManagement\ValueObjects\ChangeSet;
use Webkul\MasterDataManagement\ValueObjects\GateResult;

interface ChangeGate
{
    /**
     * Decide whether a proposed record change is applied now or held for approval.
     */
    public function submit(ChangeSet $set): GateResult;
}
