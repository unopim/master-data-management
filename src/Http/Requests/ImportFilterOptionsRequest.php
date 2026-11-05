<?php

namespace Webkul\MasterDataManagement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Webkul\MasterDataManagement\Http\Controllers\Admin\ImportFilterController;

/**
 * The admin async select's query for the master data import filters: a search term, the page, the entity type
 * access wanted and, when the picker restores a saved value, the codes to look up (`identifiers.values`).
 */
class ImportFilterOptionsRequest extends FormRequest
{
    /**
     * The most options one response page holds.
     */
    public const int PER_PAGE = 20;

    public function authorize(): bool
    {
        return bouncer()->hasPermission('data_transfer.imports');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'query'                => ['nullable', 'string', 'max:255'],
            'page'                 => ['nullable', 'integer', 'min:1'],
            'access'               => ['nullable', 'string', 'in:'.ImportFilterController::ACCESS_EDIT],
            'identifiers'          => ['nullable', 'array'],
            'identifiers.values'   => ['nullable', 'array', 'max:50'],
            'identifiers.values.*' => ['string', 'max:64'],
        ];
    }

    /**
     * The search term, if any.
     */
    public function term(): ?string
    {
        $term = $this->validated('query');

        return is_string($term) ? trim($term) : null;
    }

    /**
     * The codes the picker restores.
     *
     * @return list<string>
     */
    public function codes(): array
    {
        return array_values(array_map(strval(...), (array) $this->validated('identifiers.values', [])));
    }

    /**
     * The requested page.
     */
    public function pageNumber(): int
    {
        return (int) $this->validated('page', 1);
    }

    /**
     * Whether only entity types whose records the admin may edit are wanted.
     */
    public function editOnly(): bool
    {
        return $this->validated('access') === ImportFilterController::ACCESS_EDIT;
    }

    protected function prepareForValidation(): void
    {
        $values = $this->input('identifiers.values');

        if (is_string($values)) {
            $this->merge(['identifiers' => [...(array) $this->input('identifiers'), 'values' => explode(',', $values)]]);
        }
    }
}
