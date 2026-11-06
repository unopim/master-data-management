<?php

namespace Webkul\MasterDataManagement\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Webkul\MasterDataManagement\Models\HierarchyNode;

/**
 * @mixin HierarchyNode
 */
class HierarchyPlacementResource extends JsonResource
{
    /**
     * @param  array<int, string>  $uuids  The uuids of the records the caller may see, keyed by record id.
     */
    public function __construct(HierarchyNode $resource, protected array $uuids)
    {
        parent::__construct($resource);
    }

    /**
     * Transform the placement into an array: the hierarchy, the parent and the visible ancestors from the root down.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $ancestors = array_values(array_diff(
            array_map(intval(...), array_filter(explode('/', (string) $this->path), is_numeric(...))),
            [(int) $this->record_id],
        ));

        return [
            'hierarchy'  => $this->hierarchy->code,
            'is_primary' => $this->hierarchy->is_primary,
            'parent'     => $this->parent_id === null ? null : ($this->uuids[$this->parent_id] ?? null),
            'ancestors'  => array_values(array_filter(array_map(fn (int $id): ?string => $this->uuids[$id] ?? null, $ancestors))),
            'depth'      => $this->depth,
        ];
    }
}
