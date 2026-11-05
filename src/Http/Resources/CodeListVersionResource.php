<?php

namespace Webkul\MasterDataManagement\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Webkul\MasterDataManagement\Models\CodeListVersion;

/**
 * @mixin CodeListVersion
 */
class CodeListVersionResource extends JsonResource
{
    /**
     * Transform the version into an array, with its codes when they are loaded.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'number'         => $this->version,
            'status'         => $this->status->value,
            'effective_from' => $this->effective_from?->toDateString(),
            'effective_to'   => $this->effective_to?->toDateString(),
            'codes'          => CodeResource::collection($this->whenLoaded('codes')),
        ];
    }
}
