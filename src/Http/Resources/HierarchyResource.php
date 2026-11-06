<?php

namespace Webkul\MasterDataManagement\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Webkul\MasterDataManagement\Models\Hierarchy;

/**
 * @mixin Hierarchy
 */
class HierarchyResource extends JsonResource
{
    /**
     * Transform the hierarchy into an array, its labels keyed by locale.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'code'       => $this->code,
            'is_primary' => $this->is_primary,
            'labels'     => (object) $this->translations->pluck('label', 'locale')->filter()->all(),
        ];
    }
}
