<?php

namespace Webkul\MasterDataManagement\Exceptions;

use RuntimeException;
use Webkul\MasterDataManagement\Models\CodeListVersion;

class CodeListVersionNotDraftException extends RuntimeException
{
    public function __construct(public readonly CodeListVersion $version)
    {
        parent::__construct(trans('masterdata::app.code-lists.publish.not-draft'));
    }
}
