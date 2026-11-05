<?php

namespace Webkul\MasterDataManagement\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Webkul\MasterDataManagement\Models\MatchRule;

/**
 * @mixin MatchRule
 */
class MatchRuleResource extends JsonResource
{
    /**
     * Transform the match rule into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'target'      => $this->target,
            'comparator'  => $this->comparator,
            'weight'      => $this->weight,
            'is_blocking' => $this->is_blocking,
            'options'     => (object) ($this->options ?? []),
            'sort_order'  => $this->sort_order,
        ];
    }
}
