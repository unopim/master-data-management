<v-masterdata-match-rules>
    <x-admin::shimmer.datagrid />
</v-masterdata-match-rules>

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-masterdata-match-rules-template"
    >
        <div class="mt-3.5 p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
            <div class="flex items-start justify-between gap-4 mb-4 max-sm:flex-wrap">
                <div>
                    <p class="text-base text-gray-800 dark:text-white font-semibold">
                        @lang('masterdata::app.match-rules.title')
                    </p>

                    <p class="text-xs text-gray-500 dark:text-gray-300">
                        @lang('masterdata::app.match-rules.info')
                    </p>
                </div>

                <button
                    type="button"
                    class="secondary-button"
                    @click="openModal(null)"
                >
                    @lang('masterdata::app.match-rules.create-btn')
                </button>
            </div>

            <x-admin::datagrid
                :src="route('admin.masterdata.match_rules.index', $entityType->id)"
                ref="datagrid"
            >
                <template #body="{ records, performAction }">
                    <div
                        v-for="record in records"
                        class="row grid gap-2.5 items-center px-4 py-4 border-b dark:border-cherry-800 text-gray-600 dark:text-gray-300 transition-all hover:bg-primary-50 hover:bg-opacity-30 dark:hover:bg-cherry-800 cursor-pointer"
                        :style="`grid-template-columns: repeat(${gridsCount}, minmax(0, 1fr))`"
                        @click="openModal(record.id)"
                    >
                        <p class="truncate" v-text="targetLabel(record.target)" :title="record.target"></p>

                        <p v-html="record.comparator"></p>

                        <p v-html="record.weight"></p>

                        <p v-html="record.is_blocking"></p>

                        <p v-text="record.sort_order"></p>

                        <div class="flex justify-end" @click.stop>
                            <button
                                type="button"
                                class="icon-edit cursor-pointer rounded-md p-1.5 text-2xl transition-all hover:bg-primary-100 dark:hover:bg-gray-800"
                                title="@lang('masterdata::app.match-rules.datagrid.edit')"
                                aria-label="@lang('masterdata::app.match-rules.datagrid.edit')"
                                @click="openModal(record.id)"
                            ></button>

                            <button
                                type="button"
                                class="icon-delete cursor-pointer rounded-md p-1.5 text-2xl transition-all hover:bg-primary-100 dark:hover:bg-gray-800"
                                title="@lang('masterdata::app.match-rules.datagrid.delete')"
                                aria-label="@lang('masterdata::app.match-rules.datagrid.delete')"
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
                <x-admin::modal ref="matchRuleModal">
                    <x-slot:header>
                        <p class="text-lg text-gray-800 dark:text-white font-bold">
                            <span v-if="current.id">@lang('masterdata::app.match-rules.edit.title')</span>

                            <span v-else>@lang('masterdata::app.match-rules.create.title')</span>
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
                                @lang('masterdata::app.match-rules.form.target')
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="select"
                                name="target"
                                rules="required"
                                ::value="current.target"
                                :label="trans('masterdata::app.match-rules.form.target')"
                                :placeholder="trans('masterdata::app.match-rules.form.select-target')"
                                :options="json_encode($matchTargetOptions)"
                                track-by="id"
                                label-by="label"
                            />

                            <x-admin::form.control-group.help>
                                @lang('masterdata::app.match-rules.form.target-info')
                            </x-admin::form.control-group.help>

                            <x-admin::form.control-group.error control-name="target" />
                        </x-admin::form.control-group>

                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label class="required">
                                @lang('masterdata::app.match-rules.form.comparator')
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="select"
                                name="comparator"
                                rules="required"
                                ::value="current.comparator"
                                :label="trans('masterdata::app.match-rules.form.comparator')"
                                :placeholder="trans('masterdata::app.match-rules.form.select-comparator')"
                                :options="json_encode($comparatorOptions)"
                                track-by="id"
                                label-by="label"
                                @input="onComparatorChange($event)"
                            />

                            <x-admin::form.control-group.help>
                                @lang('masterdata::app.match-rules.form.comparator-info')
                            </x-admin::form.control-group.help>

                            <x-admin::form.control-group.error control-name="comparator" />
                        </x-admin::form.control-group>

                        <x-admin::form.control-group v-if="comparator === 'numeric'">
                            <x-admin::form.control-group.label>
                                @lang('masterdata::app.match-rules.form.tolerance')
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="number"
                                name="options[tolerance]"
                                rules="min_value:0"
                                step="any"
                                min="0"
                                ::value="current.options?.tolerance ?? ''"
                                :label="trans('masterdata::app.match-rules.form.tolerance')"
                            />

                            <x-admin::form.control-group.help>
                                @lang('masterdata::app.match-rules.form.tolerance-info')
                            </x-admin::form.control-group.help>

                            <x-admin::form.control-group.error control-name="options[tolerance]" />
                        </x-admin::form.control-group>

                        <x-admin::form.control-group v-if="comparator === 'date'">
                            <x-admin::form.control-group.label>
                                @lang('masterdata::app.match-rules.form.days')
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="number"
                                name="options[days]"
                                rules="min_value:0"
                                step="1"
                                min="0"
                                ::value="current.options?.days ?? ''"
                                :label="trans('masterdata::app.match-rules.form.days')"
                            />

                            <x-admin::form.control-group.help>
                                @lang('masterdata::app.match-rules.form.days-info')
                            </x-admin::form.control-group.help>

                            <x-admin::form.control-group.error control-name="options[days]" />
                        </x-admin::form.control-group>

                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label class="required">
                                @lang('masterdata::app.match-rules.form.weight')
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="number"
                                name="weight"
                                rules="required|min_value:0.1|max_value:100"
                                step="0.1"
                                min="0.1"
                                max="100"
                                ::value="current.weight ?? 1"
                                :label="trans('masterdata::app.match-rules.form.weight')"
                            />

                            <x-admin::form.control-group.help>
                                @lang('masterdata::app.match-rules.form.weight-info')
                            </x-admin::form.control-group.help>

                            <x-admin::form.control-group.error control-name="weight" />
                        </x-admin::form.control-group>

                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label>
                                @lang('masterdata::app.match-rules.form.sort-order')
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="number"
                                name="sort_order"
                                rules="min_value:0|max_value:9999"
                                step="1"
                                min="0"
                                ::value="current.sort_order ?? 0"
                                :label="trans('masterdata::app.match-rules.form.sort-order')"
                            />

                            <x-admin::form.control-group.error control-name="sort_order" />
                        </x-admin::form.control-group>

                        <x-admin::form.control-group class="!mb-0">
                            <x-admin::form.control-group.label>
                                @lang('masterdata::app.match-rules.form.is-blocking')
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="hidden"
                                name="is_blocking"
                                value="0"
                            />

                            <x-admin::form.control-group.control
                                type="switch"
                                name="is_blocking"
                                value="1"
                                ::checked="!! current.is_blocking"
                                :label="trans('masterdata::app.match-rules.form.is-blocking')"
                            />

                            <x-admin::form.control-group.help>
                                @lang('masterdata::app.match-rules.form.is-blocking-info')
                            </x-admin::form.control-group.help>

                            <x-admin::form.control-group.error control-name="is_blocking" />
                        </x-admin::form.control-group>
                    </x-slot>

                    <x-slot:footer>
                        <button
                            type="submit"
                            class="primary-button"
                        >
                            @lang('masterdata::app.match-rules.form.save-btn')
                        </button>
                    </x-slot>
                </x-admin::modal>
            </x-admin::form>
        </div>
    </script>

    <script type="module">
        app.component('v-masterdata-match-rules', {
            template: '#v-masterdata-match-rules-template',

            data() {
                return {
                    rules: @json($matchRulesById),

                    targets: @json(collect($matchTargetOptions)->pluck('label', 'id')),

                    current: {},

                    comparator: null,
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
                        ? "{{ route('admin.masterdata.match_rules.update', ['entityTypeId' => $entityType->id, 'id' => '__id__']) }}".replace('__id__', this.current.id)
                        : "{{ route('admin.masterdata.match_rules.store', $entityType->id) }}";
                },
            },

            methods: {
                targetLabel(target) {
                    return this.targets[target] ?? target;
                },

                openModal(id) {
                    this.current = id ? JSON.parse(JSON.stringify(this.rules[id] ?? {})) : { options: {} };

                    this.comparator = this.current.comparator ?? null;

                    this.$refs.matchRuleModal.open();
                },

                onComparatorChange(value) {
                    try {
                        this.comparator = value ? JSON.parse(value).id : null;
                    } catch (error) {
                        this.comparator = value || null;
                    }
                },
            },
        });
    </script>
@endPushOnce
