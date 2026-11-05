<?php

namespace Webkul\MasterDataManagement\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;
use Webkul\MasterDataManagement\Models\CodeList;
use Webkul\MasterDataManagement\Models\CodeListVersion;

/**
 * @mixin CodeList
 */
class CodeListResource extends JsonResource
{
    /**
     * A version list or effective version left null leaves that key out of the body; the effective version is
     * `null` in the body when the list has none in effect.
     *
     * @param  Collection<int, CodeListVersion>|null  $versions
     */
    public function __construct(
        CodeList $resource,
        protected bool $withEffectiveVersion = false,
        protected ?CodeListVersion $effectiveVersion = null,
        protected ?Collection $versions = null,
    ) {
        parent::__construct($resource);
    }

    /**
     * Transform the code list into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'code'              => $this->code,
            'names'             => (object) $this->translations->pluck('name', 'locale')->filter()->all(),
            'owner'             => $this->owner,
            'is_versioned'      => $this->is_versioned,
            'effective_version' => $this->when(
                $this->withEffectiveVersion,
                fn (): ?CodeListVersionResource => $this->effectiveVersion instanceof CodeListVersion ? new CodeListVersionResource($this->effectiveVersion) : null,
            ),
            'versions' => $this->when($this->versions instanceof Collection, fn () => CodeListVersionResource::collection($this->versions)),
        ];
    }
}
