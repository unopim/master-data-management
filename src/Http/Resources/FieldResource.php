<?php

namespace Webkul\MasterDataManagement\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Webkul\MasterDataManagement\Models\Field;
use Webkul\MasterDataManagement\Models\FieldTransform;

/**
 * @mixin Field
 */
class FieldResource extends JsonResource
{
    /**
     * Transform the field into an array; its validation rules and transforms, in run order, when loaded.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'code'     => $this->code,
            'type'     => $this->type,
            'labels'   => (object) $this->translations->pluck('label', 'locale')->filter()->all(),
            'group'    => $this->group,
            'position' => $this->position,
            'flags'    => [
                'is_required'        => $this->is_required,
                'is_unique'          => $this->is_unique,
                'is_identifier'      => $this->is_identifier,
                'value_per_locale'   => $this->value_per_locale,
                'is_effective_dated' => $this->is_effective_dated,
                'is_searchable'      => $this->is_searchable,
                'multiple'           => $this->multiple,
            ],
            'validation'            => $this->validation ?? (object) [],
            'code_list'             => $this->codeList?->code,
            'reference_entity_type' => $this->referenceEntityType?->code,
            'validation_rules'      => FieldValidationRuleResource::collection($this->whenLoaded('validationRules')),
            'transforms'            => $this->whenLoaded('transforms', fn (): array => $this->transforms
                ->map(fn (FieldTransform $transform): string => (string) $transform->transform)
                ->values()
                ->all()),
        ];
    }
}
