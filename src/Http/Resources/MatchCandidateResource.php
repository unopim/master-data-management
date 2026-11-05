<?php

namespace Webkul\MasterDataManagement\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Webkul\MasterDataManagement\Matching\MatchSuggestion;
use Webkul\MasterDataManagement\Models\MatchCandidate;

/**
 * @mixin MatchCandidate
 */
class MatchCandidateResource extends JsonResource
{
    /**
     * @param  list<MatchSuggestion>  $suggestions
     */
    public function __construct(MatchCandidate $resource, protected array $suggestions = [])
    {
        parent::__construct($resource);
    }

    /**
     * Transform the inbox row into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'system'       => $this->sourceSystem?->code,
            'type'         => $this->entityType?->code,
            'external_id'  => $this->external_id,
            'label'        => $this->label,
            'status'       => $this->status->value,
            'top_score'    => $this->top_score,
            'auto_matched' => (bool) $this->auto_matched,
            'suggestions'  => array_map(fn (MatchSuggestion $suggestion): array => [
                'uuid'   => $suggestion->record->uuid,
                'code'   => $suggestion->record->code,
                'label'  => $suggestion->record->label,
                'score'  => $suggestion->score,
                'reason' => $suggestion->reason,
            ], $this->suggestions),
            'created_at'  => $this->created_at,
        ];
    }
}
