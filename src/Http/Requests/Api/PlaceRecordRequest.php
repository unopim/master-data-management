<?php

namespace Webkul\MasterDataManagement\Http\Requests\Api;

use Webkul\AdminApi\Http\Requests\ApiFormRequest;

class PlaceRecordRequest extends ApiFormRequest
{
    /**
     * A null parent places the record at the root of the hierarchy.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'parent' => ['present', 'nullable', 'uuid'],
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
