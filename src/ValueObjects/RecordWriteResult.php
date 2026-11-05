<?php

namespace Webkul\MasterDataManagement\ValueObjects;

use Webkul\MasterDataManagement\Models\Record;

final readonly class RecordWriteResult
{
    /**
     * @param  Record  $record  The written record, or when the change is held, the record as it stands (unsaved on create).
     */
    public function __construct(
        public Record $record,
        public GateResult $gate,
    ) {}

    /**
     * Whether the change was held for approval instead of written.
     */
    public function isPending(): bool
    {
        return $this->gate->isPending();
    }
}
