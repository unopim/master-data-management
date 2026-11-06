<v-masterdata-field-transforms>
    <x-admin::shimmer.datagrid />
</v-masterdata-field-transforms>

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-masterdata-field-transforms-template"
    >
        <div class="mt-3.5 p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
            <div class="flex items-start justify-between gap-4 mb-4 max-sm:flex-wrap">
                <div>
                    <p class="text-base text-gray-800 dark:text-white font-semibold">
                        @lang('masterdata::app.field-transforms.title')
                    </p>

                    <p class="text-xs text-gray-500 dark:text-gray-300">
                        @lang('masterdata::app.field-transforms.info')
                    </p>
                </div>

                <button
                    type="button"
                    class="secondary-button"
                    @click="openModal(null)"
                >
                    @lang('masterdata::app.field-transforms.create-btn')
                </button>
            </div>

            <x-admin::datagrid
                :src="route('admin.masterdata.field_transforms.index', $entityType->id)"
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

                        <p v-html="record.transform"></p>

                        <p v-text="record.sort_order"></p>

                        <div class="flex justify-end" @click.stop>
                            <button
                                type="button"
                                class="icon-edit cursor-pointer rounded-md p-1.5 text-2xl transition-all hover:bg-primary-100 dark:hover:bg-gray-800"
                                title="@lang('masterdata::app.field-transforms.datagrid.edit')"
                                aria-label="@lang('masterdata::app.field-transforms.datagrid.edit')"
                                @click="openModal(record.id)"
                            ></button>

                            <button
                                type="button"
                                class="icon-delete cursor-pointer rounded-md p-1.5 text-2xl transition-all hover:bg-primary-100 dark:hover:bg-gray-800"
                                title="@lang('masterdata::app.field-transforms.datagrid.delete')"
                                aria-label="@lang('masterdata::app.field-transforms.datagrid.delete')"
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
                <x-admin::modal ref="fieldTransformModal">
                    <x-slot:header>
                        <p class="text-lg text-gray-800 dark:text-white font-bold">
                            <span v-if="current.id">@lang('masterdata::app.field-transforms.edit.title')</span>

                            <span v-else>@lang('masterdata::app.field-transforms.create.title')</span>
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
                                @lang('masterdata::app.field-transforms.form.field')
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="select"
                                name="field_id"
                                rules="required"
                                ::value="current.field_id"
                                :label="trans('masterdata::app.field-transforms.form.field')"
                                :placeholder="trans('masterdata::app.field-transforms.form.select-field')"
                                :options="json_encode($ruleFieldOptions)"
                                track-by="id"
                                label-by="label"
                            />

                            <x-admin::form.control-group.error control-name="field_id" />
                        </x-admin::form.control-group>

                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label class="required">
                                @lang('masterdata::app.field-transforms.form.transform')
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="select"
                                name="transform"
                                rules="required"
                                ::value="current.transform"
                                :label="trans('masterdata::app.field-transforms.form.transform')"
                                :placeholder="trans('masterdata::app.field-transforms.form.select-transform')"
                                :options="json_encode($transformOptions)"
                                track-by="id"
                                label-by="label"
                            />

                            <x-admin::form.control-group.help>
                                @lang('masterdata::app.field-transforms.form.transform-info')
                            </x-admin::form.control-group.help>

                            <x-admin::form.control-group.error control-name="transform" />
                        </x-admin::form.control-group>

                        <x-admin::form.control-group class="!mb-0">
                            <x-admin::form.control-group.label>
                                @lang('masterdata::app.field-transforms.form.sort-order')
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="number"
                                name="sort_order"
                                rules="min_value:0|max_value:9999"
                                step="1"
                                min="0"
                                ::value="current.sort_order ?? 0"
                                :label="trans('masterdata::app.field-transforms.form.sort-order')"
                            />

                            <x-admin::form.control-group.help>
                                @lang('masterdata::app.field-transforms.form.sort-order-info')
                            </x-admin::form.control-group.help>

                            <x-admin::form.control-group.error control-name="sort_order" />
                        </x-admin::form.control-group>
                    </x-slot>

                    <x-slot:footer>
                        <button
                            type="submit"
                            class="primary-button"
                        >
                            @lang('masterdata::app.field-transforms.form.save-btn')
                        </button>
                    </x-slot>
                </x-admin::modal>
            </x-admin::form>
        </div>
    </script>

    <script type="module">
        app.component('v-masterdata-field-transforms', {
            template: '#v-masterdata-field-transforms-template',

            data() {
                return {
                    transforms: @json($fieldTransformsById),

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
                        ? "{{ route('admin.masterdata.field_transforms.update', ['entityTypeId' => $entityType->id, 'id' => '__id__']) }}".replace('__id__', this.current.id)
                        : "{{ route('admin.masterdata.field_transforms.store', $entityType->id) }}";
                },
            },

            methods: {
                openModal(id) {
                    this.current = id ? JSON.parse(JSON.stringify(this.transforms[id] ?? {})) : {};

                    this.$refs.fieldTransformModal.open();
                },
            },
        });
    </script>
@endPushOnce
