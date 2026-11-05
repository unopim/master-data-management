<?php

namespace Webkul\MasterDataManagement\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Webkul\MasterDataManagement\Models\Code;

/**
 * @mixin Code
 */
class CodeResource extends JsonResource
{
    /**
     * Transform the code into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'code'        => $this->code,
            'parent_code' => $this->parent_code,
            'sort'        => $this->sort,
            'labels'      => (object) $this->translations->pluck('label', 'locale')->filter()->all(),
            'attributes'  => $this->getAttribute('attributes') ?? (object) [],
        ];
    }
}
