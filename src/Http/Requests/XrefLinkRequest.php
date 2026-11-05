<?php

namespace Webkul\MasterDataManagement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class XrefLinkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return bouncer()->hasPermission('masterdata.records.edit');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'source_system_id' => ['required', 'integer', Rule::exists('mdm_source_systems', 'id')->where('is_active', true)],
            'external_id'      => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'source_system_id' => trans('masterdata::app.records.xrefs.system'),
            'external_id'      => trans('masterdata::app.records.xrefs.external-id'),
        ];
    }
}
