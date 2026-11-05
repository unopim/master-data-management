<?php

namespace Webkul\MasterDataManagement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Webkul\MasterDataManagement\Enums\RecordStatus;

/**
 * The record form's shape. The field rules of `values` stay in RecordWriter, the one place that validates them.
 */
class RecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return bouncer()->hasPermission($this->isUpdate() ? 'masterdata.records.edit' : 'masterdata.records.create');
    }

    /**
     * The root option and a cleared picker both mean no parent.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('parent') && in_array($this->input('parent'), [RecordParentRequest::ROOT, '', null], true)) {
            $this->merge(['parent' => null]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'code'     => ['sometimes', 'nullable', 'string', 'max:255'],
            'values'   => ['sometimes', 'array'],
            'label'    => ['sometimes', 'nullable', 'string', 'max:255'],
            'status'   => ['sometimes', Rule::enum(RecordStatus::class)->only([RecordStatus::Draft, RecordStatus::Active])],
            'parent'   => ['sometimes', 'nullable', 'uuid'],
            'revision' => $this->isUpdate() ? ['required', 'integer', 'min:1'] : ['prohibited'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'code'     => trans('masterdata::app.records.fields.code'),
            'label'    => trans('masterdata::app.records.fields.label'),
            'status'   => trans('masterdata::app.records.fields.status'),
            'parent'   => trans('masterdata::app.hierarchy.parent'),
            'revision' => trans('masterdata::app.hierarchy.revision'),
        ];
    }

    /**
     * Whether the request updates an existing record rather than creating one.
     */
    protected function isUpdate(): bool
    {
        return $this->route('uuid') !== null;
    }
}
