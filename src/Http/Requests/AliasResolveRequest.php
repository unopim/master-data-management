<?php

namespace Webkul\MasterDataManagement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Webkul\MasterDataManagement\Models\CodeListProxy;
use Webkul\MasterDataManagement\Models\SourceSystemProxy;

class AliasResolveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return bouncer()->hasPermission('masterdata.code_lists.edit');
    }

    /**
     * Whether the resolve form was submitted; the listing alone only shows the form.
     */
    public function isResolve(): bool
    {
        return $this->has('resolve_term');
    }

    /**
     * A blank source system means any source system.
     */
    protected function prepareForValidation(): void
    {
        if ($this->input('resolve_source_system_id') === '') {
            $this->merge(['resolve_source_system_id' => null]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        if (! $this->isResolve()) {
            return [];
        }

        return [
            'resolve_code_list_id'     => ['required', 'integer', Rule::exists(CodeListProxy::modelClass(), 'id')],
            'resolve_source_system_id' => ['nullable', 'integer', Rule::exists(SourceSystemProxy::modelClass(), 'id')],
            'resolve_term'             => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'resolve_code_list_id'     => trans('masterdata::app.aliases.form.code-list'),
            'resolve_source_system_id' => trans('masterdata::app.aliases.form.source-system'),
            'resolve_term'             => trans('masterdata::app.aliases.form.term'),
        ];
    }
}
