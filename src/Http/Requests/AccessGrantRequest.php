<?php

namespace Webkul\MasterDataManagement\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Webkul\MasterDataManagement\Enums\GrantAction;
use Webkul\MasterDataManagement\Enums\GrantPrincipal;
use Webkul\MasterDataManagement\Models\EntityTypeProxy;
use Webkul\MasterDataManagement\Models\Record;
use Webkul\MasterDataManagement\Repositories\AccessGrantRepository;
use Webkul\MasterDataManagement\Repositories\RecordRepository;
use Webkul\User\Models\AdminProxy;
use Webkul\User\Models\RoleProxy;

/**
 * A data access grant from the Data access screen. The subtree root must be a record the signed-in admin may see.
 */
class AccessGrantRequest extends FormRequest
{
    protected ?Record $root = null;

    public function authorize(): bool
    {
        return bouncer()->hasPermission($this->route('id') ? 'masterdata.access.edit' : 'masterdata.access.create');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'principal_type' => ['required', Rule::enum(GrantPrincipal::class)],
            'role_id'        => ['nullable', 'required_if:principal_type,'.GrantPrincipal::Role->value, 'integer', Rule::exists(RoleProxy::modelClass(), 'id')],
            'admin_id'       => ['nullable', 'required_if:principal_type,'.GrantPrincipal::User->value, 'integer', Rule::exists(AdminProxy::modelClass(), 'id')],
            'entity_type_id' => ['nullable', 'integer', Rule::exists(EntityTypeProxy::modelClass(), 'id')],
            'record'         => ['required', 'uuid'],
            'actions'        => ['required', 'array', 'min:1'],
            'actions.*'      => [Rule::enum(GrantAction::class)],
        ];
    }

    /**
     * Resolve the subtree root among the records the admin may see.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->has('record')) {
                    return;
                }

                $this->root = resolve(RecordRepository::class)->findByUuid((string) $this->input('record'));

                if (! $this->root instanceof Record) {
                    $validator->errors()->add('record', trans('masterdata::app.access.validation.record-not-found'));
                }
            },
        ];
    }

    /**
     * The grant attributes to store.
     *
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        $principal = GrantPrincipal::from((string) $this->validated('principal_type'));

        return [
            'principal_type' => $principal,
            'principal_id'   => (int) $this->validated($principal === GrantPrincipal::Role ? 'role_id' : 'admin_id'),
            'entity_type_id' => $this->filled('entity_type_id') ? (int) $this->validated('entity_type_id') : null,
            'record_id'      => (int) $this->root?->id,
            'actions'        => AccessGrantRepository::normalizeActions((array) $this->validated('actions')),
        ];
    }
}
