<?php

namespace Webkul\MasterDataManagement\Http\Requests;

/**
 * The subtree root picker's query on the data access forms, open to whoever may manage data access.
 */
class AccessRootOptionsRequest extends RecordOptionsRequest
{
    public function authorize(): bool
    {
        return bouncer()->hasPermission('masterdata.access');
    }
}
