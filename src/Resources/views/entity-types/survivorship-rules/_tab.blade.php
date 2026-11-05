<v-masterdata-survivorship-rules>
    <x-admin::shimmer.datagrid />
</v-masterdata-survivorship-rules>

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-masterdata-survivorship-rules-template"
    >
        <div class="mt-3.5 p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
            <div class="flex items-start justify-between gap-4 mb-4 max-sm:flex-wrap">
                <div>
                    <p class="text-base text-gray-800 dark:text-white font-semibold">
                        @lang('masterdata::app.survivorship-rules.title')
                    </p>

                    <p class="text-xs text-gray-500 dark:text-gray-300">
                        @lang('masterdata::app.survivorship-rules.info')
                    </p>
                </div>

                <button
                    type="button"
                    class="secondary-button"
                    @click="openModal(null)"
                >
                    @lang('masterdata::app.survivorship-rules.create-btn')
                </button>
            </div>

            <x-admin::datagrid
                :src="route('admin.masterdata.survivorship_rules.index', $entityType->id)"
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

                        <p v-html="record.strategy"></p>

                        <p v-text="record.sort_order"></p>

                        <div class="flex justify-end" @click.stop>
                            <button
                                type="button"
                                class="icon-edit cursor-pointer rounded-md p-1.5 text-2xl transition-all hover:bg-primary-100 dark:hover:bg-gray-800"
                                title="@lang('masterdata::app.survivorship-rules.datagrid.edit')"
                                aria-label="@lang('masterdata::app.survivorship-rules.datagrid.edit')"
                                @click="openModal(record.id)"
                            ></button>

                            <button
                                type="button"
                                class="icon-delete cursor-pointer rounded-md p-1.5 text-2xl transition-all hover:bg-primary-100 dark:hover:bg-gray-800"
                                title="@lang('masterdata::app.survivorship-rules.datagrid.delete')"
                                aria-label="@lang('masterdata::app.survivorship-rules.datagrid.delete')"
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
                <x-admin::modal ref="survivorshipRuleModal">
                    <x-slot:header>
                        <p class="text-lg text-gray-800 dark:text-white font-bold">
                            <span v-if="current.id">@lang('masterdata::app.survivorship-rules.edit.title')</span>

                            <span v-else>@lang('masterdata::app.survivorship-rules.create.title')</span>
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
                                @lang('masterdata::app.survivorship-rules.form.target')
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="select"
                                name="target"
                                rules="required"
                                ::value="current.target"
                                :label="trans('masterdata::app.survivorship-rules.form.target')"
                                :placeholder="trans('masterdata::app.survivorship-rules.form.select-target')"
                                :options="json_encode($survivorshipTargetOptions)"
                                track-by="id"
                                label-by="label"
                            />

                            <x-admin::form.control-group.help>
                                @lang('masterdata::app.survivorship-rules.form.target-info')
                            </x-admin::form.control-group.help>

                            <x-admin::form.control-group.error control-name="target" />
                        </x-admin::form.control-group>

                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label class="required">
                                @lang('masterdata::app.survivorship-rules.form.strategy')
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="select"
                                name="strategy"
                                rules="required"
                                ::value="current.strategy"
                                :label="trans('masterdata::app.survivorship-rules.form.strategy')"
                                :placeholder="trans('masterdata::app.survivorship-rules.form.select-strategy')"
                                :options="json_encode($strategyOptions)"
                                track-by="id"
                                label-by="label"
                                @input="onStrategyChange($event)"
                            />

                            <x-admin::form.control-group.help>
                                @lang('masterdata::app.survivorship-rules.form.strategy-info')
                            </x-admin::form.control-group.help>

                            <x-admin::form.control-group.error control-name="strategy" />
                        </x-admin::form.control-group>

                        <x-admin::form.control-group v-if="strategy === '{{ \Webkul\MasterDataManagement\Survivorship\Strategies\SourcePriorityStrategy::CODE }}'">
                            <x-admin::form.control-group.label>
                                @lang('masterdata::app.survivorship-rules.form.source-order')
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="hidden"
                                name="options[source_order]"
                                value="[]"
                            />

                            <x-admin::form.control-group.control
                                type="multiselect"
                                name="options[source_order]"
                                ::value="JSON.stringify(current.source_order ?? [])"
                                ::options="orderedSources"
                                :label="trans('masterdata::app.survivorship-rules.form.source-order')"
                                :placeholder="trans('masterdata::app.survivorship-rules.form.select-sources')"
                                track-by="id"
                                label-by="label"
                            />

                            <x-admin::form.control-group.help>
                                @lang('masterdata::app.survivorship-rules.form.source-order-info')
                            </x-admin::form.control-group.help>

                            <x-admin::form.control-group.error control-name="options[source_order]" />
                        </x-admin::form.control-group>

                        <x-admin::form.control-group class="!mb-0">
                            <x-admin::form.control-group.label>
                                @lang('masterdata::app.survivorship-rules.form.sort-order')
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="number"
                                name="sort_order"
                                rules="min_value:0|max_value:9999"
                                step="1"
                                min="0"
                                ::value="current.sort_order ?? 0"
                                :label="trans('masterdata::app.survivorship-rules.form.sort-order')"
                            />

                            <x-admin::form.control-group.error control-name="sort_order" />
                        </x-admin::form.control-group>
                    </x-slot>

                    <x-slot:footer>
                        <button
                            type="submit"
                            class="primary-button"
                        >
                            @lang('masterdata::app.survivorship-rules.form.save-btn')
                        </button>
                    </x-slot>
                </x-admin::modal>
            </x-admin::form>
        </div>
    </script>

    <script type="module">
        app.component('v-masterdata-survivorship-rules', {
            template: '#v-masterdata-survivorship-rules-template',

            data() {
                return {
                    rules: @json($survivorshipRulesById),

                    targets: @json(collect($survivorshipTargetOptions)->pluck('label', 'id')),

                    sources: @json($sourceSystemOptions),

                    current: {},

                    strategy: null,
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
                        ? "{{ route('admin.masterdata.survivorship_rules.update', ['entityTypeId' => $entityType->id, 'id' => '__id__']) }}".replace('__id__', this.current.id)
                        : "{{ route('admin.masterdata.survivorship_rules.store', $entityType->id) }}";
                },

                orderedSources() {
                    const order = this.current.source_order ?? [];

                    return [
                        ...order.map(id => this.sources.find(source => source.id === id)).filter(Boolean),
                        ...this.sources.filter(source => ! order.includes(source.id)),
                    ];
                },
            },

            methods: {
                targetLabel(target) {
                    return this.targets[target ?? '{{ \Webkul\MasterDataManagement\Models\SurvivorshipRule::TARGET_DEFAULT }}'] ?? target;
                },

                openModal(id) {
                    this.current = id ? JSON.parse(JSON.stringify(this.rules[id] ?? {})) : { target: '{{ \Webkul\MasterDataManagement\Models\SurvivorshipRule::TARGET_DEFAULT }}', source_order: [] };

                    this.strategy = this.current.strategy ?? null;

                    this.$refs.survivorshipRuleModal.open();
                },

                onStrategyChange(value) {
                    try {
                        this.strategy = value ? JSON.parse(value).id : null;
                    } catch (error) {
                        this.strategy = value || null;
                    }
                },
            },
        });
    </script>
@endPushOnce
