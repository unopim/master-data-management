<?php

namespace Webkul\MasterDataManagement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Approve or reject a held record change; the controller checks the admin may decide that request.
 */
class ChangeRequestDecisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->guard('admin')->check();
    }

    /**
     * A rejection needs a comment that tells the requester why.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'comment' => [$this->routeIs('admin.masterdata.approvals.reject') ? 'required' : 'nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'comment' => trans('masterdata::app.approvals.fields.comment'),
        ];
    }
}
