<?php

namespace Webkul\MasterDataManagement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * The tree page's query: the hierarchy to show and, for one level, the record whose children to list.
 */
class RecordTreeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return bouncer()->hasPermission('masterdata.records');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'hierarchy' => ['nullable', 'integer', 'min:1'],
            'parent'    => ['nullable', 'uuid'],
        ];
    }

    /**
     * The requested hierarchy id, if any.
     */
    public function hierarchyId(): ?int
    {
        $id = $this->validated('hierarchy');

        return $id === null ? null : (int) $id;
    }

    /**
     * The uuid of the record whose children to list, null for the roots.
     */
    public function parentUuid(): ?string
    {
        $parent = $this->validated('parent');

        return is_string($parent) ? $parent : null;
    }
}
