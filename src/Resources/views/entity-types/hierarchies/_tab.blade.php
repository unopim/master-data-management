@php
    $defaultLocaleCode = config('masterdata.default_locale');
@endphp

<v-masterdata-hierarchies>
    <x-admin::shimmer.datagrid />
</v-masterdata-hierarchies>

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-masterdata-hierarchies-template"
    >
        <div class="mt-3.5 p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
            <div class="flex items-start justify-between gap-4 mb-4 max-sm:flex-wrap">
                <div>
                    <p class="text-base text-gray-800 dark:text-white font-semibold">
                        @lang('masterdata::app.hierarchies.title')
                    </p>

                    <p class="text-xs text-gray-500 dark:text-gray-300">
                        @lang('masterdata::app.hierarchies.info')
                    </p>
                </div>

                <div class="flex items-center gap-2.5">
                    <a
                        href="{{ route('admin.masterdata.records.tree', $entityType->code) }}"
                        class="transparent-button"
                    >
                        @lang('masterdata::app.hierarchies.tree.open')
                    </a>

                    <button
                        type="button"
                        class="secondary-button"
                        @click="openModal(null)"
                    >
                        @lang('masterdata::app.hierarchies.create-btn')
                    </button>
                </div>
            </div>

            <x-admin::datagrid
                :src="route('admin.masterdata.hierarchies.index', $entityType->id)"
                ref="datagrid"
            >
                <template #body="{ records, performAction }">
                    <div
                        v-for="record in records"
                        class="row grid gap-2.5 items-center px-4 py-4 border-b dark:border-cherry-800 text-gray-600 dark:text-gray-300 transition-all hover:bg-primary-50 hover:bg-opacity-30 dark:hover:bg-cherry-800 cursor-pointer"
                        :style="`grid-template-columns: repeat(${gridsCount}, minmax(0, 1fr))`"
                        @click="openModal(record.id)"
                    >
                        <p class="truncate" v-text="record.code" :title="record.code"></p>

                        <p class="truncate" v-text="record.label" :title="record.label"></p>

                        <p v-html="record.is_primary"></p>

                        <div class="flex justify-end" @click.stop>
                            <button
                                type="button"
                                class="icon-edit cursor-pointer rounded-md p-1.5 text-2xl transition-all hover:bg-primary-100 dark:hover:bg-gray-800"
                                title="@lang('masterdata::app.hierarchies.datagrid.edit')"
                                aria-label="@lang('masterdata::app.hierarchies.datagrid.edit')"
                                @click="openModal(record.id)"
                            ></button>

                            <button
                                v-if="! isPrimary(record.id)"
                                type="button"
                                class="icon-delete cursor-pointer rounded-md p-1.5 text-2xl transition-all hover:bg-primary-100 dark:hover:bg-gray-800"
                                title="@lang('masterdata::app.hierarchies.datagrid.delete')"
                                aria-label="@lang('masterdata::app.hierarchies.datagrid.delete')"
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
                <x-admin::modal ref="hierarchyModal">
                    <x-slot:header>
                        <p class="text-lg text-gray-800 dark:text-white font-bold">
                            <span v-if="current.id">@lang('masterdata::app.hierarchies.edit.title')</span>

                            <span v-else>@lang('masterdata::app.hierarchies.create.title')</span>
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
                                @lang('masterdata::app.hierarchies.form.code')
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="text"
                                name="code"
                                rules="required"
                                ::value="current.code"
                                ::readonly="!! current.id"
                                :label="trans('masterdata::app.hierarchies.form.code')"
                                :placeholder="trans('masterdata::app.hierarchies.form.code')"
                            />

                            <x-admin::form.control-group.help>
                                @lang('masterdata::app.hierarchies.form.code-info')
                            </x-admin::form.control-group.help>

                            <x-admin::form.control-group.error control-name="code" />
                        </x-admin::form.control-group>

                        <p class="mb-4 text-base text-gray-800 dark:text-white font-semibold">
                            @lang('masterdata::app.hierarchies.form.labels')
                        </p>

                        @foreach ($locales as $locale)
                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label class="{{ $locale->code === $defaultLocaleCode ? 'required' : '' }}">
                                    {{ $locale->name }}
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="text"
                                    name="label[{{ $locale->code }}]"
                                    :rules="$locale->code === $defaultLocaleCode ? 'required' : ''"
                                    ::value="current.label?.['{{ $locale->code }}'] ?? ''"
                                    :label="trans('masterdata::app.hierarchies.form.label').' ('.$locale->name.')'"
                                    :placeholder="trans('masterdata::app.hierarchies.form.label')"
                                />

                                <x-admin::form.control-group.error control-name="label[{{ $locale->code }}]" />
                            </x-admin::form.control-group>
                        @endforeach
                    </x-slot>

                    <x-slot:footer>
                        <button
                            type="submit"
                            class="primary-button"
                        >
                            @lang('masterdata::app.hierarchies.form.save-btn')
                        </button>
                    </x-slot>
                </x-admin::modal>
            </x-admin::form>
        </div>
    </script>

    <script type="module">
        app.component('v-masterdata-hierarchies', {
            template: '#v-masterdata-hierarchies-template',

            data() {
                return {
                    hierarchies: @json($hierarchiesById),

                    current: {},
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
                        ? "{{ route('admin.masterdata.hierarchies.update', ['entityTypeId' => $entityType->id, 'id' => '__id__']) }}".replace('__id__', this.current.id)
                        : "{{ route('admin.masterdata.hierarchies.store', $entityType->id) }}";
                },
            },

            methods: {
                isPrimary(id) {
                    return !! this.hierarchies[id]?.is_primary;
                },

                openModal(id) {
                    this.current = id ? JSON.parse(JSON.stringify(this.hierarchies[id] ?? {})) : { label: {} };

                    this.$refs.hierarchyModal.open();
                },
            },
        });
    </script>
@endPushOnce
