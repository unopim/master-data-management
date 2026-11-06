@php
    $ruleTypes = \Webkul\MasterDataManagement\Enums\ValidationRuleType::class;
@endphp

<v-masterdata-validation-rules>
    <x-admin::shimmer.datagrid />
</v-masterdata-validation-rules>

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-masterdata-validation-rules-template"
    >
        <div class="mt-3.5 p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
            <div class="flex items-start justify-between gap-4 mb-4 max-sm:flex-wrap">
                <div>
                    <p class="text-base text-gray-800 dark:text-white font-semibold">
                        @lang('masterdata::app.validation-rules.title')
                    </p>

                    <p class="text-xs text-gray-500 dark:text-gray-300">
                        @lang('masterdata::app.validation-rules.info')
                    </p>
                </div>

                <button
                    type="button"
                    class="secondary-button"
                    @click="openModal(null)"
                >
                    @lang('masterdata::app.validation-rules.create-btn')
                </button>
            </div>

            <x-admin::datagrid
                :src="route('admin.masterdata.validation_rules.index', $entityType->id)"
                ref="datagrid"
            >
                <template #body="{ records, performAction }">
                    <div
                        v-for="record in records"
                        class="row grid gap-2.5 items-center px-4 py-4 border-b dark:border-cherry-800 text-gray-600 dark:text-gray-300 transition-all hover:bg-primary-50 hover:bg-opacity-30 dark:hover:bg-cherry-800 cursor-pointer"
                        :style="`grid-template-columns: repeat(${gridsCount}, minmax(0, 1fr))`"
                        @click="openModal(record.id)"
                    >
                        <p class="truncate" v-text="record.field_code"></p>

                        <p v-html="record.type"></p>

                        <p v-html="record.severity"></p>

                        <p v-text="record.sort_order"></p>

                        <div class="flex justify-end" @click.stop>
                            <button
                                type="button"
                                class="icon-edit cursor-pointer rounded-md p-1.5 text-2xl transition-all hover:bg-primary-100 dark:hover:bg-gray-800"
                                title="@lang('masterdata::app.validation-rules.datagrid.edit')"
                                aria-label="@lang('masterdata::app.validation-rules.datagrid.edit')"
                                @click="openModal(record.id)"
                            ></button>

                            <button
                                type="button"
                                class="icon-delete cursor-pointer rounded-md p-1.5 text-2xl transition-all hover:bg-primary-100 dark:hover:bg-gray-800"
                                title="@lang('masterdata::app.validation-rules.datagrid.delete')"
                                aria-label="@lang('masterdata::app.validation-rules.datagrid.delete')"
                                @click="performAction(record.actions.find(action => action.index === 'delete'))"
                            ></button>
                        </div>
                    </div>
                </template>
            </x-admin::datagrid>

            <x-admin::form
                ::action="formAction"
                method="POST"
                ajax="true"
                :track-dirty="false"
            >
                <x-admin::modal ref="validationRuleModal">
                    <x-slot:header>
                        <p class="text-lg text-gray-800 dark:text-white font-bold">
                            <span v-if="current.id">@lang('masterdata::app.validation-rules.edit.title')</span>

                            <span v-else>@lang('masterdata::app.validation-rules.create.title')</span>
                        </p>
                    </x-slot>

                    <x-slot:content>
                        <x-admin::form.control-group.control
                            type="hidden"
                            name="_method"
                            ::value="current.id ? 'PUT' : 'POST'"
                        />

                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label class="required">
                                @lang('masterdata::app.validation-rules.form.field')
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="select"
                                name="field_id"
                                rules="required"
                                ::value="current.field_id"
                                :label="trans('masterdata::app.validation-rules.form.field')"
                                :placeholder="trans('masterdata::app.validation-rules.form.select-field')"
                                :options="json_encode($ruleFieldOptions)"
                                track-by="id"
                                label-by="label"
                            />

                            <x-admin::form.control-group.error control-name="field_id" />
                        </x-admin::form.control-group>

                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label class="required">
                                @lang('masterdata::app.validation-rules.form.type')
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="select"
                                name="type"
                                rules="required"
                                ::value="current.type"
                                :label="trans('masterdata::app.validation-rules.form.type')"
                                :placeholder="trans('masterdata::app.validation-rules.form.select-type')"
                                :options="json_encode($ruleTypeOptions)"
                                track-by="id"
                                label-by="label"
                                @input="onTypeChange($event)"
                            />

                            <x-admin::form.control-group.error control-name="type" />
                        </x-admin::form.control-group>

                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label class="required">
                                @lang('masterdata::app.validation-rules.form.severity')
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="select"
                                name="severity"
                                rules="required"
                                ::value="current.severity"
                                :label="trans('masterdata::app.validation-rules.form.severity')"
                                :options="json_encode($severityOptions)"
                                track-by="id"
                                label-by="label"
                            />

                            <x-admin::form.control-group.help>
                                @lang('masterdata::app.validation-rules.form.severity-info')
                            </x-admin::form.control-group.help>

                            <x-admin::form.control-group.error control-name="severity" />
                        </x-admin::form.control-group>

                        <x-admin::form.control-group v-if="type === '{{ $ruleTypes::Regex->value }}'">
                            <x-admin::form.control-group.label class="required">
                                @lang('masterdata::app.validation-rules.form.pattern')
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="text"
                                name="params[pattern]"
                                rules="required"
                                ::value="current.params?.pattern ?? ''"
                                :label="trans('masterdata::app.validation-rules.form.pattern')"
                                placeholder="/^[A-Z]{2}[0-9]+$/"
                            />

                            <x-admin::form.control-group.help>
                                @lang('masterdata::app.validation-rules.form.pattern-info')
                            </x-admin::form.control-group.help>

                            <x-admin::form.control-group.error control-name="params[pattern]" />
                        </x-admin::form.control-group>

                        <x-admin::form.control-group v-if="type === '{{ $ruleTypes::Min->value }}' || type === '{{ $ruleTypes::Max->value }}'">
                            <x-admin::form.control-group.label class="required">
                                @lang('masterdata::app.validation-rules.form.value')
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="text"
                                name="params[value]"
                                rules="required"
                                ::value="current.params?.value ?? ''"
                                :label="trans('masterdata::app.validation-rules.form.value')"
                            />

                            <x-admin::form.control-group.error control-name="params[value]" />
                        </x-admin::form.control-group>

                        <div
                            class="grid grid-cols-2 gap-4"
                            v-if="type === '{{ $ruleTypes::Length->value }}'"
                        >
                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label>
                                    @lang('masterdata::app.validation-rules.form.min-length')
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="number"
                                    name="params[min]"
                                    rules="min_value:0"
                                    min="0"
                                    step="1"
                                    ::value="current.params?.min ?? ''"
                                    :label="trans('masterdata::app.validation-rules.form.min-length')"
                                />

                                <x-admin::form.control-group.error control-name="params[min]" />
                            </x-admin::form.control-group>

                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label>
                                    @lang('masterdata::app.validation-rules.form.max-length')
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="number"
                                    name="params[max]"
                                    rules="min_value:1"
                                    min="1"
                                    step="1"
                                    ::value="current.params?.max ?? ''"
                                    :label="trans('masterdata::app.validation-rules.form.max-length')"
                                />

                                <x-admin::form.control-group.error control-name="params[max]" />
                            </x-admin::form.control-group>
                        </div>

                        <template v-if="type === '{{ $ruleTypes::CrossField->value }}'">
                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label class="required">
                                    @lang('masterdata::app.validation-rules.form.operator')
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="select"
                                    name="params[operator]"
                                    rules="required"
                                    ::value="current.params?.operator"
                                    :label="trans('masterdata::app.validation-rules.form.operator')"
                                    :options="json_encode($operatorOptions)"
                                    track-by="id"
                                    label-by="label"
                                />

                                <x-admin::form.control-group.error control-name="params[operator]" />
                            </x-admin::form.control-group>

                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label class="required">
                                    @lang('masterdata::app.validation-rules.form.other-field')
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="select"
                                    name="params[field]"
                                    rules="required"
                                    ::value="current.params?.field"
                                    :label="trans('masterdata::app.validation-rules.form.other-field')"
                                    :placeholder="trans('masterdata::app.validation-rules.form.select-field')"
                                    :options="json_encode($crossFieldOptions)"
                                    track-by="id"
                                    label-by="label"
                                />

                                <x-admin::form.control-group.help>
                                    @lang('masterdata::app.validation-rules.form.other-field-info')
                                </x-admin::form.control-group.help>

                                <x-admin::form.control-group.error control-name="params[field]" />
                            </x-admin::form.control-group>
                        </template>

                        <x-admin::form.control-group v-if="type === '{{ $ruleTypes::OneOfCodes->value }}'">
                            <x-admin::form.control-group.label class="required">
                                @lang('masterdata::app.validation-rules.form.codes')
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="textarea"
                                name="params[codes]"
                                rules="required"
                                ::value="(current.params?.codes ?? []).join('\n')"
                                :label="trans('masterdata::app.validation-rules.form.codes')"
                            />

                            <x-admin::form.control-group.help>
                                @lang('masterdata::app.validation-rules.form.codes-info')
                            </x-admin::form.control-group.help>

                            <x-admin::form.control-group.error control-name="params[codes]" />
                        </x-admin::form.control-group>

                        <template v-if="type === '{{ $ruleTypes::DateRange->value }}'">
                            <div class="grid grid-cols-2 gap-4">
                                <x-admin::form.control-group>
                                    <x-admin::form.control-group.label>
                                        @lang('masterdata::app.validation-rules.form.after')
                                    </x-admin::form.control-group.label>

                                    <x-admin::form.control-group.control
                                        type="text"
                                        name="params[after]"
                                        ::value="current.params?.after ?? ''"
                                        :label="trans('masterdata::app.validation-rules.form.after')"
                                        placeholder="2020-01-01"
                                    />

                                    <x-admin::form.control-group.error control-name="params[after]" />
                                </x-admin::form.control-group>

                                <x-admin::form.control-group>
                                    <x-admin::form.control-group.label>
                                        @lang('masterdata::app.validation-rules.form.before')
                                    </x-admin::form.control-group.label>

                                    <x-admin::form.control-group.control
                                        type="text"
                                        name="params[before]"
                                        ::value="current.params?.before ?? ''"
                                        :label="trans('masterdata::app.validation-rules.form.before')"
                                        placeholder="today"
                                    />

                                    <x-admin::form.control-group.error control-name="params[before]" />
                                </x-admin::form.control-group>
                            </div>

                            <p class="mb-4 text-xs text-gray-500 dark:text-gray-300">
                                @lang('masterdata::app.validation-rules.form.date-info')
                            </p>
                        </template>

                        <p class="mb-2.5 text-sm text-gray-800 dark:text-white font-semibold">
                            @lang('masterdata::app.validation-rules.form.messages')
                        </p>

                        <p class="mb-2.5 text-xs text-gray-500 dark:text-gray-300">
                            @lang('masterdata::app.validation-rules.form.messages-info')
                        </p>

                        @foreach ($ruleLocales as $locale)
                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label>
                                    {{ $locale->name }}
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="text"
                                    name="messages[{{ $locale->code }}]"
                                    ::value="current.messages?.['{{ $locale->code }}'] ?? ''"
                                    :label="trans('masterdata::app.validation-rules.form.messages').' ('.$locale->name.')'"
                                />

                                <x-admin::form.control-group.error control-name="messages[{{ $locale->code }}]" />
                            </x-admin::form.control-group>
                        @endforeach

                        <x-admin::form.control-group class="!mb-0">
                            <x-admin::form.control-group.label>
                                @lang('masterdata::app.validation-rules.form.sort-order')
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="number"
                                name="sort_order"
                                rules="min_value:0|max_value:9999"
                                step="1"
                                min="0"
                                ::value="current.sort_order ?? 0"
                                :label="trans('masterdata::app.validation-rules.form.sort-order')"
                            />

                            <x-admin::form.control-group.error control-name="sort_order" />
                        </x-admin::form.control-group>
                    </x-slot>

                    <x-slot:footer>
                        <button
                            type="submit"
                            class="primary-button"
                        >
                            @lang('masterdata::app.validation-rules.form.save-btn')
                        </button>
                    </x-slot>
                </x-admin::modal>
            </x-admin::form>
        </div>
    </script>

    <script type="module">
        app.component('v-masterdata-validation-rules', {
            template: '#v-masterdata-validation-rules-template',

            data() {
                return {
                    rules: @json($validationRulesById),

                    current: {},

                    type: null,
                };
            },

            computed: {
                gridsCount() {
                    let count = this.$refs.datagrid.available.columns.length;

                    if (this.$refs.datagrid.available.actions.length) {
                        ++count;
                    }

                    return count;
                },

                formAction() {
                    return this.current.id
                        ? "{{ route('admin.masterdata.validation_rules.update', ['entityTypeId' => $entityType->id, 'id' => '__id__']) }}".replace('__id__', this.current.id)
                        : "{{ route('admin.masterdata.validation_rules.store', $entityType->id) }}";
                },
            },

            methods: {
                openModal(id) {
                    this.current = id
                        ? JSON.parse(JSON.stringify(this.rules[id] ?? {}))
                        : { severity: '{{ \Webkul\MasterDataManagement\Enums\ValidationSeverity::Block->value }}', params: {}, messages: {} };

                    this.type = this.current.type ?? null;

                    this.$refs.validationRuleModal.open();
                },

                onTypeChange(value) {
                    try {
                        this.type = value ? JSON.parse(value).id : null;
                    } catch (error) {
                        this.type = value || null;
                    }
                },
            },
        });
    </script>
@endPushOnce
