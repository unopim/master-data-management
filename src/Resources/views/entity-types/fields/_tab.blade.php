<v-masterdata-fields>
    <x-admin::shimmer.datagrid />
</v-masterdata-fields>

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-masterdata-fields-template"
    >
        <div class="mt-3.5 p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
            <div class="flex items-start justify-between gap-4 mb-4 max-sm:flex-wrap">
                <div>
                    <p class="text-base text-gray-800 dark:text-white font-semibold">
                        @lang('masterdata::app.fields.title')
                    </p>

                    <p class="text-xs text-gray-500 dark:text-gray-300">
                        @lang('masterdata::app.fields.info')
                    </p>
                </div>

                <button
                    type="button"
                    class="secondary-button"
                    @click="openModal(null)"
                >
                    @lang('masterdata::app.fields.create-btn')
                </button>
            </div>

            <x-admin::datagrid
                :src="route('admin.masterdata.fields.index', $entityType->id)"
                ref="datagrid"
            >
                <template #body="{ records, performAction }">
                    <div
                        v-for="record in records"
                        class="row grid gap-2.5 items-center px-4 py-4 border-b dark:border-cherry-800 text-gray-600 dark:text-gray-300 transition-all cursor-pointer hover:bg-primary-50 hover:bg-opacity-30 dark:hover:bg-cherry-800"
                        :style="`grid-template-columns: repeat(${gridsCount}, minmax(0, 1fr))`"
                        @click="openModal(record.id)"
                    >
                        <p class="truncate" v-text="record.code" :title="record.code"></p>

                        <p class="truncate" v-text="record.label" :title="record.label"></p>

                        <p v-html="record.type"></p>

                        <p v-html="record.is_required"></p>

                        <p v-html="record.is_searchable"></p>

                        <p v-html="record.is_archived"></p>

                        <p v-text="record.position"></p>

                        <div class="flex justify-end" @click.stop>
                            <button
                                type="button"
                                class="icon-edit cursor-pointer rounded-md p-1.5 text-2xl transition-all hover:bg-primary-100 dark:hover:bg-gray-800"
                                title="@lang('masterdata::app.fields.datagrid.edit')"
                                aria-label="@lang('masterdata::app.fields.datagrid.edit')"
                                @click="openModal(record.id)"
                            ></button>

                            <button
                                type="button"
                                class="icon-delete cursor-pointer rounded-md p-1.5 text-2xl transition-all hover:bg-primary-100 dark:hover:bg-gray-800"
                                title="@lang('masterdata::app.fields.datagrid.delete')"
                                aria-label="@lang('masterdata::app.fields.datagrid.delete')"
                                @click="performAction(record.actions.find(action => action.index === 'delete'))"
                            ></button>
                        </div>
                    </div>
                </template>
            </x-admin::datagrid>

            @include('masterdata::entity-types.fields._form-modal')
        </div>
    </script>

    <script type="module">
        app.component('v-masterdata-fields', {
            template: '#v-masterdata-fields-template',

            data() {
                return {
                    fields: @json($fieldsById),

                    current: {},

                    fieldType: null,
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
                        ? "{{ route('admin.masterdata.fields.update', ['entityTypeId' => $entityType->id, 'id' => '__id__']) }}".replace('__id__', this.current.id)
                        : "{{ route('admin.masterdata.fields.store', $entityType->id) }}";
                },

                usesCodeList() {
                    return ['select', 'multiselect'].includes(this.fieldType);
                },
            },

            methods: {
                openModal(id) {
                    this.current = id ? JSON.parse(JSON.stringify(this.fields[id] ?? {})) : { label: {}, validation: {} };

                    this.fieldType = this.current.type ?? null;

                    this.$refs.fieldModal.open();
                },

                onTypeChange(value) {
                    try {
                        this.fieldType = value ? JSON.parse(value).id : null;
                    } catch (error) {
                        this.fieldType = value || null;
                    }
                },
            },
        });
    </script>
@endPushOnce
