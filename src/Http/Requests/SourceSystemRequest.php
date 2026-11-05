<?php

namespace Webkul\MasterDataManagement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Webkul\MasterDataManagement\Enums\SourceSystemType;
use Webkul\MasterDataManagement\Models\SourceSystemProxy;

class SourceSystemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return bouncer()->hasPermission(
            $this->route('id') ? 'masterdata.source_systems.edit' : 'masterdata.source_systems.create'
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'code'                    => $this->route('id')
                ? ['prohibited']
                : ['required', 'string', 'regex:/^[a-z][a-z0-9_]{1,63}$/', Rule::unique(SourceSystemProxy::modelClass(), 'code')],
            'name'                    => ['required', 'string', 'max:255'],
            'type'                    => ['required', Rule::enum(SourceSystemType::class)],
            'priority'                => ['sometimes', 'integer', 'min:0', 'max:1000000'],
            'is_active'               => ['sometimes', 'boolean'],
            'contact_owner'           => ['nullable', 'string', 'max:255'],
            'requires_approval'       => ['sometimes', 'boolean'],
            'external_id_is_personal' => ['sometimes', 'boolean'],
        ];
    }
}
