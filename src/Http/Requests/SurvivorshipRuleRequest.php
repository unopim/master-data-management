<?php

namespace Webkul\MasterDataManagement\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Webkul\MasterDataManagement\Models\Field;
use Webkul\MasterDataManagement\Models\SourceSystemProxy;
use Webkul\MasterDataManagement\Models\SurvivorshipRule;
use Webkul\MasterDataManagement\Repositories\FieldRepository;
use Webkul\MasterDataManagement\Repositories\SurvivorshipRuleRepository;
use Webkul\MasterDataManagement\Survivorship\Strategies\SourcePriorityStrategy;
use Webkul\MasterDataManagement\Survivorship\StrategyRegistry;

class SurvivorshipRuleRequest extends FormRequest
{
    protected ?SurvivorshipRule $survivorshipRule = null;

    public function authorize(): bool
    {
        return bouncer()->hasPermission('masterdata.entity_types.edit');
    }

    /**
     * Read an empty or `*` target as the default rule and the source order multiselect, sent as a JSON list or a
     * comma-separated string, as a list of ids.
     */
    protected function prepareForValidation(): void
    {
        if (in_array($this->input('target'), ['', SurvivorshipRule::TARGET_DEFAULT], true)) {
            $this->merge(['target' => null]);
        }

        $order = $this->input('options.source_order');

        if (is_string($order)) {
            $decoded = json_decode($order, true);

            $ids = is_array($decoded) ? array_values($decoded) : array_values(array_filter(array_map(trim(...), explode(',', $order)), fn (string $id): bool => $id !== ''));

            $this->merge(['options' => [...(array) $this->input('options'), 'source_order' => $ids]]);
        }
    }

    /**
     * The target is empty for the type default, the record label or an active field of the type, once per type.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'target'                 => ['present', 'nullable', 'string', Rule::in($this->targets())],
            'strategy'               => ['required', 'string', Rule::in(resolve(StrategyRegistry::class)->codes())],
            'sort_order'             => ['sometimes', 'nullable', 'integer', 'min:0', 'max:9999'],
            'options'                => ['sometimes', 'nullable', 'array'],
            'options.source_order'   => ['nullable', 'array', 'max:100'],
            'options.source_order.*' => ['integer', 'distinct', Rule::exists(SourceSystemProxy::modelClass(), 'id')],
        ];
    }

    /**
     * Refuse a second rule for a target, the default included, and a new rule once the type holds the most it may.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->has('target')) {
                    return;
                }

                $entityTypeId = (int) $this->route('entityTypeId');

                $repository = resolve(SurvivorshipRuleRepository::class);

                $target = $this->input('target');

                if ($repository->targetTaken($entityTypeId, is_string($target) ? $target : null, $this->survivorshipRule()?->id)) {
                    $validator->errors()->add('target', trans('masterdata::app.survivorship-rules.validation.target-taken'));
                }

                if (! $this->survivorshipRule() instanceof SurvivorshipRule && $repository->countFor($entityTypeId) >= SurvivorshipRuleRepository::MAX_RULES) {
                    $validator->errors()->add('target', trans('masterdata::app.survivorship-rules.validation.too-many', ['count' => SurvivorshipRuleRepository::MAX_RULES]));
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
            'target'                 => trans('masterdata::app.survivorship-rules.form.target'),
            'strategy'               => trans('masterdata::app.survivorship-rules.form.strategy'),
            'sort_order'             => trans('masterdata::app.survivorship-rules.form.sort-order'),
            'options.source_order'   => trans('masterdata::app.survivorship-rules.form.source-order'),
            'options.source_order.*' => trans('masterdata::app.survivorship-rules.form.source-order'),
        ];
    }

    /**
     * The validated rule keyed for the repository; the source order is kept only for the source priority strategy.
     *
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        $strategy = (string) $this->validated('strategy');

        $order = array_map(intval(...), array_values((array) ($this->validated('options')['source_order'] ?? [])));

        return [
            'target'     => $this->validated('target'),
            'strategy'   => $strategy,
            'sort_order' => (int) ($this->validated('sort_order') ?? 0),
            'options'    => $strategy === SourcePriorityStrategy::CODE && $order !== [] ? [SurvivorshipRule::OPTION_SOURCE_ORDER => $order] : null,
        ];
    }

    /**
     * The survivorship rule being updated, or null on create.
     */
    public function survivorshipRule(): ?SurvivorshipRule
    {
        $id = $this->route('id');

        if (! $id) {
            return null;
        }

        return $this->survivorshipRule ??= resolve(SurvivorshipRuleRepository::class)->findFor((int) $this->route('entityTypeId'), (int) $id)
            ?? abort(404);
    }

    /**
     * The targets a rule of the type may decide: the record label and the type's active fields.
     *
     * @return list<string>
     */
    protected function targets(): array
    {
        return [
            SurvivorshipRule::TARGET_LABEL,
            ...resolve(FieldRepository::class)->activeFor((int) $this->route('entityTypeId'))
                ->map(fn (Field $field): string => (string) $field->code)
                ->values()
                ->all(),
        ];
    }
}
