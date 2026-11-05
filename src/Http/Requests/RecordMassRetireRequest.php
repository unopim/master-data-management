<?php

namespace Webkul\MasterDataManagement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RecordMassRetireRequest extends FormRequest
{
    /**
     * The most records one mass retire may select, since each one passes the change gate in the request.
     */
    public const int MAX_RECORDS = 500;

    public function authorize(): bool
    {
        return bouncer()->hasPermission('masterdata.records.delete');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'indices'   => ['required', 'array', 'min:1', 'max:'.self::MAX_RECORDS],
            'indices.*' => ['required', 'integer', 'min:1', 'distinct'],
        ];
    }

    /**
     * The selected record ids.
     *
     * @return list<int>
     */
    public function recordIds(): array
    {
        return array_map(intval(...), $this->validated('indices'));
    }
}
