<?php

namespace Webkul\MasterDataManagement\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Webkul\MasterDataManagement\Models\SurvivorshipRule;

/**
 * @mixin SurvivorshipRule
 */
class SurvivorshipRuleResource extends JsonResource
{
    /**
     * Transform the survivorship rule into an array; a null target is the type's default rule.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'target'     => $this->target,
            'strategy'   => $this->strategy,
            'options'    => (object) ($this->options ?? []),
            'sort_order' => $this->sort_order,
        ];
    }
}
