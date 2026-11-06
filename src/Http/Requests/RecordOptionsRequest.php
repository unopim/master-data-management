<?php

namespace Webkul\MasterDataManagement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * The async record picker's query: a search term, the page, the record being edited, an optional type filter, the
 * reference or select field a picker fills (`field`) and, when the picker restores a saved value, the uuids or codes
 * to look up (`identifiers.values`).
 */
class RecordOptionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return bouncer()->hasPermission('masterdata.records');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'query'                => ['nullable', 'string', 'max:255'],
            'page'                 => ['nullable', 'integer', 'min:1'],
            'record'               => ['nullable', 'uuid'],
            'field'                => ['nullable', 'string', 'max:64'],
            'types'                => ['nullable', 'array', 'max:50'],
            'types.*'              => ['integer'],
            'identifiers'          => ['nullable', 'array'],
            'identifiers.values'   => ['nullable', 'array', 'max:20'],
            'identifiers.values.*' => ['string', 'max:64'],
        ];
    }
}
