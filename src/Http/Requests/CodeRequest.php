<?php

namespace Webkul\MasterDataManagement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Webkul\MasterDataManagement\Models\Code;
use Webkul\MasterDataManagement\Models\CodeProxy;
use Webkul\MasterDataManagement\Repositories\CodeRepository;

class CodeRequest extends FormRequest
{
    protected ?Code $existingCode = null;

    public function authorize(): bool
    {
        return bouncer()->hasPermission('masterdata.code_lists.edit');
    }

    /**
     * Blank optional values arrive as empty strings from the form and are stored as null.
     */
    protected function prepareForValidation(): void
    {
        foreach (['parent_code', 'effective_from', 'effective_to'] as $key) {
            if ($this->input($key) === '') {
                $this->merge([$key => null]);
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $code = $this->existingCode();
        $versionId = $this->versionId();
        $defaultLocale = config('masterdata.default_locale');

        return [
            'code' => $code instanceof Code
                ? ['sometimes', Rule::in([$code->code])]
                : [
                    'required',
                    'string',
                    'max:128',
                    'regex:/^[A-Za-z0-9][A-Za-z0-9_.\-]*$/',
                    Rule::unique(CodeProxy::modelClass(), 'code')->where('version_id', $versionId),
                ],
            'parent_code' => [
                'nullable',
                'string',
                'max:128',
                Rule::notIn([$code?->code ?? $this->input('code')]),
                Rule::exists(CodeProxy::modelClass(), 'code')->where('version_id', $versionId),
            ],
            'sort'                  => ['sometimes', 'integer', 'min:0', 'max:4294967295'],
            'is_active'             => ['sometimes', 'boolean'],
            'effective_from'        => ['nullable', 'date'],
            'effective_to'          => ['nullable', 'date', ...($this->filled('effective_from') ? ['after:effective_from'] : [])],
            'label'                 => ['required', 'array'],
            'label.'.$defaultLocale => ['required', 'string', 'max:255'],
            'label.*'               => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'code'           => trans('masterdata::app.codes.form.code'),
            'parent_code'    => trans('masterdata::app.codes.form.parent-code'),
            'sort'           => trans('masterdata::app.codes.form.sort'),
            'effective_from' => trans('masterdata::app.codes.form.effective-from'),
            'effective_to'   => trans('masterdata::app.codes.form.effective-to'),
            'label.*'        => trans('masterdata::app.codes.form.label'),
        ];
    }

    /**
     * Get the validated payload keyed for the repository: labels become locale-keyed attributes.
     *
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        $data = $this->safe()->except(['label', 'code']);

        if (! $this->existingCode() instanceof Code) {
            $data['code'] = $this->validated('code');
            $data['version_id'] = $this->versionId();
        }

        $locales = core()->getAllActiveLocales()->pluck('code')->all();

        foreach ((array) $this->validated('label', []) as $locale => $label) {
            if (in_array($locale, $locales, true)) {
                $data[$locale] = ['label' => $label];
            }
        }

        return $data;
    }

    /**
     * The code being updated, or null on create.
     */
    public function existingCode(): ?Code
    {
        $id = $this->route('codeId');

        if (! $id) {
            return null;
        }

        return $this->existingCode ??= resolve(CodeRepository::class)->findForVersion($this->versionId(), (int) $id)
            ?? abort(404);
    }

    /**
     * The id of the version the code belongs to, from the route.
     */
    public function versionId(): int
    {
        return (int) $this->route('versionId');
    }
}
