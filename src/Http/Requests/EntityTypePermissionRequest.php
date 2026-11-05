<?php

namespace Webkul\MasterDataManagement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Webkul\MasterDataManagement\Enums\RecordAction;

class EntityTypePermissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return bouncer()->hasPermission('masterdata.entity_types.edit');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'permissions'     => ['sometimes', 'array'],
            'permissions.*'   => ['array'],
            'permissions.*.*' => ['string', Rule::enum(RecordAction::class)],
        ];
    }

    /**
     * The submitted actions of one role.
     *
     * @return list<string>
     */
    public function actionsFor(int $roleId): array
    {
        $actions = $this->validated('permissions.'.$roleId, []);

        return is_array($actions) ? array_values($actions) : [];
    }
}
