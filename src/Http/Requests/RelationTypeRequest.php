<?php

namespace Webkul\MasterDataManagement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Webkul\MasterDataManagement\Enums\RelationCardinality;
use Webkul\MasterDataManagement\Models\EntityTypeProxy;
use Webkul\MasterDataManagement\Models\RelationType;
use Webkul\MasterDataManagement\Models\RelationTypeProxy;
use Webkul\MasterDataManagement\Repositories\RelationRepository;
use Webkul\MasterDataManagement\Repositories\RelationTypeRepository;

class RelationTypeRequest extends FormRequest
{
    protected ?RelationType $relationType = null;

    public function authorize(): bool
    {
        return bouncer()->hasPermission('masterdata.entity_types.edit');
    }

    /**
     * The code never changes; the target type and cardinality are fixed once relations of the type exist.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $type = $this->relationType();

        $locked = $type instanceof RelationType && resolve(RelationRepository::class)->existsForType((int) $type->id);

        return [
            'code' => $type instanceof RelationType
                ? ['sometimes', Rule::in([$type->code])]
                : ['required', 'string', 'regex:/^[a-z][a-z0-9_]{0,63}$/', Rule::unique(RelationTypeProxy::modelClass(), 'code')],
            'to_entity_type_id' => $locked
                ? ['sometimes', 'integer', Rule::in([$type->to_entity_type_id])]
                : ['required', 'integer', Rule::exists(EntityTypeProxy::modelClass(), 'id')],
            'cardinality' => $locked
                ? ['sometimes', Rule::in([$type->cardinality->value])]
                : ['required', Rule::enum(RelationCardinality::class)],
            'label'                                      => [$type instanceof RelationType ? 'sometimes' : 'required', 'array'],
            'label.'.config('masterdata.default_locale') => $type instanceof RelationType ? ['sometimes', 'required', 'string', 'max:255'] : ['required', 'string', 'max:255'],
            'label.*'                                    => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'code'              => trans('masterdata::app.relation-types.form.code'),
            'to_entity_type_id' => trans('masterdata::app.relation-types.form.target-type'),
            'cardinality'       => trans('masterdata::app.relation-types.form.cardinality'),
            'label.*'           => trans('masterdata::app.relation-types.form.label'),
        ];
    }

    /**
     * Get the validated payload keyed for the repository, labels as locale-keyed attributes.
     *
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        $data = $this->safe()->only(['to_entity_type_id', 'cardinality']);

        if (! $this->relationType() instanceof RelationType) {
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
     * The non-system relation type being updated, or null on create.
     */
    public function relationType(): ?RelationType
    {
        $id = $this->route('id');

        if (! $id) {
            return null;
        }

        $type = $this->relationType ??= resolve(RelationTypeRepository::class)->findFrom((int) $this->route('entityTypeId'), (int) $id)
            ?? abort(404);

        abort_if($type->is_system, 422, trans('masterdata::app.relation-types.validation.system-type'));

        return $type;
    }
}
