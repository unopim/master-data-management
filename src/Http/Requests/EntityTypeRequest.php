<?php

namespace Webkul\MasterDataManagement\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Webkul\MasterDataManagement\Enums\CodeStrategy;
use Webkul\MasterDataManagement\Enums\EntityTypeStatus;
use Webkul\MasterDataManagement\Enums\MasteringStyle;
use Webkul\MasterDataManagement\Matching\MatchInbox;
use Webkul\MasterDataManagement\Models\EntityType;
use Webkul\MasterDataManagement\Models\EntityTypeProxy;
use Webkul\MasterDataManagement\Models\IconProxy;
use Webkul\MasterDataManagement\Models\SourceSystemProxy;
use Webkul\MasterDataManagement\Quality\CompletenessCalculator;
use Webkul\MasterDataManagement\Repositories\FieldRepository;
use Webkul\User\Models\AdminProxy;

class EntityTypeRequest extends FormRequest
{
    /**
     * The ACL key that guards the settings deciding who skips approval: data owner and approval required.
     */
    public const string GOVERNANCE_ACL = 'masterdata.entity_types.governance';

    /**
     * The icon select's value prefix for an icon library entry, which the select offers beside the admin font icons.
     */
    public const string LIBRARY_ICON_PREFIX = 'library:';

    public function authorize(): bool
    {
        return bouncer()->hasPermission(
            $this->route('id') ? 'masterdata.entity_types.edit' : 'masterdata.entity_types.create'
        );
    }

