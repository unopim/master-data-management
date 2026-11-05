<?php

namespace Webkul\MasterDataManagement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Webkul\MasterDataManagement\Services\SurvivorshipResolver;

class RecordMergeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return bouncer()->hasPermission('masterdata.records.merge');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'merged'            => ['required', 'uuid'],
            'survivor_revision' => ['required', 'integer', 'min:1'],
            'merged_revision'   => ['required', 'integer', 'min:1'],
            'choices'           => ['sometimes', 'array', 'max:500'],
            'choices.*'         => ['string', Rule::in([SurvivorshipResolver::SURVIVOR, SurvivorshipResolver::MERGED])],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'merged' => trans('masterdata::app.records.merge.duplicate'),
        ];
    }
}
