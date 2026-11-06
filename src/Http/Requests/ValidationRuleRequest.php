<?php

namespace Webkul\MasterDataManagement\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Webkul\Core\Repositories\LocaleRepository;
use Webkul\MasterDataManagement\Enums\ValidationSeverity;
use Webkul\MasterDataManagement\Models\Field;
use Webkul\MasterDataManagement\Models\FieldValidationRule;
use Webkul\MasterDataManagement\Repositories\FieldValidationRuleRepository;
use Webkul\MasterDataManagement\Services\FieldRuleFormOptions;
use Webkul\MasterDataManagement\Validation\Contracts\ValueRule;
use Webkul\MasterDataManagement\Validation\RuleTypeRegistry;

class ValidationRuleRequest extends FormRequest
{
    protected ?FieldValidationRule $validationRule = null;

    public function authorize(): bool
    {
        return bouncer()->hasPermission('masterdata.entity_types.edit');
    }

    /**
     * Let the chosen rule type coerce its params, and drop blank messages.
     */
    protected function prepareForValidation(): void
    {
        $messages = $this->input('messages');

        if (is_array($messages)) {
            $this->merge(['messages' => array_filter($messages, fn (mixed $message): bool => is_string($message) && trim($message) !== '')]);
        }

        $type = $this->ruleType();

        if ($type instanceof ValueRule) {
            $this->merge(['params' => $type->prepare(is_array($this->input('params')) ? $this->input('params') : [])]);
        }
    }

    /**
     * The field is an active field of the type storing its values in the record; the params follow the rule type.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $field = $this->field();

        $type = $this->ruleType();

        return [
            'field_id'   => ['required', 'integer', Rule::in($this->fieldIds())],
            'type'       => ['required', 'string', Rule::in(resolve(RuleTypeRegistry::class)->codes())],
            'severity'   => ['required', Rule::enum(ValidationSeverity::class)],
            'params'     => ['sometimes', 'nullable', 'array'],
            'messages'   => ['sometimes', 'nullable', 'array:'.implode(',', $this->localeCodes())],
            'messages.*' => ['string', 'max:500'],
            'sort_order' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:9999'],
            ...($field instanceof Field && $type instanceof ValueRule ? $type->paramRules($field, (array) $this->input('params', [])) : []),
        ];
    }

    /**
     * Refuse a new rule once the type holds the most it may.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($this->validationRule() instanceof FieldValidationRule) {
                    return;
                }

                if (resolve(FieldValidationRuleRepository::class)->countFor((int) $this->route('entityTypeId')) >= FieldValidationRuleRepository::MAX_ROWS) {
                    $validator->errors()->add('field_id', trans('masterdata::app.validation-rules.validation.too-many', ['count' => FieldValidationRuleRepository::MAX_ROWS]));
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
            'field_id'        => trans('masterdata::app.validation-rules.form.field'),
            'type'            => trans('masterdata::app.validation-rules.form.type'),
            'severity'        => trans('masterdata::app.validation-rules.form.severity'),
            'messages'        => trans('masterdata::app.validation-rules.form.messages'),
            'messages.*'      => trans('masterdata::app.validation-rules.form.messages'),
            'sort_order'      => trans('masterdata::app.validation-rules.form.sort-order'),
            'params.pattern'  => trans('masterdata::app.validation-rules.form.pattern'),
            'params.value'    => trans('masterdata::app.validation-rules.form.value'),
            'params.min'      => trans('masterdata::app.validation-rules.form.min-length'),
            'params.max'      => trans('masterdata::app.validation-rules.form.max-length'),
            'params.field'    => trans('masterdata::app.validation-rules.form.other-field'),
            'params.operator' => trans('masterdata::app.validation-rules.form.operator'),
            'params.codes'    => trans('masterdata::app.validation-rules.form.codes'),
            'params.codes.*'  => trans('masterdata::app.validation-rules.form.codes'),
            'params.after'    => trans('masterdata::app.validation-rules.form.after'),
            'params.before'   => trans('masterdata::app.validation-rules.form.before'),
        ];
    }

    /**
     * The validated rule keyed for the repository; only the params the rule type declares are kept.
     *
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        $type = $this->ruleType();

        $field = $this->field();

        $keys = $type instanceof ValueRule && $field instanceof Field
            ? array_unique(array_map(fn (string $key): string => explode('.', $key)[1] ?? '', array_keys($type->paramRules($field, (array) $this->input('params', [])))))
            : [];

        $params = array_filter(
            array_intersect_key((array) ($this->validated('params') ?? []), array_flip($keys)),
            fn (mixed $param): bool => $param !== null && $param !== '',
        );

        $messages = (array) ($this->validated('messages') ?? []);

        return [
            'field_id'   => (int) $this->validated('field_id'),
            'type'       => (string) $this->validated('type'),
            'severity'   => (string) $this->validated('severity'),
            'params'     => $params === [] ? null : $params,
            'messages'   => $messages === [] ? null : $messages,
            'sort_order' => (int) ($this->validated('sort_order') ?? 0),
        ];
    }

    /**
     * The validation rule being updated, or null on create.
     */
    public function validationRule(): ?FieldValidationRule
    {
        $id = $this->route('id');

        if (! $id) {
            return null;
        }

        if ($this->validationRule instanceof FieldValidationRule) {
            return $this->validationRule;
        }

        $rule = resolve(FieldValidationRuleRepository::class)->findFor((int) $this->route('entityTypeId'), (int) $id);

        abort_unless($rule instanceof FieldValidationRule, 404);

        return $this->validationRule = $rule;
    }

    /**
     * The registered rule type the input names, or null when it names none.
     */
    protected function ruleType(): ?ValueRule
    {
        $code = $this->input('type');

        $registry = resolve(RuleTypeRegistry::class);

        return is_string($code) && $registry->has($code) ? $registry->get($code) : null;
    }

    /**
     * The field the input names, when it is one the type's rules may cover.
     */
    protected function field(): ?Field
    {
        $id = $this->input('field_id');

        return is_numeric($id) ? $this->ruleFields()->firstWhere('id', (int) $id) : null;
    }

    /**
     * @return list<int>
     */
    protected function fieldIds(): array
    {
        return array_values(array_map(intval(...), $this->ruleFields()->modelKeys()));
    }

    /**
     * @return Collection<int, Field>
     */
    protected function ruleFields(): Collection
    {
        return resolve(FieldRuleFormOptions::class)->ruleFields((int) $this->route('entityTypeId'));
    }

    /**
     * The codes of the active locales a message may be given in.
     *
     * @return list<string>
     */
    protected function localeCodes(): array
    {
        return array_values(array_map(strval(...), resolve(LocaleRepository::class)->getActiveLocales()->pluck('code')->all()));
    }
}
