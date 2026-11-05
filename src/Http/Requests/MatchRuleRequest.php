<?php

namespace Webkul\MasterDataManagement\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Webkul\MasterDataManagement\Matching\ComparatorRegistry;
use Webkul\MasterDataManagement\Models\Field;
use Webkul\MasterDataManagement\Models\MatchRule;
use Webkul\MasterDataManagement\Models\MatchRuleProxy;
use Webkul\MasterDataManagement\Repositories\FieldRepository;
use Webkul\MasterDataManagement\Repositories\MatchRuleRepository;

class MatchRuleRequest extends FormRequest
{
    protected ?MatchRule $matchRule = null;

    public function authorize(): bool
    {
        return bouncer()->hasPermission('masterdata.entity_types.edit');
    }

    /**
     * Read the blocking switch as a boolean.
     */
    protected function prepareForValidation(): void
    {
        if ($this->exists('is_blocking')) {
            $this->merge(['is_blocking' => $this->boolean('is_blocking')]);
        }
    }

    /**
     * The target is the record code, its label or an active field of the type, compared once per comparator.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $entityTypeId = (int) $this->route('entityTypeId');

        return [
            'target'            => ['required', 'string', Rule::in($this->targets())],
            'comparator'        => [
                'required',
                'string',
                Rule::in(resolve(ComparatorRegistry::class)->codes()),
                Rule::unique(MatchRuleProxy::modelClass(), 'comparator')
                    ->where('entity_type_id', $entityTypeId)
                    ->where('target', (string) $this->input('target'))
                    ->ignore($this->matchRule()?->id),
            ],
            'weight'            => ['required', 'numeric', 'between:0.1,100'],
            'is_blocking'       => ['sometimes', 'boolean'],
            'sort_order'        => ['sometimes', 'nullable', 'integer', 'min:0', 'max:9999'],
            'options'           => ['sometimes', 'nullable', 'array'],
            'options.tolerance' => ['nullable', 'numeric', 'min:0'],
            'options.days'      => ['nullable', 'numeric', 'min:0'],
        ];
    }

    /**
     * Refuse a blocking rule on a field that is not searchable, since only indexed values can be looked up, and a new
     * rule once the type holds the most rules it may.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $entityTypeId = (int) $this->route('entityTypeId');

                $target = (string) $this->input('target');

                if ($this->boolean('is_blocking') && ! in_array($target, [MatchRule::TARGET_CODE, MatchRule::TARGET_LABEL], true)
                    && ! resolve(FieldRepository::class)->searchableFor($entityTypeId)->contains('code', $target)) {
                    $validator->errors()->add('is_blocking', trans('masterdata::app.match-rules.validation.blocking-not-searchable'));
                }

                if (! $this->matchRule() instanceof MatchRule
                    && resolve(MatchRuleRepository::class)->countFor($entityTypeId) >= MatchRuleRepository::MAX_RULES) {
                    $validator->errors()->add('target', trans('masterdata::app.match-rules.validation.too-many', ['count' => MatchRuleRepository::MAX_RULES]));
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
            'target'            => trans('masterdata::app.match-rules.form.target'),
            'comparator'        => trans('masterdata::app.match-rules.form.comparator'),
            'weight'            => trans('masterdata::app.match-rules.form.weight'),
            'is_blocking'       => trans('masterdata::app.match-rules.form.is-blocking'),
            'sort_order'        => trans('masterdata::app.match-rules.form.sort-order'),
            'options.tolerance' => trans('masterdata::app.match-rules.form.tolerance'),
            'options.days'      => trans('masterdata::app.match-rules.form.days'),
        ];
    }

    /**
     * The validated rule keyed for the repository; only the option the comparator reads is kept.
     *
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        $comparator = (string) $this->validated('comparator');

        $option = match ($comparator) {
            'numeric' => 'tolerance',
            'date'    => 'days',
            default   => null,
        };

        $value = $option === null ? null : ($this->validated('options')[$option] ?? null);

        return [
            'target'      => $this->validated('target'),
            'comparator'  => $comparator,
            'weight'      => (float) $this->validated('weight'),
            'is_blocking' => (bool) $this->validated('is_blocking', false),
            'sort_order'  => (int) ($this->validated('sort_order') ?? 0),
            'options'     => $value === null ? null : [$option => (float) $value],
        ];
    }

    /**
     * The match rule being updated, or null on create.
     */
    public function matchRule(): ?MatchRule
    {
        $id = $this->route('id');

        if (! $id) {
            return null;
        }

        return $this->matchRule ??= resolve(MatchRuleRepository::class)->findFor((int) $this->route('entityTypeId'), (int) $id)
            ?? abort(404);
    }

    /**
     * The targets a rule of the type may compare: the record code and label and the type's active fields.
     *
     * @return list<string>
     */
    protected function targets(): array
    {
        return [
            MatchRule::TARGET_CODE,
            MatchRule::TARGET_LABEL,
            ...resolve(FieldRepository::class)->activeFor((int) $this->route('entityTypeId'))
                ->map(fn (Field $field): string => (string) $field->code)
                ->values()
                ->all(),
        ];
    }
}
