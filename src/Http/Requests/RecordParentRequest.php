<?php

namespace Webkul\MasterDataManagement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RecordParentRequest extends FormRequest
{
    /**
     * The select value that places the record at the root of its hierarchy.
     */
    public const string ROOT = 'root';

    public function authorize(): bool
    {
        return bouncer()->hasPermission('masterdata.records.edit');
    }

    /**
     * The root option and a cleared select both mean no parent.
     */
    protected function prepareForValidation(): void
    {
        if (in_array($this->input('parent'), [self::ROOT, ''], true)) {
            $this->merge(['parent' => null]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'parent'   => ['present', 'nullable', 'uuid'],
            'revision' => ['required', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'parent'   => trans('masterdata::app.hierarchy.parent'),
            'revision' => trans('masterdata::app.hierarchy.revision'),
        ];
    }
}
