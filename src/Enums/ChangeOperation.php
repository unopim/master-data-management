<?php

namespace Webkul\MasterDataManagement\Enums;

enum ChangeOperation: string
{
    case Create = 'create';
    case Update = 'update';
    case Retire = 'retire';
    case Reactivate = 'reactivate';
    case LinkXref = 'link_xref';
    case UnlinkXref = 'unlink_xref';
    case Merge = 'merge';
    case Unmerge = 'unmerge';

    /**
     * Whether the change links or unlinks a source system id rather than writing the record row.
     */
    public function touchesXrefs(): bool
    {
        return $this === self::LinkXref || $this === self::UnlinkXref;
    }
}
