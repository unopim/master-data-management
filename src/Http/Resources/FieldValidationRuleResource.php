<?php

namespace Webkul\MasterDataManagement\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Webkul\MasterDataManagement\Models\FieldValidationRule;

/**
 * @mixin FieldValidationRule
 */
class FieldValidationRuleResource extends JsonResource
{
    /**
     * Transform the validation rule into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'type'       => $this->type,
            'severity'   => $this->severity->value,
            'params'     => (object) ($this->params ?? []),
            'messages'   => (object) ($this->messages ?? []),
            'sort_order' => $this->sort_order,
        ];
    }
}
