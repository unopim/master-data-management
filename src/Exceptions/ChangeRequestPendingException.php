<?php

namespace Webkul\MasterDataManagement\Exceptions;

use Illuminate\Validation\ValidationException;

/**
 * The change gate refused a change because its target already holds a pending change request. A validation error,
 * so callers that only know ValidationException still answer 422.
 */
class ChangeRequestPendingException extends ValidationException {}
