<?php

namespace Webkul\MasterDataManagement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class QualityDecisionRequest extends FormRequest
{
    /**
     * The `return_to` value that sends the admin back to the issue's record page.
     */
    public const string RETURN_TO_RECORD = 'record';

    public function authorize(): bool
    {
        return bouncer()->hasPermission('masterdata.quality.resolve');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'return_to' => ['nullable', 'string', 'in:'.self::RETURN_TO_RECORD],
            'record'    => ['nullable', 'required_if:return_to,'.self::RETURN_TO_RECORD, 'uuid'],
        ];
    }
}
