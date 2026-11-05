@php
    $selectedParents = json_encode(array_map(intval(...), (array) (old('allowed_parent_type_ids') ?? ($entityType->allowed_parent_type_ids ?? []))));
    $defaultLocaleCode = core()->getDefaultLocaleCodeFromDefaultChannel();
    $completenessScope = old('settings.completeness_scope') ?? ($entityType->settings['completeness_scope'] ?? 'all');
    $autoMatchThreshold = old('settings.auto_match_threshold') ?? ($entityType->settings['auto_match_threshold'] ?? null);
    $matchOnWrite = (bool) (old('settings.match_on_write') ?? $entityType->matchesOnWrite());
@endphp

<div class="flex gap-2.5 mt-3.5 max-xl:flex-wrap">
    <div class="flex flex-col gap-2 flex-1 max-xl:flex-auto">
        <div class="p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
            <x-admin::heading
                class="mb-4"
                :title="trans('masterdata::app.entity-types.form.basics')"
            />

            @if ($entityType->exists)
                <x-admin::form.control-group>
                    <x-admin::form.control-group.label>
                        @lang('masterdata::app.entity-types.form.code')
                    </x-admin::form.control-group.label>

                    <x-admin::form.control-group.control
                        type="text"
                        name="code_display"
                        :value="$entityType->code"
                        :label="trans('masterdata::app.entity-types.form.code')"
                        disabled="disabled"
                    />

                    <x-admin::form.control-group.help>
                        @lang('masterdata::app.help.entity-types.code')
                    </x-admin::form.control-group.help>
                </x-admin::form.control-group>
            @else
                <x-admin::form.control-group>
                    <x-admin::form.control-group.label class="required">
                        @lang('masterdata::app.entity-types.form.code')
                    </x-admin::form.control-group.label>

                    <x-admin::form.control-group.control
                        type="text"
                        name="code"
                        rules="required"
                        :value="old('code')"
                        :label="trans('masterdata::app.entity-types.form.code')"
                        :placeholder="trans('masterdata::app.entity-types.form.code')"
                    />

                    <x-admin::form.control-group.help>
                        @lang('masterdata::app.help.entity-types.code')
                    </x-admin::form.control-group.help>

                    <x-admin::form.control-group.error control-name="code" />
                </x-admin::form.control-group>
            @endif

            <x-admin::form.control-group>
                <div class="flex items-center justify-between gap-2">
                    <x-admin::form.control-group.label>
                        @lang('masterdata::app.entity-types.form.icon')
                    </x-admin::form.control-group.label>

                    @if (bouncer()->hasPermission('masterdata.icons'))
                        <a
                            href="{{ route('admin.masterdata.icons.index') }}"
                            class="flex items-center gap-1 mb-1.5 text-xs font-semibold text-primary-600 dark:text-primary-400 hover:underline"
                        >
                            <span class="icon-image text-base" aria-hidden="true"></span>

                            @lang('masterdata::app.entity-types.form.manage-icons')
                        </a>
                    @endif
                </div>

                <x-admin::form.control-group.control
                    type="select"
                    name="icon"
                    :value="old('icon') ?? ($entityType->icon_id ? \Webkul\MasterDataManagement\Http\Requests\EntityTypeRequest::LIBRARY_ICON_PREFIX.$entityType->icon_id : $entityType->icon)"
                    :label="trans('masterdata::app.entity-types.form.icon')"
                    :placeholder="trans('masterdata::app.entity-types.form.select-icon')"
                    :options="json_encode($iconOptions)"
                    track-by="id"
                    label-by="label"
                />

                <x-admin::form.control-group.help>
                    @lang('masterdata::app.help.entity-types.icon')
                </x-admin::form.control-group.help>

                <x-admin::form.control-group.error control-name="icon" />
            </x-admin::form.control-group>

            @if ($entityType->exists)
                <x-admin::form.control-group>
                    <x-admin::form.control-group.label>
                        @lang('masterdata::app.entity-types.form.label-field-code')
                    </x-admin::form.control-group.label>

                    <x-admin::form.control-group.control
                        type="select"
                        name="label_field_code"
                        :value="old('label_field_code') ?? $entityType->label_field_code"
                        :label="trans('masterdata::app.entity-types.form.label-field-code')"
                        :placeholder="trans('masterdata::app.entity-types.form.label-field-code')"
                        :options="json_encode($labelFieldOptions)"
                        track-by="id"
                        label-by="label"
                    />

                    <x-admin::form.control-group.help>
                        @lang('masterdata::app.help.entity-types.label-field-code')
                    </x-admin::form.control-group.help>

                    <x-admin::form.control-group.error control-name="label_field_code" />
                </x-admin::form.control-group>
            @endif

            <x-admin::form.control-group>
                <x-admin::form.control-group.label>
                    @lang('masterdata::app.entity-types.form.status')
                </x-admin::form.control-group.label>

                <x-admin::form.control-group.control
                    type="select"
                    name="status"
                    rules="required"
                    :value="old('status') ?? $entityType->status->value"
                    :label="trans('masterdata::app.entity-types.form.status')"
                    :options="json_encode($statusOptions)"
                    track-by="id"
                    label-by="label"
                />

                <x-admin::form.control-group.help>
                    @lang('masterdata::app.help.entity-types.status')
                </x-admin::form.control-group.help>

                <x-admin::form.control-group.error control-name="status" />
            </x-admin::form.control-group>

            <x-admin::form.control-group>
                <x-admin::form.control-group.label>
                    @lang('masterdata::app.entity-types.form.mastering-style')
                </x-admin::form.control-group.label>

                <x-admin::form.control-group.control
                    type="select"
                    name="mastering_style"
                    rules="required"
                    :value="old('mastering_style') ?? $entityType->mastering_style->value"
                    :label="trans('masterdata::app.entity-types.form.mastering-style')"
                    :options="json_encode($styleOptions)"
                    track-by="id"
                    label-by="label"
                />

                <x-admin::form.control-group.help>
                    @lang('masterdata::app.help.entity-types.mastering-style')
                </x-admin::form.control-group.help>

                <x-admin::form.control-group.error control-name="mastering_style" />
            </x-admin::form.control-group>

            <x-admin::form.control-group>
                <x-admin::form.control-group.label>
                    @lang('masterdata::app.entity-types.form.sort-order')
                </x-admin::form.control-group.label>

                <x-admin::form.control-group.control
                    type="number"
                    name="sort_order"
                    rules="required|integer|min_value:0|max_value:9999"
                    min="0"
                    max="9999"
                    step="1"
                    :value="old('sort_order') ?? $entityType->sort_order"
                    :label="trans('masterdata::app.entity-types.form.sort-order')"
                    :placeholder="trans('masterdata::app.entity-types.form.sort-order')"
                />

                <x-admin::form.control-group.help>
                    @lang('masterdata::app.entity-types.form.sort-order-info')
                </x-admin::form.control-group.help>

                <x-admin::form.control-group.error control-name="sort_order" />
            </x-admin::form.control-group>

            @foreach ($locales as $locale)
                @php
                    $translation = $entityType->translations->firstWhere('locale', $locale->code);
                @endphp

                <div class="pt-4 border-t border-gray-200 dark:border-cherry-800">
                    <x-admin::heading
                        class="mb-3"
                        :title="$locale->name"
                        size="sm"
                    />

                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label class="{{ ! $entityType->exists && $locale->code === $defaultLocaleCode ? 'required' : '' }}">
                            @lang('masterdata::app.entity-types.form.name')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="text"
                            name="translations[{{ $locale->code }}][name]"
                            :rules="! $entityType->exists && $locale->code === $defaultLocaleCode ? 'required' : ''"
                            :value="old('translations.'.$locale->code.'.name') ?? $translation?->name"
                            :label="trans('masterdata::app.entity-types.form.name')"
                            :placeholder="trans('masterdata::app.entity-types.form.name')"
                        />

                        @if ($loop->first)
                            <x-admin::form.control-group.help>
                                @lang('masterdata::app.help.entity-types.name')
                            </x-admin::form.control-group.help>
                        @endif

                        <x-admin::form.control-group.error control-name="translations[{{ $locale->code }}][name]" />
                    </x-admin::form.control-group>

                    <x-admin::form.control-group class="!mb-0">
                        <x-admin::form.control-group.label>
                            @lang('masterdata::app.entity-types.form.description')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="textarea"
                            name="translations[{{ $locale->code }}][description]"
                            :value="old('translations.'.$locale->code.'.description') ?? $translation?->description"
                            :label="trans('masterdata::app.entity-types.form.description')"
                            :placeholder="trans('masterdata::app.entity-types.form.description')"
                        />

                        @if ($loop->first)
                            <x-admin::form.control-group.help>
                                @lang('masterdata::app.help.entity-types.description')
                            </x-admin::form.control-group.help>
                        @endif

                        <x-admin::form.control-group.error control-name="translations[{{ $locale->code }}][description]" />
                    </x-admin::form.control-group>
                </div>
            @endforeach
        </div>
    </div>

    <div class="flex flex-col gap-2 w-[360px] max-w-full max-sm:w-full">
        <div class="p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
            <x-admin::heading
                class="mb-4"
                :title="trans('masterdata::app.entity-types.form.identity')"
            />

            <v-entity-type-identity-fields></v-entity-type-identity-fields>
        </div>

        <div class="p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
            <x-admin::heading
                class="mb-4"
                :title="trans('masterdata::app.entity-types.form.hierarchy')"
            />

            <x-admin::form.control-group>
                <x-admin::form.control-group.label>
                    @lang('masterdata::app.entity-types.form.is-hierarchical')
                </x-admin::form.control-group.label>

                <x-admin::form.control-group.control
                    type="hidden"
                    name="is_hierarchical"
                    value="0"
                />

                <x-admin::form.control-group.control
                    type="switch"
                    name="is_hierarchical"
                    value="1"
                    :label="trans('masterdata::app.entity-types.form.is-hierarchical')"
                    :checked="(bool) (old('is_hierarchical') ?? $entityType->is_hierarchical)"
                />

                <x-admin::form.control-group.help>
                    @lang('masterdata::app.help.entity-types.is-hierarchical')
                </x-admin::form.control-group.help>
            </x-admin::form.control-group>

            <x-admin::form.control-group>
                <x-admin::form.control-group.label>
                    @lang('masterdata::app.access.entity-type.is-scoped')
                </x-admin::form.control-group.label>

                <x-admin::form.control-group.control
                    type="hidden"
                    name="is_scoped"
                    value="0"
                />

                <x-admin::form.control-group.control
                    type="switch"
                    name="is_scoped"
                    value="1"
                    :label="trans('masterdata::app.access.entity-type.is-scoped')"
                    :checked="(bool) (old('is_scoped') ?? $entityType->is_scoped)"
                />

                <x-admin::form.control-group.help>
                    @lang('masterdata::app.access.entity-type.is-scoped-info')
                </x-admin::form.control-group.help>
            </x-admin::form.control-group>

            <x-admin::form.control-group class="!mb-0">
                <x-admin::form.control-group.label>
                    @lang('masterdata::app.entity-types.form.allowed-parents')
                </x-admin::form.control-group.label>

                <x-admin::form.control-group.control
                    type="hidden"
                    name="allowed_parent_type_ids"
                    value="[]"
                />

                <x-admin::form.control-group.control
                    type="multiselect"
                    name="allowed_parent_type_ids"
                    :value="$selectedParents"
                    :label="trans('masterdata::app.entity-types.form.allowed-parents')"
                    :placeholder="trans('masterdata::app.entity-types.form.allowed-parents')"
                    :options="json_encode($parentOptions)"
                    track-by="id"
                    label-by="label"
                />

                <x-admin::form.control-group.help>
                    @lang('masterdata::app.help.entity-types.allowed-parents')
                </x-admin::form.control-group.help>

                <x-admin::form.control-group.error control-name="allowed_parent_type_ids" />
            </x-admin::form.control-group>
        </div>

        <div class="p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
            <x-admin::heading
                class="mb-4"
                :title="trans('masterdata::app.entity-types.form.governance')"
            />

            <x-admin::form.control-group class="!mb-0">
                <x-admin::form.control-group.label>
                    @lang('masterdata::app.entity-types.form.approval-required')
                </x-admin::form.control-group.label>

                @if ($canChangeGovernance)
                    <x-admin::form.control-group.control
                        type="hidden"
                        name="approval_required"
                        value="0"
                    />

                    <x-admin::form.control-group.control
                        type="switch"
                        name="approval_required"
                        value="1"
                        :label="trans('masterdata::app.entity-types.form.approval-required')"
                        :checked="(bool) (old('approval_required') ?? $entityType->approval_required)"
                    />

                    <x-admin::form.control-group.help>
                        @lang('masterdata::app.help.entity-types.approval-required')
                    </x-admin::form.control-group.help>
                @else
                    <x-admin::form.control-group.control
                        type="switch"
                        name="approval_required_display"
                        value="1"
                        :label="trans('masterdata::app.entity-types.form.approval-required')"
                        :checked="(bool) $entityType->approval_required"
                        disabled
                    />

                    <x-admin::form.control-group.help>
                        @lang('masterdata::app.help.entity-types.approval-required')
                    </x-admin::form.control-group.help>

                    <x-admin::form.control-group.help>
                        @lang('masterdata::app.entity-types.form.governance-locked')
                    </x-admin::form.control-group.help>
                @endif
            </x-admin::form.control-group>

            <x-admin::form.control-group class="!mb-0 mt-4">
                <x-admin::form.control-group.label>
                    @lang('masterdata::app.entity-types.form.data-owner')
                </x-admin::form.control-group.label>

                @if ($canChangeGovernance)
                    <x-admin::form.control-group.control
                        type="select"
                        name="data_owner_id"
                        :value="old('data_owner_id') ?? ($entityType->data_owner_id ?? '')"
                        :label="trans('masterdata::app.entity-types.form.data-owner')"
                        :placeholder="trans('masterdata::app.entity-types.form.no-owner')"
                        :options="json_encode($ownerOptions)"
                        track-by="id"
                        label-by="label"
                    />
                @else
                    <x-admin::form.control-group.control
                        type="text"
                        name="data_owner_display"
                        :value="$entityType->dataOwner?->name ?? trans('masterdata::app.entity-types.form.no-owner')"
                        :label="trans('masterdata::app.entity-types.form.data-owner')"
                        disabled
                    />

                    <x-admin::form.control-group.help>
                        @lang('masterdata::app.entity-types.form.governance-locked')
                    </x-admin::form.control-group.help>
                @endif

                <x-admin::form.control-group.help>
                    @lang('masterdata::app.entity-types.form.data-owner-info')
                </x-admin::form.control-group.help>

                <x-admin::form.control-group.error control-name="data_owner_id" />
            </x-admin::form.control-group>
        </div>

        <div class="p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
            <x-admin::heading
                class="mb-4"
                :title="trans('masterdata::app.entity-types.form.quality-matching')"
                :subtitle="trans('masterdata::app.entity-types.form.quality-info')"
            />

            <x-admin::form.control-group>
                <x-admin::form.control-group.label>
                    @lang('masterdata::app.entity-types.form.completeness-scope')
                </x-admin::form.control-group.label>

                <x-admin::form.control-group.control
                    type="select"
                    name="settings[completeness_scope]"
                    :value="$completenessScope"
                    :label="trans('masterdata::app.entity-types.form.completeness-scope')"
                    :options="json_encode($completenessScopeOptions)"
                    track-by="id"
                    label-by="label"
                />

                <x-admin::form.control-group.help>
                    @lang('masterdata::app.help.entity-types.completeness-scope')
                </x-admin::form.control-group.help>

                <x-admin::form.control-group.error control-name="settings[completeness_scope]" />
            </x-admin::form.control-group>

            <x-admin::form.control-group>
                <x-admin::form.control-group.label>
                    @lang('masterdata::app.entity-types.form.required-systems')
                </x-admin::form.control-group.label>

                <x-admin::form.control-group.control
                    type="hidden"
                    name="settings[required_source_system_ids]"
                    value="[]"
                />

                <x-admin::form.control-group.control
                    type="multiselect"
                    name="settings[required_source_system_ids]"
                    :value="json_encode($requiredSystemOptions)"
                    :label="trans('masterdata::app.entity-types.form.required-systems')"
                    :placeholder="trans('masterdata::app.entity-types.form.required-systems')"
                    async="true"
                    :list-route="route('admin.masterdata.source_systems.options')"
                    query-params='{"inactive":1}'
                    track-by="id"
                    label-by="label"
                />

                <x-admin::form.control-group.help>
                    @lang('masterdata::app.help.entity-types.required-systems')
                </x-admin::form.control-group.help>

                <x-admin::form.control-group.error control-name="settings[required_source_system_ids]" />
            </x-admin::form.control-group>

            <x-admin::form.control-group class="!mb-0">
                <p class="mb-3 text-xs text-gray-600 dark:text-gray-300">
                    @lang('masterdata::app.entity-types.form.matching-info')
                </p>

                <x-admin::form.control-group.label>
                    @lang('masterdata::app.entity-types.form.auto-match-threshold')
                </x-admin::form.control-group.label>

                <x-admin::form.control-group.control
                    type="number"
                    name="settings[auto_match_threshold]"
                    rules="min_value:0.5|max_value:1"
                    step="0.01"
                    min="0.5"
                    max="1"
                    :value="$autoMatchThreshold"
                    :label="trans('masterdata::app.entity-types.form.auto-match-threshold')"
                    :placeholder="trans('masterdata::app.entity-types.form.auto-match-off')"
                />

                <x-admin::form.control-group.help>
                    @lang('masterdata::app.entity-types.form.auto-match-threshold-info')
                </x-admin::form.control-group.help>

                <x-admin::form.control-group.error control-name="settings[auto_match_threshold]" />
            </x-admin::form.control-group>

            <x-admin::form.control-group class="!mb-0 mt-4">
                <x-admin::form.control-group.label>
                    @lang('masterdata::app.entity-types.form.match-on-write')
                </x-admin::form.control-group.label>

                <x-admin::form.control-group.control
                    type="hidden"
                    name="settings[match_on_write]"
                    value="0"
                />

                <x-admin::form.control-group.control
                    type="switch"
                    name="settings[match_on_write]"
                    value="1"
                    :label="trans('masterdata::app.entity-types.form.match-on-write')"
                    :checked="$matchOnWrite"
                />

                <x-admin::form.control-group.help>
                    @lang('masterdata::app.entity-types.form.match-on-write-info')
                </x-admin::form.control-group.help>
            </x-admin::form.control-group>
        </div>
    </div>
