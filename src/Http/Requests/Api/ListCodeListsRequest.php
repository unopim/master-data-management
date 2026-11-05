<?php

namespace Webkul\MasterDataManagement\Http\Requests\Api;

use Webkul\AdminApi\Http\Requests\ApiFormRequest;

class ListCodeListsRequest extends ApiFormRequest
{
    /**
     * Get the validation rules for the request.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'limit'  => ['sometimes', 'integer', 'min:1', 'max:100'],
            'cursor' => ['sometimes', 'nullable', 'string', 'max:2048'],
        ];
    }
}
