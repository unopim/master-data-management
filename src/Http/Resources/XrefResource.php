<?php

namespace Webkul\MasterDataManagement\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Webkul\MasterDataManagement\Models\Record;

class XrefResource extends JsonResource
{
    /**
     * @param  array<string, list<string>>  $ids
     */
    public function __construct(protected Record $record, protected array $ids)
    {
        parent::__construct($record);
    }

    /**
     * Transform the resolution into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'record' => new RecordResource($this->record, $this->ids),
            'ids'    => (object) $this->ids,
        ];
    }
}
