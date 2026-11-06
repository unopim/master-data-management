<?php

namespace Webkul\MasterDataManagement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Webkul\MasterDataManagement\Services\TemplateReader;

class TemplateInstallRequest extends FormRequest
{
    public function authorize(): bool
    {
        return bouncer()->hasPermission('masterdata.entity_types.templates');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'template'     => ['required', 'string', Rule::in(resolve(TemplateReader::class)->available())],
            'with_samples' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'template' => trans('masterdata::app.templates.template'),
        ];
    }
}
