<?php

namespace Webkul\MasterDataManagement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * The admin async select's query for code list and source system options: a search term, the page and, when the
 * picker restores a saved value, the ids to look up (`identifiers.values`, sent as one id or a list).
 */
class SelectOptionsRequest extends FormRequest
{
    /**
     * The most options one response page holds.
     */
    public const int PER_PAGE = 20;

    public function authorize(): bool
    {
        return bouncer()->hasPermission('masterdata');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'query'                => ['nullable', 'string', 'max:255'],
            'page'                 => ['nullable', 'integer', 'min:1'],
            'inactive'             => ['nullable', 'boolean'],
            'identifiers'          => ['nullable', 'array'],
            'identifiers.values'   => ['nullable', 'array', 'max:50'],
            'identifiers.values.*' => ['integer'],
        ];
    }

    /**
     * The search term, if any.
     */
    public function term(): ?string
    {
        $term = $this->validated('query');

        return is_string($term) ? $term : null;
    }

    /**
     * The ids the picker restores.
     *
     * @return list<int>
     */
    public function ids(): array
    {
        return array_values(array_map(intval(...), (array) $this->validated('identifiers.values', [])));
    }

    /**
     * The requested page.
     */
    public function pageNumber(): int
    {
        return (int) $this->validated('page', 1);
    }

    protected function prepareForValidation(): void
    {
        $values = $this->input('identifiers.values');

        if (is_scalar($values)) {
            $this->merge(['identifiers' => [...(array) $this->input('identifiers'), 'values' => [$values]]]);
        }
    }
}
