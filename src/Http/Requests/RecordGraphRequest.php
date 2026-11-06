<?php

namespace Webkul\MasterDataManagement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * The record graph's query: how many hops out to go and the most nodes to return, capped by `masterdata.graph`.
 */
class RecordGraphRequest extends FormRequest
{
    public function authorize(): bool
    {
        return bouncer()->hasPermission('masterdata.records.view');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'hops'  => ['nullable', 'integer', 'min:1', 'max:'.$this->maxHops()],
            'limit' => ['nullable', 'integer', 'min:1', 'max:'.$this->maxNodes()],
        ];
    }

    /**
     * The requested hop count, one by default.
     */
    public function hops(): int
    {
        return (int) ($this->validated('hops') ?? 1);
    }

    /**
     * The requested node limit, the configured most by default.
     */
    public function limit(): int
    {
        return (int) ($this->validated('limit') ?? $this->maxNodes());
    }

    /**
     * The most nodes a graph may hold.
     */
    protected function maxNodes(): int
    {
        return max(1, (int) config('masterdata.graph.max_nodes', 100));
    }

    /**
     * The most hops a graph may span.
     */
    protected function maxHops(): int
    {
        return max(1, (int) config('masterdata.graph.max_hops', 2));
    }
}
