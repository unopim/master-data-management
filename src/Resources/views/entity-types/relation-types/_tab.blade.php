@php
    $defaultLocaleCode = config('masterdata.default_locale');
@endphp

<v-masterdata-relation-types>
    <x-admin::shimmer.datagrid />
</v-masterdata-relation-types>

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-masterdata-relation-types-template"
    >
        <div class="mt-3.5 p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
            <div class="flex items-start justify-between gap-4 mb-4 max-sm:flex-wrap">
                <div>
                    <p class="text-base text-gray-800 dark:text-white font-semibold">
                        @lang('masterdata::app.relation-types.title')
                    </p>

                    <p class="text-xs text-gray-500 dark:text-gray-300">
                        @lang('masterdata::app.relation-types.info')
                    </p>
                </div>

                <button
                    type="button"
                    class="secondary-button"
                    @click="openModal(null)"
                >
                    @lang('masterdata::app.relation-types.create-btn')
                </button>
            </div>

            <x-admin::datagrid
                :src="route('admin.masterdata.relation_types.index', $entityType->id)"
                ref="datagrid"
            >
                <template #body="{ records, performAction }">
                    <div
                        v-for="record in records"
                        class="row grid gap-2.5 items-center px-4 py-4 border-b dark:border-cherry-800 text-gray-600 dark:text-gray-300 transition-all hover:bg-primary-50 hover:bg-opacity-30 dark:hover:bg-cherry-800"
                        :class="{ 'cursor-pointer': ! isSystem(record.id) }"
                        :style="`grid-template-columns: repeat(${gridsCount}, minmax(0, 1fr))`"
                        @click="openModal(record.id)"
                    >
                        <p class="truncate" v-text="record.code" :title="record.code"></p>

                        <p class="truncate" v-text="record.label" :title="record.label"></p>

                        <p class="truncate" v-text="record.target_type" :title="record.target_type"></p>

                        <p v-html="record.cardinality"></p>

                        <p v-html="record.is_system"></p>

                        <div class="flex justify-end" @click.stop>
                            <template v-if="! isSystem(record.id)">
                                <button
                                    type="button"
                                    class="icon-edit cursor-pointer rounded-md p-1.5 text-2xl transition-all hover:bg-primary-100 dark:hover:bg-gray-800"
                                    title="@lang('masterdata::app.relation-types.datagrid.edit')"
                                    aria-label="@lang('masterdata::app.relation-types.datagrid.edit')"
                                    @click="openModal(record.id)"
                                ></button>

                                <button
                                    type="button"
                                    class="icon-delete cursor-pointer rounded-md p-1.5 text-2xl transition-all hover:bg-primary-100 dark:hover:bg-gray-800"
                                    title="@lang('masterdata::app.relation-types.datagrid.delete')"
                                    aria-label="@lang('masterdata::app.relation-types.datagrid.delete')"
                                    @click="performAction(record.actions.find(action => action.index === 'delete'))"
                                ></button>
                            </template>

                            <p
                                v-else
                                class="text-xs text-gray-500 dark:text-gray-400"
                            >
                                @lang('masterdata::app.relation-types.system-info')
                            </p>
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
                <x-admin::modal ref="relationTypeModal">
                    <x-slot:header>
                        <p class="text-lg text-gray-800 dark:text-white font-bold">
                            <span v-if="current.id">@lang('masterdata::app.relation-types.edit.title')</span>

                            <span v-else>@lang('masterdata::app.relation-types.create.title')</span>
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
                                @lang('masterdata::app.relation-types.form.code')
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="text"
                                name="code"
                                rules="required"
                                ::value="current.code"
                                ::readonly="!! current.id"
                                :label="trans('masterdata::app.relation-types.form.code')"
                                :placeholder="trans('masterdata::app.relation-types.form.code')"
                            />

                            <x-admin::form.control-group.help>
                                @lang('masterdata::app.help.relation-types.code')
                            </x-admin::form.control-group.help>

                            <x-admin::form.control-group.error control-name="code" />
                        </x-admin::form.control-group>

                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label class="required">
                                @lang('masterdata::app.relation-types.form.target-type')
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="select"
                                name="to_entity_type_id"
                                rules="required"
                                ::value="current.to_entity_type_id"
                                :label="trans('masterdata::app.relation-types.form.target-type')"
                                :placeholder="trans('masterdata::app.relation-types.form.select-target-type')"
                                :options="json_encode($targetTypeOptions)"
                                track-by="id"
                                label-by="label"
                            />

                            <x-admin::form.control-group.help>
                                @lang('masterdata::app.help.relation-types.target-type')
                            </x-admin::form.control-group.help>

                            <x-admin::form.control-group.error control-name="to_entity_type_id" />
                        </x-admin::form.control-group>

                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label class="required">
                                @lang('masterdata::app.relation-types.form.cardinality')
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="select"
                                name="cardinality"
                                rules="required"
                                ::value="current.cardinality"
                                :label="trans('masterdata::app.relation-types.form.cardinality')"
                                :options="json_encode($cardinalityOptions)"
                                track-by="id"
                                label-by="label"
                            />

                            <x-admin::form.control-group.help>
                                @lang('masterdata::app.help.relation-types.cardinality')
                            </x-admin::form.control-group.help>

                            <x-admin::form.control-group.error control-name="cardinality" />
                        </x-admin::form.control-group>

                        <p class="mb-4 text-base text-gray-800 dark:text-white font-semibold">
                            @lang('masterdata::app.relation-types.form.labels')
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
                                    :label="trans('masterdata::app.relation-types.form.label').' ('.$locale->name.')'"
                                    :placeholder="trans('masterdata::app.relation-types.form.label')"
                                />

                                @if ($loop->first)
                                    <x-admin::form.control-group.help>
                                        @lang('masterdata::app.help.relation-types.labels')
                                    </x-admin::form.control-group.help>
                                @endif

                                <x-admin::form.control-group.error control-name="label[{{ $locale->code }}]" />
                            </x-admin::form.control-group>
                        @endforeach
                    </x-slot>

                    <x-slot:footer>
                        <button
                            type="submit"
                            class="primary-button"
                        >
                            @lang('masterdata::app.relation-types.form.save-btn')
                        </button>
                    </x-slot>
                </x-admin::modal>
            </x-admin::form>
        </div>
    </script>

    <script type="module">
        app.component('v-masterdata-relation-types', {
            template: '#v-masterdata-relation-types-template',

            data() {
                return {
                    types: @json($relationTypesById),

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
                        ? "{{ route('admin.masterdata.relation_types.update', ['entityTypeId' => $entityType->id, 'id' => '__id__']) }}".replace('__id__', this.current.id)
                        : "{{ route('admin.masterdata.relation_types.store', $entityType->id) }}";
                },
            },

            methods: {
                isSystem(id) {
                    return !! this.types[id]?.is_system;
                },

                openModal(id) {
                    if (id && this.isSystem(id)) {
                        return;
                    }

                    this.current = id ? JSON.parse(JSON.stringify(this.types[id] ?? {})) : { label: {} };

                    this.$refs.relationTypeModal.open();
                },
            },
        });
    </script>
@endPushOnce
