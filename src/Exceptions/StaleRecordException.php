<?php

namespace Webkul\MasterDataManagement\Exceptions;

use RuntimeException;
use Webkul\MasterDataManagement\Models\Record;

class StaleRecordException extends RuntimeException
{
    public function __construct(
        public readonly Record $record,
        public readonly int $expectedRevision,
    ) {
        parent::__construct(trans('masterdata::app.records.stale-revision'));
    }
}
