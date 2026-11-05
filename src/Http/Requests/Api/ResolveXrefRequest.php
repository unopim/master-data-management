<?php

namespace Webkul\MasterDataManagement\Http\Requests\Api;

use Webkul\AdminApi\Http\Requests\ApiFormRequest;

class ResolveXrefRequest extends ApiFormRequest
{
    /**
     * Get the validation rules for the request.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'system' => ['required', 'string', 'max:255'],
            'type'   => ['required', 'string', 'max:255'],
            'id'     => ['required', 'string', 'max:255'],
        ];
    }
}
