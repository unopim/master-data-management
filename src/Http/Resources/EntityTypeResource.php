<?php

namespace Webkul\MasterDataManagement\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Webkul\MasterDataManagement\Models\EntityType;

/**
 * @mixin EntityType
 */
class EntityTypeResource extends JsonResource
{
    /**
     * @param  array<int, string>  $typeCodes  Entity type codes keyed by id, covering the allowed parent types.
     */
    public function __construct(EntityType $resource, protected array $typeCodes = [])
    {
        parent::__construct($resource);
    }

    /**
     * Transform the entity type and its active fields into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'code'                 => $this->code,
            'names'                => (object) $this->translations->pluck('name', 'locale')->filter()->all(),
            'icon'                 => $this->icon,
            'label_field_code'     => $this->label_field_code,
            'is_hierarchical'      => $this->is_hierarchical,
            'is_scoped'            => (bool) $this->is_scoped,
            'allowed_parent_types' => array_values(array_filter(array_map(
                fn (mixed $id): ?string => $this->typeCodes[(int) $id] ?? null,
                $this->allowed_parent_type_ids ?? [],
            ))),
            'approval_required'  => $this->approval_required,
            'mastering_style'    => $this->mastering_style->value,
            'code_strategy'      => $this->code_strategy->value,
            'schema_version'     => $this->schema_version,
            'status'             => $this->status->value,
            'fields'             => FieldResource::collection($this->fields),
            'match_rules'        => MatchRuleResource::collection($this->whenLoaded('matchRules')),
            'survivorship_rules' => SurvivorshipRuleResource::collection($this->whenLoaded('survivorshipRules')),
        ];
    }
}
