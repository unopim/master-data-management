<?php

namespace Webkul\MasterDataManagement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Webkul\MasterDataManagement\Models\EntityTypeProxy;

class ModelExportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return bouncer()->hasPermission('masterdata.entity_types.model_export');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'types'   => ['sometimes', 'array', 'max:500'],
            'types.*' => ['string', 'max:64', Rule::exists(EntityTypeProxy::modelClass(), 'code')],
        ];
    }

    /**
     * The codes of the entity types to export; empty exports the whole model.
     *
     * @return list<string>
     */
    public function typeCodes(): array
    {
        return array_values(array_unique(array_map(strval(...), (array) $this->validated('types', []))));
    }
}
