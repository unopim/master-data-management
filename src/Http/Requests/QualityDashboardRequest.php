<?php

namespace Webkul\MasterDataManagement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Webkul\MasterDataManagement\Enums\TrendRange;

class QualityDashboardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return bouncer()->hasPermission('masterdata.quality_dashboard');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'entity_type' => ['nullable', 'integer', 'min:1'],
            'days'        => ['nullable', Rule::enum(TrendRange::class)],
        ];
    }

    /**
     * The trend range asked for, a month by default.
     */
    public function range(): TrendRange
    {
        return TrendRange::tryFrom((int) $this->validated('days')) ?? TrendRange::Month;
    }

    /**
     * The entity type whose trend is asked for, if any.
     */
    public function entityTypeId(): ?int
    {
        $id = $this->validated('entity_type');

        return $id === null ? null : (int) $id;
    }
}
