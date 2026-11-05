<?php

namespace Webkul\MasterDataManagement\Services;

use Webkul\MasterDataManagement\Contracts\ChangeGate;
use Webkul\MasterDataManagement\ValueObjects\ChangeSet;
use Webkul\MasterDataManagement\ValueObjects\GateResult;

class DirectChangeGate implements ChangeGate
{
    /**
     * Apply every change immediately; used while approval is off.
     */
    public function submit(ChangeSet $set): GateResult
    {
        return GateResult::apply();
    }
}
