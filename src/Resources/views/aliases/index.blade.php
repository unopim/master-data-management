<x-admin::layouts>
    <x-slot:title>
        @lang('masterdata::app.aliases.index.title')
    </x-slot>

    <v-masterdata-aliases>
        <x-admin::layouts.page-header :title="trans('masterdata::app.aliases.index.title')" />

        <x-admin::shimmer.datagrid />
    </v-masterdata-aliases>

    @pushOnce('scripts')
        <script
            type="text/x-template"
            id="v-masterdata-aliases-template"
        >
            <div>
                <x-admin::layouts.page-header :title="trans('masterdata::app.aliases.index.title')">
                    <x-slot:actions>
                        <button
                            type="button"
                            class="primary-button"
                            @click="openModal(null)"
                        >
                            @lang('masterdata::app.aliases.index.create-btn')
                        </button>
                    </x-slot>
                </x-admin::layouts.page-header>

                @include('masterdata::code-lists._tabs', ['activeReferenceTab' => 'aliases'])

                <p class="mt-3.5 text-xs text-gray-500 dark:text-gray-300">
                    @lang('masterdata::app.aliases.index.info')
                </p>

                <div class="mt-3.5 p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
                    <p class="text-base text-gray-800 dark:text-white font-semibold">
                        @lang('masterdata::app.aliases.resolve.title')
                    </p>

                    <p class="mb-4 text-xs text-gray-500 dark:text-gray-300">
                        @lang('masterdata::app.aliases.resolve.info')
                    </p>

                    <x-admin::form
                        :action="route('admin.masterdata.aliases.index')"
                        method="GET"
                    >
                        <div class="grid grid-cols-3 gap-4 max-lg:grid-cols-1">
                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label class="required">
                                    @lang('masterdata::app.aliases.form.code-list')
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="select"
                                    name="resolve_code_list_id"
                                    rules="required"
                                    :value="$resolveInput['resolve_code_list_id'] ?? null"
                                    :label="trans('masterdata::app.aliases.form.code-list')"
                                    :placeholder="trans('masterdata::app.aliases.form.select-code-list')"
                                    async="true"
                                    :list-route="route('admin.masterdata.code_lists.options')"
                                    track-by="id"
                                    label-by="label"
                                />

                                <x-admin::form.control-group.help>
                                    @lang('masterdata::app.help.aliases.resolve-code-list')
                                </x-admin::form.control-group.help>

                                <x-admin::form.control-group.error control-name="resolve_code_list_id" />
                            </x-admin::form.control-group>

                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label>
                                    @lang('masterdata::app.aliases.form.source-system')
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="select"
                                    name="resolve_source_system_id"
                                    :value="$resolveInput['resolve_source_system_id'] ?? null"
                                    :label="trans('masterdata::app.aliases.form.source-system')"
                                    :placeholder="trans('masterdata::app.aliases.resolve.any-system')"
                                    async="true"
                                    :list-route="route('admin.masterdata.source_systems.options')"
                                    query-params='{"inactive":1}'
                                    track-by="id"
                                    label-by="label"
                                />

                                <x-admin::form.control-group.help>
                                    @lang('masterdata::app.help.aliases.resolve-source-system')
                                </x-admin::form.control-group.help>

                                <x-admin::form.control-group.error control-name="resolve_source_system_id" />
                            </x-admin::form.control-group>

                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label class="required">
                                    @lang('masterdata::app.aliases.form.term')
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="text"
                                    name="resolve_term"
                                    rules="required|max:255"
                                    :value="$resolveInput['resolve_term'] ?? null"
                                    :label="trans('masterdata::app.aliases.form.term')"
                                    :placeholder="trans('masterdata::app.aliases.form.term')"
                                />

                                <x-admin::form.control-group.help>
                                    @lang('masterdata::app.help.aliases.resolve-term')
                                </x-admin::form.control-group.help>

                                <x-admin::form.control-group.error control-name="resolve_term" />
                            </x-admin::form.control-group>
                        </div>

                        <button
                            type="submit"
                            class="primary-button"
                        >
                            @lang('masterdata::app.aliases.resolve.btn')
                        </button>
                    </x-admin::form>

                    @if ($resolution !== null)
                        <p
                            class="mt-4 text-sm text-gray-800 dark:text-white"
                            role="status"
                            v-pre
                        >
                            @if ($resolution['target'] !== null)
                                {{ trans('masterdata::app.aliases.resolve.found', ['term' => $resolution['term'], 'target' => $resolution['target']]) }}
                            @else
                                {{ trans('masterdata::app.aliases.resolve.not-found', ['term' => $resolution['term']]) }}
                            @endif
                        </p>
                    @endif
                </div>

                <x-admin::datagrid
                    :src="route('admin.masterdata.aliases.index')"
                    ref="datagrid"
                >
                    <template #body="{ records, performAction }">
                        <div
                            v-for="record in records"
                            class="row grid gap-2.5 items-center px-4 py-4 border-b dark:border-cherry-800 text-gray-600 dark:text-gray-300 transition-all cursor-pointer hover:bg-primary-50 hover:bg-opacity-30 dark:hover:bg-cherry-800"
                            :style="`grid-template-columns: repeat(${gridsCount}, minmax(0, 1fr))`"
                            @click="openModal(record)"
                        >
                            <p class="truncate" v-text="record.term" :title="record.term"></p>

                            <p class="truncate" v-text="record.target" :title="record.target"></p>

                            <p v-text="record.scope_type"></p>

                            <p class="truncate" v-text="record.scope" :title="record.scope"></p>

                            <p v-text="record.locale"></p>

                            <p class="truncate" v-text="record.source_system"></p>

                            <div class="flex justify-end" @click.stop>
                                <button
                                    type="button"
                                    class="icon-edit cursor-pointer rounded-md p-1.5 text-2xl transition-all hover:bg-primary-100 dark:hover:bg-gray-800"
                                    title="@lang('masterdata::app.aliases.datagrid.edit')"
                                    aria-label="@lang('masterdata::app.aliases.datagrid.edit')"
                                    @click="openModal(record)"
                                ></button>

                                <button
                                    type="button"
                                    class="icon-delete cursor-pointer rounded-md p-1.5 text-2xl transition-all hover:bg-primary-100 dark:hover:bg-gray-800"
                                    title="@lang('masterdata::app.aliases.datagrid.delete')"
                                    aria-label="@lang('masterdata::app.aliases.datagrid.delete')"
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
                    <x-admin::modal ref="aliasModal">
                        <x-slot:header>
                            <p class="text-lg text-gray-800 dark:text-white font-bold">
                                <span v-if="current.id">@lang('masterdata::app.aliases.edit.title')</span>

                                <span v-else>@lang('masterdata::app.aliases.create.title')</span>
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
                                    @lang('masterdata::app.aliases.form.scope-type')
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="select"
                                    name="scope_type"
                                    rules="required"
                                    ::value="current.scope_type"
                                    :label="trans('masterdata::app.aliases.form.scope-type')"
                                    :placeholder="trans('masterdata::app.aliases.form.select-scope-type')"
                                    :options="json_encode($scopeTypeOptions)"
                                    track-by="id"
                                    label-by="label"
                                    @input="onScopeTypeChange($event)"
                                />

                                <x-admin::form.control-group.help>
                                    @lang('masterdata::app.help.aliases.scope-type')
                                </x-admin::form.control-group.help>

                                <x-admin::form.control-group.error control-name="scope_type" />
                            </x-admin::form.control-group>

                            <x-admin::form.control-group v-if="scopeType === 'code_list'">
                                <x-admin::form.control-group.label class="required">
                                    @lang('masterdata::app.aliases.form.code-list')
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="select"
                                    name="scope_id"
                                    rules="required"
                                    ::value="current.scope_id"
                                    :label="trans('masterdata::app.aliases.form.code-list')"
                                    :placeholder="trans('masterdata::app.aliases.form.select-code-list')"
                                    async="true"
                                    :list-route="route('admin.masterdata.code_lists.options')"
                                    track-by="id"
                                    label-by="label"
                                />

                                <x-admin::form.control-group.help>
                                    @lang('masterdata::app.help.aliases.code-list')
                                </x-admin::form.control-group.help>

                                <x-admin::form.control-group.error control-name="scope_id" />
                            </x-admin::form.control-group>

                            <x-admin::form.control-group v-if="scopeType === 'field'">
                                <x-admin::form.control-group.label class="required">
                                    @lang('masterdata::app.aliases.form.field')
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="select"
                                    name="scope_id"
                                    rules="required"
                                    ::value="current.scope_id"
                                    :label="trans('masterdata::app.aliases.form.field')"
                                    :placeholder="trans('masterdata::app.aliases.form.select-field')"
                                    :options="json_encode($fieldOptions)"
                                    track-by="id"
                                    label-by="label"
                                />

                                <x-admin::form.control-group.help>
                                    @lang('masterdata::app.help.aliases.field')
                                </x-admin::form.control-group.help>

                                <x-admin::form.control-group.error control-name="scope_id" />
                            </x-admin::form.control-group>

                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label class="required">
                                    @lang('masterdata::app.aliases.form.term')
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="text"
                                    name="term"
                                    rules="required"
                                    ::value="current.term"
                                    :label="trans('masterdata::app.aliases.form.term')"
                                    :placeholder="trans('masterdata::app.aliases.form.term')"
                                />

                                <x-admin::form.control-group.help>
                                    @lang('masterdata::app.help.aliases.term')
                                </x-admin::form.control-group.help>

                                <x-admin::form.control-group.error control-name="term" />
                            </x-admin::form.control-group>

                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label class="required">
                                    @lang('masterdata::app.aliases.form.target')
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="text"
                                    name="target"
                                    rules="required"
                                    ::value="current.target"
                                    :label="trans('masterdata::app.aliases.form.target')"
                                    :placeholder="trans('masterdata::app.aliases.form.target-info')"
                                />

                                <x-admin::form.control-group.help>
                                    @lang('masterdata::app.help.aliases.target')
                                </x-admin::form.control-group.help>

                                <x-admin::form.control-group.error control-name="target" />
                            </x-admin::form.control-group>

                            <div class="grid grid-cols-2 gap-x-4 max-sm:grid-cols-1">
                                <x-admin::form.control-group class="!mb-0">
                                    <x-admin::form.control-group.label>
                                        @lang('masterdata::app.aliases.form.locale')
                                    </x-admin::form.control-group.label>

                                    <x-admin::form.control-group.control
                                        type="select"
                                        name="locale"
                                        ::value="current.locale"
                                        :label="trans('masterdata::app.aliases.form.locale')"
                                        :placeholder="trans('masterdata::app.aliases.form.select-locale')"
                                        :options="json_encode($localeOptions)"
                                        track-by="id"
                                        label-by="label"
                                    />

                                    <x-admin::form.control-group.help>
                                        @lang('masterdata::app.help.aliases.locale')
                                    </x-admin::form.control-group.help>

                                    <x-admin::form.control-group.error control-name="locale" />
                                </x-admin::form.control-group>

                                <x-admin::form.control-group class="!mb-0">
                                    <x-admin::form.control-group.label>
                                        @lang('masterdata::app.aliases.form.source-system')
                                    </x-admin::form.control-group.label>

                                    <x-admin::form.control-group.control
                                        type="select"
                                        name="source_system_id"
                                        ::value="current.source_system_id"
                                        :label="trans('masterdata::app.aliases.form.source-system')"
                                        :placeholder="trans('masterdata::app.aliases.form.select-source-system')"
                                        async="true"
                                    :list-route="route('admin.masterdata.source_systems.options')"
                                    query-params='{"inactive":1}'
                                        track-by="id"
                                        label-by="label"
                                    />

                                    <x-admin::form.control-group.help>
                                        @lang('masterdata::app.help.aliases.source-system')
                                    </x-admin::form.control-group.help>

                                    <x-admin::form.control-group.error control-name="source_system_id" />
                                </x-admin::form.control-group>
                            </div>
                        </x-slot>

                        <x-slot:footer>
                            <button
                                type="submit"
                                class="primary-button"
                            >
                                @lang('masterdata::app.aliases.form.save-btn')
                            </button>
                        </x-slot>
                    </x-admin::modal>
                </x-admin::form>
            </div>
        </script>

        <script type="module">
            app.component('v-masterdata-aliases', {
                template: '#v-masterdata-aliases-template',

                data() {
                    return {
                        current: {},

                        scopeType: null,
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
                            ? "{{ route('admin.masterdata.aliases.update', '__id__') }}".replace('__id__', this.current.id)
                            : "{{ route('admin.masterdata.aliases.store') }}";
                    },
                },

                methods: {
                    openModal(record) {
                        this.current = record
                            ? {
                                id: record.id,
                                scope_type: record.scope_type_value,
                                scope_id: record.scope_id,
                                term: record.term,
                                target: record.target,
                                locale: record.locale,
                                source_system_id: record.source_system_id,
                            }
                            : { scope_type: 'code_list' };

                        this.scopeType = this.current.scope_type;

                        this.$refs.aliasModal.open();
                    },

                    onScopeTypeChange(value) {
                        try {
                            this.scopeType = value ? JSON.parse(value).id : null;
                        } catch (error) {
                            this.scopeType = value || null;
                        }
                    },
                },
            });
        </script>
    @endPushOnce
</x-admin::layouts>
