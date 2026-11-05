<?php

namespace Webkul\MasterDataManagement\Exceptions;

use RuntimeException;
use Webkul\MasterDataManagement\Models\SourceSystem;

class XrefConflictException extends RuntimeException
{
    public function __construct(
        public readonly SourceSystem $sourceSystem,
        public readonly string $externalId,
        public readonly int $existingRecordId,
    ) {
        parent::__construct(trans('masterdata::app.xrefs.conflict', [
            'system' => $sourceSystem->code,
            'id'     => $externalId,
        ]));
    }
}
