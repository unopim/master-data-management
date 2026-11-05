<?php

namespace Webkul\MasterDataManagement\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Webkul\MasterDataManagement\Models\Record;

/**
 * @mixin Record
 */
class RecordResource extends JsonResource
{
    /**
     * A null `ids`, `references` or `relations` leaves that key out of the body.
     *
     * @param  array<string, list<string>>|null  $ids
     * @param  array<string, string|list<string>>|null  $references  Reference field values, field code => uuid(s).
     * @param  array<string, list<string>>|null  $relations  Reference field targets, field code => uuids.
     */
    public function __construct(
        Record $resource,
        protected ?array $ids = [],
        protected ?array $references = [],
        protected ?array $relations = null,
    ) {
        parent::__construct($resource);
    }

    /**
     * Transform the record into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid'           => $this->uuid,
            'code'           => $this->code,
            'label'          => $this->label,
            'status'         => $this->status->value,
            'values'         => $this->values ?? (object) [],
            'references'     => $this->when($this->references !== null, fn (): object => (object) $this->references),
            'relations'      => $this->when($this->relations !== null, fn (): object => (object) $this->relations),
            'revision'       => $this->revision,
            'schema_version' => $this->schema_version,
            'ids'            => $this->when($this->ids !== null, fn (): object => (object) $this->ids),
            'created_at'     => $this->created_at,
            'updated_at'     => $this->updated_at,
        ];
    }
}
