<?php

namespace Webkul\MasterDataManagement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Webkul\MasterDataManagement\Enums\EntityTypeStatus;

class MatchCandidateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return bouncer()->hasPermission('masterdata.matching.create');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'source_system_id' => ['required', 'integer', Rule::exists('mdm_source_systems', 'id')->where('is_active', true)],
            'entity_type_id'   => ['required', 'integer', Rule::exists('mdm_entity_types', 'id')->where('status', EntityTypeStatus::Active->value)],
            'external_id'      => ['required', 'string', 'max:255'],
            'code'             => ['nullable', 'string', 'max:255'],
            'label'            => ['required_without:code', 'nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'source_system_id' => trans('masterdata::app.matching.fields.system'),
            'entity_type_id'   => trans('masterdata::app.matching.fields.entity-type'),
            'external_id'      => trans('masterdata::app.matching.fields.external-id'),
            'code'             => trans('masterdata::app.matching.fields.code'),
            'label'            => trans('masterdata::app.matching.fields.label'),
        ];
    }
}