    /**
     * Fold the create form's name into the default locale translation, normalize the
     * multiselect, which submits a JSON or comma-separated string, and read the switches as
     * booleans, since a switched-off switch submits an empty value.
     */
    protected function prepareForValidation(): void
    {
        if ($this->isCreating() && $this->filled('name')) {
            $defaultLocale = core()->getDefaultLocaleCodeFromDefaultChannel();
            $translations = (array) $this->input('translations', []);

            $translations[$defaultLocale]['name'] ??= $this->input('name');

            $this->merge(['translations' => $translations]);
        }

        $icon = $this->input('icon');

        if (is_string($icon) && str_starts_with($icon, self::LIBRARY_ICON_PREFIX)) {
            $libraryId = substr($icon, strlen(self::LIBRARY_ICON_PREFIX));

            $this->merge(['icon' => null, 'icon_id' => ctype_digit($libraryId) ? (int) $libraryId : $libraryId]);
        } elseif ($this->exists('icon')) {
            $this->merge(['icon_id' => null]);
        }

        foreach (['is_hierarchical', 'approval_required'] as $switch) {
            if ($this->exists($switch)) {
                $this->merge([$switch => $this->boolean($switch)]);
            }
        }

        $settings = $this->input('settings');

        if (is_array($settings) && is_array($settings['sla_hours'] ?? null)) {
            $settings['sla_hours'] = array_filter(
                $settings['sla_hours'],
                is_numeric(...),
            );
        }

        if (is_array($settings) && is_string($settings['required_source_system_ids'] ?? null)) {
            $settings['required_source_system_ids'] = $this->decodeIds($settings['required_source_system_ids']);
        }

        if (is_array($settings) && array_key_exists('auto_match_threshold', $settings)) {
            $settings['auto_match_threshold'] = is_numeric($settings['auto_match_threshold']) ? (float) $settings['auto_match_threshold'] : null;
        }

        if (is_array($settings) && array_key_exists(EntityType::MATCH_ON_WRITE_SETTING, $settings)) {
            $settings[EntityType::MATCH_ON_WRITE_SETTING] = filter_var($settings[EntityType::MATCH_ON_WRITE_SETTING], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        }

        if (is_array($settings)) {
            $this->merge(['settings' => $settings]);
        }

        $parents = $this->input('allowed_parent_type_ids');

        if (is_string($parents)) {
            $this->merge(['allowed_parent_type_ids' => $this->decodeIds($parents)]);
        }
    }

    /**
     * Decode a multiselect value, submitted as a JSON list or a comma-separated string; numeric ids become integers
     * so the stored list matches the integer option ids the form shows selected.
     *
     * @return array<int, mixed>
     */
    protected function decodeIds(string $value): array
    {
        $decoded = json_decode($value, true);

        $ids = is_array($decoded)
            ? array_values($decoded)
            : array_values(array_filter(array_map(trim(...), explode(',', $value))));

        return array_map(fn (mixed $id): mixed => is_string($id) && ctype_digit($id) ? (int) $id : $id, $ids);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $id = $this->route('id');
        $defaultLocale = core()->getDefaultLocaleCodeFromDefaultChannel();

        $selfParenting = $id && ! $this->boolean('is_hierarchical', (bool) EntityTypeProxy::modelClass()::query()->whereKey($id)->value('is_hierarchical'))
            ? [Rule::notIn([(int) $id])]
            : [];

        return [
            'code'                      => $this->isCreating()
                ? ['required', 'string', 'regex:/^[a-z][a-z0-9_]{1,63}$/', Rule::unique(EntityTypeProxy::modelClass(), 'code')]
                : ['prohibited'],
            'name'                                 => $this->isCreating() ? ['nullable', 'string', 'max:255'] : ['prohibited'],
            'translations'                         => ['sometimes', 'array'],
            'translations.'.$defaultLocale.'.name' => $this->isCreating() ? ['required', 'string', 'max:255'] : ['nullable', 'string', 'max:255'],
            'translations.*.name'                  => ['nullable', 'string', 'max:255'],
            'translations.*.description'           => ['nullable', 'string', 'max:2000'],
            'icon'                                 => ['nullable', 'string', Rule::in(config('masterdata.entity_type_icons', []))],
            'icon_id'                              => ['nullable', 'integer', Rule::exists(IconProxy::modelClass(), 'id')],
            'label_field_code'                     => ['nullable', 'string', Rule::in($this->labelFieldCodes())],
            'is_hierarchical'                      => ['sometimes', 'boolean'],
            'is_scoped'                            => ['sometimes', 'boolean'],
            'allowed_parent_type_ids'              => ['sometimes', 'nullable', 'array'],
            'allowed_parent_type_ids.*'            => ['integer', Rule::exists(EntityTypeProxy::modelClass(), 'id'), ...$selfParenting],
            'approval_required'                    => ['sometimes', 'boolean'],
            'data_owner_id'                        => ['sometimes', 'nullable', 'integer', Rule::exists(AdminProxy::modelClass(), 'id')],
            'mastering_style'                      => ['sometimes', Rule::enum(MasteringStyle::class)],
            'code_strategy'                        => ['sometimes', Rule::enum(CodeStrategy::class)],
            'code_pattern'                         => $this->input('code_strategy') === CodeStrategy::Pattern->value
                ? ['required', 'string', 'max:128', 'regex:/\{seq(:\d{1,2})?\}/']
                : ['nullable', 'string', 'max:128'],
            'status'                                => ['sometimes', Rule::enum(EntityTypeStatus::class)],
            'sort_order'                            => ['sometimes', 'required', 'integer', 'min:0', 'max:9999'],
            'settings'                              => ['sometimes', 'nullable', 'array'],
            'settings.required_source_system_ids'   => ['nullable', 'array'],
            'settings.required_source_system_ids.*' => ['integer', Rule::exists(SourceSystemProxy::modelClass(), 'id')],
            ...array_combine(
                array_map(fn (string $key): string => 'settings.'.$key, array_keys(self::settingsRules())),
                self::settingsRules(),
            ),
        ];
    }

    /**
     * The rules of the entity type settings that hold no ids, keyed by setting; the model bundle import shares them.
     *
     * @return array<string, list<mixed>>
     */
    public static function settingsRules(): array
    {
        return [
            'sla_hours'            => ['nullable', 'array'],
            'sla_hours.*'          => ['nullable', 'integer', 'min:1', 'max:87600'],
            'completeness_scope'   => ['nullable', Rule::in([CompletenessCalculator::SCOPE_ALL, CompletenessCalculator::SCOPE_REQUIRED])],
            'auto_match_threshold' => ['nullable', 'numeric', 'between:'.MatchInbox::MIN_THRESHOLD.','.MatchInbox::MAX_THRESHOLD],
            'match_on_write'       => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Refuse a change of the data owner or of approval required by an admin without the governance permission;
     * resubmitting the stored value is no change. Both decide who may skip approval, so one key guards them.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (bouncer()->hasPermission(self::GOVERNANCE_ACL)) {
                    return;
                }

                $current = $this->isCreating()
                    ? null
                    : EntityTypeProxy::modelClass()::query()->whereKey($this->route('id'))->toBase()->first(['data_owner_id', 'approval_required']);

                $submittedOwner = $this->input('data_owner_id');

                $ownerChanged = $this->exists('data_owner_id')
                    && ($submittedOwner === null || $submittedOwner === '' ? null : (int) $submittedOwner) !== ($current?->data_owner_id === null ? null : (int) $current->data_owner_id);

                $approvalChanged = $this->exists('approval_required')
                    && $this->boolean('approval_required') !== (bool) ($current?->approval_required ?? false);

                foreach (['data_owner_id' => $ownerChanged, 'approval_required' => $approvalChanged] as $key => $changed) {
                    if ($changed) {
                        $validator->errors()->add($key, trans('masterdata::app.entity-types.form.governance-forbidden'));
                    }
                }
            },
        ];
    }

    /**
     * Get the validated payload keyed for the repository: translations become locale-keyed attributes.
     *
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        $data = $this->safe()->except(['translations', 'name']);

        if (blank($data['code_pattern'] ?? null)) {
            unset($data['code_pattern']);
        }

        $locales = core()->getAllActiveLocales()->pluck('code')->all();

        foreach ($this->validated('translations', []) as $locale => $values) {
            if (in_array($locale, $locales, true)) {
                $data[$locale] = $values;
            }
        }

        return $data;
    }

    /**
     * The codes of the type's active fields, the only ones that can drive a record's label; a new type has none yet.
     *
     * @return list<string>
     */
    protected function labelFieldCodes(): array
    {
        if ($this->isCreating()) {
            return [];
        }

        return resolve(FieldRepository::class)->activeFor((int) $this->route('id'))->pluck('code')->values()->all();
    }

    protected function isCreating(): bool
    {
        return ! $this->route('id');
    }
}
