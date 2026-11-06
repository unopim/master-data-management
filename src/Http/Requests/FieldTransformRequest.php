<?php

namespace Webkul\MasterDataManagement\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Webkul\MasterDataManagement\Models\FieldTransform;
use Webkul\MasterDataManagement\Repositories\FieldTransformRepository;
use Webkul\MasterDataManagement\Services\FieldRuleFormOptions;
use Webkul\MasterDataManagement\Standardization\TransformRegistry;

class FieldTransformRequest extends FormRequest
{
    protected ?FieldTransform $fieldTransform = null;

    public function authorize(): bool
    {
        return bouncer()->hasPermission('masterdata.entity_types.edit');
    }

    /**
     * The field is an active field of the type storing its values in the record; the transform is registered.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'field_id'   => ['required', 'integer', Rule::in(array_map(intval(...), resolve(FieldRuleFormOptions::class)->ruleFields((int) $this->route('entityTypeId'))->modelKeys()))],
            'transform'  => ['required', 'string', Rule::in(resolve(TransformRegistry::class)->codes())],
            'sort_order' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:9999'],
        ];
    }

    /**
     * Refuse a transform the field already runs, and a new transform once the type holds the most it may.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->hasAny(['field_id', 'transform'])) {
                    return;
                }

                $repository = resolve(FieldTransformRepository::class);

                $taken = $repository->forEntityType((int) $this->route('entityTypeId'))->contains(
                    fn (FieldTransform $transform): bool => $transform->field_id === (int) $this->input('field_id')
                        && $transform->transform === $this->input('transform')
                        && $transform->id !== $this->fieldTransform()?->id,
                );

                if ($taken) {
                    $validator->errors()->add('transform', trans('masterdata::app.field-transforms.validation.taken'));
                }

                if (! $this->fieldTransform() instanceof FieldTransform && $repository->countFor((int) $this->route('entityTypeId')) >= FieldTransformRepository::MAX_ROWS) {
                    $validator->errors()->add('field_id', trans('masterdata::app.field-transforms.validation.too-many', ['count' => FieldTransformRepository::MAX_ROWS]));
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
            'field_id'   => trans('masterdata::app.field-transforms.form.field'),
            'transform'  => trans('masterdata::app.field-transforms.form.transform'),
            'sort_order' => trans('masterdata::app.field-transforms.form.sort-order'),
        ];
    }

    /**
     * The validated transform keyed for the repository.
     *
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        return [
            'field_id'   => (int) $this->validated('field_id'),
            'transform'  => (string) $this->validated('transform'),
            'sort_order' => (int) ($this->validated('sort_order') ?? 0),
        ];
    }

    /**
     * The field transform being updated, or null on create.
     */
    public function fieldTransform(): ?FieldTransform
    {
        $id = $this->route('id');

        if (! $id) {
            return null;
        }

        if ($this->fieldTransform instanceof FieldTransform) {
            return $this->fieldTransform;
        }

        $transform = resolve(FieldTransformRepository::class)->findFor((int) $this->route('entityTypeId'), (int) $id);

        abort_unless($transform instanceof FieldTransform, 404);

        return $this->fieldTransform = $transform;
    }
}
