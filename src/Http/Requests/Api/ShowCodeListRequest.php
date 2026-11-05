<?php

namespace Webkul\MasterDataManagement\Http\Requests\Api;

use Webkul\AdminApi\Http\Requests\ApiFormRequest;

class ShowCodeListRequest extends ApiFormRequest
{
    /**
     * Get the validation rules for the request.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'include' => ['sometimes', 'nullable', 'string', 'in:versions'],
        ];
    }
}
