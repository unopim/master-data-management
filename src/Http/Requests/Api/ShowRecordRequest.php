<?php

namespace Webkul\MasterDataManagement\Http\Requests\Api;

use Webkul\AdminApi\Http\Requests\ApiFormRequest;

class ShowRecordRequest extends ApiFormRequest
{
    /**
     * The extra data a record read may include.
     */
    public const array INCLUDES = ['xrefs', 'relations'];

    /**
     * Get the validation rules for the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'include' => ['sometimes', 'nullable', 'string', 'regex:/^('.implode('|', self::INCLUDES).')(,('.implode('|', self::INCLUDES).'))*$/'],
        ];
    }

    /**
     * Whether the caller asked to include the given extra data.
     */
    public function includes(string $include): bool
    {
        return in_array($include, explode(',', $this->string('include')->toString()), true);
    }
}
