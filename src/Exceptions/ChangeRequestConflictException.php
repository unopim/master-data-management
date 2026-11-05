<?php

namespace Webkul\MasterDataManagement\Exceptions;

use RuntimeException;
use Webkul\MasterDataManagement\Models\ChangeRequest;

class ChangeRequestConflictException extends RuntimeException
{
    public function __construct(public readonly ChangeRequest $changeRequest)
    {
        parent::__construct(trans('masterdata::app.approvals.conflict'));
    }
}
