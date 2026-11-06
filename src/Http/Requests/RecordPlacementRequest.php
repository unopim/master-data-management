<?php

namespace Webkul\MasterDataManagement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RecordPlacementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return bouncer()->hasPermission('masterdata.records.edit');
    }

    /**
     * The root option and a cleared select both place the record at the root of the hierarchy.
     */
    protected function prepareForValidation(): void
    {
        if (in_array($this->input('parent'), [RecordParentRequest::ROOT, ''], true)) {
            $this->merge(['parent' => null]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'parent' => ['present', 'nullable', 'uuid'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'parent' => trans('masterdata::app.hierarchy.parent'),
        ];
    }

    /**
     * The uuid of the chosen parent, null for the root.
     */
    public function parentUuid(): ?string
    {
        $parent = $this->validated('parent');

        return is_string($parent) ? $parent : null;
    }
}
