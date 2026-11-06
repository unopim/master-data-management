<x-admin::layouts>
    <x-slot:title>
        @lang('masterdata::app.entity-types.index.title')
    </x-slot>

    <x-admin::layouts.page-header :title="trans('masterdata::app.entity-types.index.title')">
        <x-slot:actions>
            @include('masterdata::entity-types._model-transfer')

            @if (bouncer()->hasPermission('masterdata.entity_types.create'))
                <x-admin::catalog.quick-create-modal
                    id="entityTypeCreateModal"
                    :action="route('admin.masterdata.entity_types.store')"
                    :button-label="trans('masterdata::app.entity-types.index.create-btn')"
                    :title="trans('masterdata::app.entity-types.create.title')"
                    :name-label="trans('masterdata::app.entity-types.form.name')"
                    :name-locale="$defaultLocaleCode"
                    :name-field="'translations['.$defaultLocaleCode.'][name]'"
                    :code-label="trans('masterdata::app.entity-types.form.code')"
                    :code-hint="trans('masterdata::app.entity-types.quick-create.code-info')"
                    :save-label="trans('masterdata::app.entity-types.create.save-btn')"
                >
                    <v-entity-type-quick-create-fields></v-entity-type-quick-create-fields>
                </x-admin::catalog.quick-create-modal>
            @endif
        </x-slot>
    </x-admin::layouts.page-header>

    <x-admin::datagrid :src="route('admin.masterdata.entity_types.index')" />

    @if (bouncer()->hasPermission('masterdata.entity_types.create'))
        @pushOnce('scripts')
            <script
                type="text/x-template"
                id="v-entity-type-quick-create-fields-template"
            >
                <div>
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label class="required">
                            @lang('masterdata::app.entity-types.form.code-strategy')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="select"
                            name="code_strategy"
                            rules="required"
                            :value="$newEntityType->code_strategy->value"
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

                    <x-admin::form.control-group v-if="strategy === '{{ \Webkul\MasterDataManagement\Enums\CodeStrategy::Pattern->value }}'">
                        <x-admin::form.control-group.label class="required">
                            @lang('masterdata::app.entity-types.form.code-pattern')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="text"
                            name="code_pattern"
                            rules="required"
                            :value="$newEntityType->code_pattern"
                            :label="trans('masterdata::app.entity-types.form.code-pattern')"
                            :placeholder="$newEntityType->code_pattern"
                        />

                        <x-admin::form.control-group.help>
                            @lang('masterdata::app.entity-types.quick-create.code-pattern-info')
                        </x-admin::form.control-group.help>

                        @include('masterdata::entity-types._code-pattern-preview', [
                            'pattern' => $newEntityType->code_pattern,
                        ])

                        <x-admin::form.control-group.error control-name="code_pattern" />
                    </x-admin::form.control-group>

                    <x-admin::form.control-group class="!mb-0">
                        <x-admin::form.control-group.label class="required">
                            @lang('masterdata::app.entity-types.form.mastering-style')
                        </x-admin::form.control-group.label>

                        @foreach ($styleOptions as $style)
                            <div class="flex gap-2.5 items-start mb-2 select-none">
                                <x-admin::form.control-group.control
                                    type="radio"
                                    name="mastering_style"
                                    rules="required"
                                    v-model="masteringStyle"
                                    :id="'mastering_style_'.$style['id']"
                                    :for="'mastering_style_'.$style['id']"
                                    :value="$style['id']"
                                    :label="$style['label']"
                                />

                                <label
                                    class="flex flex-col gap-0.5 cursor-pointer"
                                    for="{{ form_control_id('mastering_style_'.$style['id']) }}"
                                >
                                    <span class="text-sm text-gray-800 dark:text-white font-semibold">
                                        {{ $style['label'] }}
                                    </span>

                                    <span class="text-xs text-gray-600 dark:text-gray-300">
                                        @lang('masterdata::app.entity-types.quick-create.mastering-styles-info.'.$style['id'])
                                    </span>
                                </label>
                            </div>
                        @endforeach

                        <x-admin::form.control-group.error control-name="mastering_style" />
                    </x-admin::form.control-group>
                </div>
            </script>

            <script type="module">
                app.component('v-entity-type-quick-create-fields', {
                    template: '#v-entity-type-quick-create-fields-template',

                    data() {
                        return {
                            strategy: @json($newEntityType->code_strategy->value),

                            masteringStyle: @json($newEntityType->mastering_style->value),
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
    @endif
</x-admin::layouts>
