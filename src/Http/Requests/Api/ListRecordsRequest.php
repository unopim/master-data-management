<?php

namespace Webkul\MasterDataManagement\Http\Requests\Api;

use Illuminate\Validation\Rule;
use Webkul\MasterDataManagement\Enums\RecordStatus;

class ListRecordsRequest extends ShowRecordRequest
{
    /**
     * The filter keys that match record columns; any other key names a searchable field.
     */
    public const array COLUMN_FILTERS = ['code', 'status', 'updated_since'];

    /**
     * Get the validation rules for the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'limit'                => ['sometimes', 'integer', 'min:1', 'max:100'],
            'cursor'               => ['sometimes', 'nullable', 'string', 'max:2048'],
            'filter'               => ['sometimes', 'array'],
            'filter.code'          => ['sometimes', 'string', 'max:255'],
            'filter.status'        => ['sometimes', Rule::enum(RecordStatus::class)],
            'filter.updated_since' => ['sometimes', 'date'],
            'filter.*'             => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * The page size, 50 unless given.
     */
    public function limit(): int
    {
        return $this->integer('limit', 50);
    }

    /**
     * The value of a column filter, or null when not given.
     */
    public function columnFilter(string $key): ?string
    {
        $value = $this->validated('filter.'.$key);

        return is_string($value) && $value !== '' ? $value : null;
    }

    /**
     * The field filters as field code => value.
     *
     * @return array<string, string>
     */
    public function fieldFilters(): array
    {
        $filters = (array) ($this->validated('filter') ?? []);

        return array_map(strval(...), array_diff_key(array_filter($filters, fn (mixed $value): bool => $value !== null), array_flip(self::COLUMN_FILTERS)));
    }
}