</div>

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-entity-type-identity-fields-template"
    >
        <div>
            <x-admin::form.control-group>
                <x-admin::form.control-group.label>
                    @lang('masterdata::app.entity-types.form.code-strategy')
                </x-admin::form.control-group.label>

                <x-admin::form.control-group.control
                    type="select"
                    name="code_strategy"
                    rules="required"
                    :value="old('code_strategy') ?? $entityType->code_strategy->value"
                    :label="trans('masterdata::app.entity-types.form.code-strategy')"
                    :options="json_encode($strategyOptions)"
                    track-by="id"
                    label-by="label"
                    @input="updateStrategy($event)"
                />

                <x-admin::form.control-group.help>
                    @lang('masterdata::app.help.entity-types.code-strategy')
                </x-admin::form.control-group.help>

                <x-admin::form.control-group.error control-name="code_strategy" />
            </x-admin::form.control-group>

            <x-admin::form.control-group class="!mb-0" v-show="strategy === '{{ \Webkul\MasterDataManagement\Enums\CodeStrategy::Pattern->value }}'">
                <x-admin::form.control-group.label>
                    @lang('masterdata::app.entity-types.form.code-pattern')
                </x-admin::form.control-group.label>

                <x-admin::form.control-group.control
                    type="text"
                    name="code_pattern"
                    :value="old('code_pattern') ?? $entityType->code_pattern"
                    :label="trans('masterdata::app.entity-types.form.code-pattern')"
                    :placeholder="trans('masterdata::app.entity-types.form.code-pattern')"
                />

                <x-admin::form.control-group.help>
                    @lang('masterdata::app.entity-types.quick-create.code-pattern-info')
                </x-admin::form.control-group.help>

                @include('masterdata::entity-types._code-pattern-preview', [
                    'typeCode' => $entityType->code,
                    'pattern'  => old('code_pattern') ?? $entityType->code_pattern,
                    'start'    => $nextSequence,
                ])

                <x-admin::form.control-group.error control-name="code_pattern" />
            </x-admin::form.control-group>
        </div>
    </script>

    <script type="module">
        app.component('v-entity-type-identity-fields', {
            template: '#v-entity-type-identity-fields-template',

            data() {
                return {
                    strategy: @json(old('code_strategy') ?? $entityType->code_strategy->value),
                };
            },

            methods: {
                updateStrategy(value) {
                    try {
                        this.strategy = JSON.parse(value)?.id ?? value;
                    } catch (error) {
                        this.strategy = value;
                    }
                },
            },
        });
    </script>
@endPushOnce
