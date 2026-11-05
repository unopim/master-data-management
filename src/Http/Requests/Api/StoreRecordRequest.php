<?php

namespace Webkul\MasterDataManagement\Http\Requests\Api;

use Webkul\AdminApi\Http\Requests\ApiFormRequest;

/**
 * Checks the shape of a record write; RecordWriter validates the values against the entity type's fields.
 */
class StoreRecordRequest extends ApiFormRequest
{
    /**
     * Get the validation rules for the request.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'code'   => ['sometimes', 'string', 'max:255'],
            'label'  => ['sometimes', 'nullable', 'string', 'max:255'],
            'status' => ['sometimes', 'string'],
            'values' => ['sometimes', 'array'],
            'parent' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
