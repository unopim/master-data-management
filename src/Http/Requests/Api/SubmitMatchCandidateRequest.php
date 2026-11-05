<?php

namespace Webkul\MasterDataManagement\Http\Requests\Api;

use Webkul\AdminApi\Http\Requests\ApiFormRequest;
use Webkul\MasterDataManagement\Http\Requests\Api\Concerns\ValidatesValuePayload;

class SubmitMatchCandidateRequest extends ApiFormRequest
{
    use ValidatesValuePayload;

    /**
     * Get the validation rules for the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'system' => ['required', 'string', 'max:255'],
            'type'   => ['required', 'string', 'max:255'],
            'id'     => ['required', 'string', 'max:255'],
            'code'   => ['nullable', 'string', 'max:255'],
            'label'  => ['required_without:code', 'nullable', 'string', 'max:255'],
            ...$this->valuePayloadRules(),
        ];
    }
}
