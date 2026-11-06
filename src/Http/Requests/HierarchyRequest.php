<?php

namespace Webkul\MasterDataManagement\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Webkul\MasterDataManagement\Models\Hierarchy;
use Webkul\MasterDataManagement\Repositories\HierarchyRepository;

class HierarchyRequest extends FormRequest
{
    protected ?Hierarchy $hierarchy = null;

    public function authorize(): bool
    {
        return bouncer()->hasPermission('masterdata.entity_types.edit');
    }

    /**
     * The code is set once and never names the primary hierarchy; labels follow the relation type form.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $hierarchy = $this->hierarchy();

        return [
            'code' => $hierarchy instanceof Hierarchy
                ? ['sometimes', Rule::in([$hierarchy->code])]
                : ['required', 'string', 'regex:/^[a-z][a-z0-9_]{0,63}$/', Rule::notIn([Hierarchy::PRIMARY_CODE])],
            'label'                                      => [$hierarchy instanceof Hierarchy ? 'sometimes' : 'required', 'array'],
            'label.'.config('masterdata.default_locale') => $hierarchy instanceof Hierarchy ? ['sometimes', 'required', 'string', 'max:255'] : ['required', 'string', 'max:255'],
            'label.*'                                    => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Refuse a code another hierarchy of the type uses and a new hierarchy once the type holds the most it may.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($this->hierarchy() instanceof Hierarchy || $validator->errors()->has('code')) {
                    return;
                }

                $entityTypeId = (int) $this->route('entityTypeId');

                $repository = resolve(HierarchyRepository::class);

                if ($repository->codeTaken($entityTypeId, (string) $this->input('code'))) {
                    $validator->errors()->add('code', trans('masterdata::app.hierarchies.validation.code-taken'));
                }

                if ($repository->countFor($entityTypeId) >= HierarchyRepository::MAX_HIERARCHIES) {
                    $validator->errors()->add('code', trans('masterdata::app.hierarchies.validation.too-many', ['count' => HierarchyRepository::MAX_HIERARCHIES]));
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'code'    => trans('masterdata::app.hierarchies.form.code'),
            'label.*' => trans('masterdata::app.hierarchies.form.label'),
        ];
    }

    /**
     * Get the validated payload keyed for the repository, labels as locale-keyed attributes.
     *
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        $data = [];

        if (! $this->hierarchy() instanceof Hierarchy) {
            $data['code'] = $this->validated('code');
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
     * The hierarchy being updated, or null on create.
     */
    public function hierarchy(): ?Hierarchy
    {
        $id = $this->route('id');

        if (! $id) {
            return null;
        }

        return $this->hierarchy ??= resolve(HierarchyRepository::class)->findFor((int) $this->route('entityTypeId'), (int) $id)
            ?? abort(404);
    }
}
