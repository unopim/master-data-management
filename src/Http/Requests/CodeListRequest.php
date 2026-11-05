<?php

namespace Webkul\MasterDataManagement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Webkul\MasterDataManagement\Models\CodeListProxy;

class CodeListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return bouncer()->hasPermission(
            $this->isCreating() ? 'masterdata.code_lists.create' : 'masterdata.code_lists.edit'
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $defaultLocale = config('masterdata.default_locale');

        return [
            'code' => $this->isCreating()
                ? ['required', 'string', 'regex:/^[a-z][a-z0-9_]{0,63}$/', Rule::unique(CodeListProxy::modelClass(), 'code')]
                : ['prohibited'],
            'owner'                                => ['nullable', 'string', 'max:255'],
            'is_versioned'                         => ['sometimes', 'boolean'],
            'translations'                         => ['required', 'array'],
            'translations.'.$defaultLocale.'.name' => ['required', 'string', 'max:255'],
            'translations.*.name'                  => ['nullable', 'string', 'max:255'],
            'translations.*.description'           => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'code'                       => trans('masterdata::app.code-lists.form.code'),
            'owner'                      => trans('masterdata::app.code-lists.form.owner'),
            'translations.*.name'        => trans('masterdata::app.code-lists.form.name'),
            'translations.*.description' => trans('masterdata::app.code-lists.form.description'),
        ];
    }

    /**
     * Get the validated payload keyed for the repository: translations become locale-keyed attributes.
     *
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        $data = $this->safe()->except(['translations']);

        $locales = core()->getAllActiveLocales()->pluck('code')->all();

        foreach ((array) $this->validated('translations', []) as $locale => $values) {
            if (in_array($locale, $locales, true)) {
                $data[$locale] = [
                    'name'        => $values['name'] ?? null,
                    'description' => $values['description'] ?? null,
                ];
            }
        }

        return $data;
    }

    protected function isCreating(): bool
    {
        return ! $this->route('id');
    }
}
