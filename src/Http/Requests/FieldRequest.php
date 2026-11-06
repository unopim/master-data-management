<?php

namespace Webkul\MasterDataManagement\Http\Requests;

use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Webkul\Attribute\Rules\ValidationTypes;
use Webkul\MasterDataManagement\FieldTypes\FieldTypeRegistry;
use Webkul\MasterDataManagement\Models\CodeListProxy;
use Webkul\MasterDataManagement\Models\EntityTypeProxy;
use Webkul\MasterDataManagement\Models\Field;
use Webkul\MasterDataManagement\Models\FieldProxy;
use Webkul\MasterDataManagement\Repositories\FieldRepository;
use Webkul\MasterDataManagement\Repositories\RelationRepository;
use Webkul\MasterDataManagement\Rules\CompilableRegex;
use Webkul\MasterDataManagement\Services\SchemaPublisher;

class FieldRequest extends FormRequest
{
    /**
     * The field types that draw their values from a code list.
     */
    public const CODE_LIST_TYPES = ['select', 'multiselect'];

    /**
     * The keys the `validation` JSON accepts.
     */
    public const VALIDATION_KEYS = ['type', 'regex', 'min', 'max', 'max_length'];

    protected ?Field $field = null;

    public function authorize(): bool
    {
        return bouncer()->hasPermission('masterdata.entity_types.edit');
    }

    /**
     * A unique field is always searchable, since the uniqueness check reads the index; only select
     * types keep a code list and only reference types keep a reference entity type. References are
     * relations between records, never per locale.
     */
    protected function prepareForValidation(): void
    {
        $field = $this->field();

        $isUnique = $this->has('is_unique') ? $this->boolean('is_unique') : ($field?->is_unique ?? false);

        if ($isUnique) {
            $this->merge(['is_searchable' => true]);
        }

        $type = $this->fieldType();

        if (! in_array($type, self::CODE_LIST_TYPES, true)) {
            $this->merge(['code_list_id' => null]);
        }

        if ($type !== 'reference') {
            $this->merge(['reference_entity_type_id' => null]);
        } else {
            $this->merge(['value_per_locale' => false]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $field = $this->field();
        $type = $this->fieldType();
        $defaultLocale = config('masterdata.default_locale');

        $rules = [
            'code' => $field instanceof Field
                ? ['sometimes', Rule::in([$field->code])]
                : [
                    'required',
                    'string',
                    'regex:/^[a-z][a-z0-9_]{0,63}$/',
                    Rule::unique(FieldProxy::modelClass(), 'code')->where('entity_type_id', $this->entityTypeId()),
                ],
            'type' => $field instanceof Field
                ? ['sometimes', Rule::in([$field->type])]
                : ['required', 'string', Rule::in(resolve(FieldTypeRegistry::class)->codes())],
            'group'                    => ['nullable', 'string', 'max:64'],
            'position'                 => ['sometimes', 'integer', 'min:0', 'max:65535'],
            'label'                    => [$field instanceof Field ? 'sometimes' : 'required', 'array'],
            'label.'.$defaultLocale    => $field instanceof Field ? ['sometimes', 'required', 'string', 'max:255'] : ['required', 'string', 'max:255'],
            'label.*'                  => ['nullable', 'string', 'max:255'],
            'code_list_id'             => [
                'nullable',
                Rule::requiredIf(in_array($type, self::CODE_LIST_TYPES, true)),
                'integer',
                Rule::exists(CodeListProxy::modelClass(), 'id'),
            ],
            'reference_entity_type_id' => [
                'nullable',
                Rule::requiredIf($type === 'reference'),
                'integer',
                Rule::exists(EntityTypeProxy::modelClass(), 'id'),
            ],
            'validation'            => ['nullable', 'array:'.implode(',', self::VALIDATION_KEYS)],
            'validation.type'       => ['nullable', Rule::in(ValidationTypes::VALIDATION_TYPES)],
            'validation.regex'      => ['nullable', 'required_if:validation.type,regex', 'string', 'max:255', new CompilableRegex],
            'validation.min'        => ['nullable', 'numeric'],
            'validation.max'        => ['nullable', 'numeric'],
            'validation.max_length' => ['nullable', 'integer', 'min:1', 'max:65535'],
        ];

        foreach (SchemaPublisher::FLAGS as $flag) {
            $rules[$flag] = ['sometimes', 'boolean'];
        }

        if ($this->referenceShapeLocked()) {
            $rules['reference_entity_type_id'] = ['sometimes', $this->unchanged((int) $field->reference_entity_type_id)];
            $rules['multiple'] = ['sometimes', 'boolean', $this->unchanged((bool) $field->multiple)];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'code'                     => trans('masterdata::app.fields.form.code'),
            'type'                     => trans('masterdata::app.fields.form.type'),
            'label.*'                  => trans('masterdata::app.fields.form.label'),
            'code_list_id'             => trans('masterdata::app.fields.form.code-list'),
            'reference_entity_type_id' => trans('masterdata::app.fields.form.reference-type'),
            'multiple'                 => trans('masterdata::app.fields.form.flags.multiple'),
            'validation.type'          => trans('masterdata::app.fields.form.validation-type'),
            'validation.regex'         => trans('masterdata::app.fields.form.validation-regex'),
        ];
    }

    /**
     * Get the validated payload keyed for the repository: labels become locale-keyed attributes and
     * the blank validation keys are dropped.
     *
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        $data = $this->safe()->except(['label', 'validation', 'code', 'type']);

        if (! $this->field() instanceof Field) {
            $data['code'] = $this->validated('code');
            $data['type'] = $this->validated('type');
        }

        if ($this->has('validation')) {
            $validation = array_filter(
                Arr::only((array) $this->validated('validation', []), self::VALIDATION_KEYS),
                fn ($value): bool => $value !== null && $value !== '',
            );

            $data['validation'] = $validation === [] ? null : $validation;
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
     * The field being updated, or null on create.
     */
    public function field(): ?Field
    {
        $id = $this->route('id');

        if (! $id) {
            return null;
        }

        return $this->field ??= resolve(FieldRepository::class)->findForEntityType($this->entityTypeId(), (int) $id)
            ?? abort(404);
    }

    /**
     * The id of the entity type the field belongs to, from the route.
     */
    public function entityTypeId(): int
    {
        return (int) $this->route('entityTypeId');
    }

    /**
     * The type of the field: the stored type on update, which cannot change, else the submitted type.
     */
    protected function fieldType(): ?string
    {
        $type = $this->field()?->type ?? $this->input('type');

        return is_string($type) ? $type : null;
    }

    /**
     * Whether the field is a reference field whose relations exist, so its target type and cardinality are fixed.
     */
    public function referenceShapeLocked(): bool
    {
        $field = $this->field();

        return $field instanceof Field
            && $field->type === 'reference'
            && resolve(RelationRepository::class)->fieldIdsWithRelations([(int) $field->id]) !== [];
    }

    /**
     * A rule that fails when the submitted value differs from the stored one.
     */
    protected function unchanged(int|bool $current): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($current): void {
            $submitted = is_bool($current) ? filter_var($value, FILTER_VALIDATE_BOOL) : (int) $value;

            if ($submitted !== $current) {
                $fail('masterdata::app.fields.validation.reference-locked')->translate();
            }
        };
    }
}
