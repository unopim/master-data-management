<?php

namespace Webkul\MasterDataManagement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CrosswalkLookupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return bouncer()->hasPermission('masterdata.crosswalk');
    }

    /**
     * Whether the lookup form was submitted; the empty page only shows the form.
     */
    public function isLookup(): bool
    {
        return $this->hasAny(['source_system_id', 'entity_type_id', 'external_id']);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        if (! $this->isLookup()) {
            return [];
        }

        return [
            'source_system_id' => ['required', 'integer', Rule::exists('mdm_source_systems', 'id')],
            'entity_type_id'   => ['required', 'integer', Rule::exists('mdm_entity_types', 'id')],
            'external_id'      => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'source_system_id' => trans('masterdata::app.crosswalk.fields.system'),
            'entity_type_id'   => trans('masterdata::app.crosswalk.fields.entity-type'),
            'external_id'      => trans('masterdata::app.crosswalk.fields.external-id'),
        ];
    }
}
