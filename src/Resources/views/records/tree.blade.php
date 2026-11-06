<x-admin::layouts>
    <x-slot:title>
        {{ trans('masterdata::app.hierarchies.tree.title', ['type' => $entityType->name ?: $entityType->code]) }}
    </x-slot>

    <x-admin::layouts.edit-page-header
        :title="trans('masterdata::app.hierarchies.tree.title', ['type' => $entityType->name ?: $entityType->code])"
        :back-url="route('admin.masterdata.records.index', $entityType->code)"
        :back-label="trans('admin::app.account.edit.back-btn')"
        :sticky="false"
    >
        @if (count($hierarchies) > 1)
            <x-slot:actions>
                <x-admin::dropdown position="bottom-right">
                    <x-slot:toggle>
                        <button
                            type="button"
                            class="secondary-button"
                            aria-label="{{ trans('masterdata::app.hierarchies.tree.switch') }}"
                            aria-haspopup="menu"
                        >
                            <span>{{ $current->displayLabel() }}</span>

                            <span class="icon-chevron-down text-2xl" aria-hidden="true"></span>
                        </button>
                    </x-slot>

                    <x-slot:menu class="!py-1 max-h-80 min-w-56 overflow-y-auto">
                        @foreach ($hierarchies as $option)
                            <li>
                                <a
                                    href="{{ route('admin.masterdata.records.tree', ['entity_type' => $entityType->code, 'hierarchy' => $option['id']]) }}"
                                    class="block px-4 py-2 text-sm font-normal hover:bg-primary-50 dark:hover:bg-cherry-800 {{ $option['id'] === $current->id ? 'font-semibold text-primary-700 dark:text-primary-400' : 'text-gray-600 dark:text-gray-300' }}"
                                    @if ($option['id'] === $current->id) aria-current="page" @endif
                                >
                                    {{ $option['label'] }}
                                </a>
                            </li>
                        @endforeach
                    </x-slot>
                </x-admin::dropdown>
            </x-slot>
        @endif
    </x-admin::layouts.edit-page-header>

    <div class="mt-3.5 p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
        @if ($current === null)
            <x-admin::list.empty-state
                :title="trans('masterdata::app.hierarchies.tree.empty')"
                :description="trans('masterdata::app.hierarchies.tree.empty-info')"
            />
        @else
            <p class="mb-3 text-base text-gray-800 dark:text-white font-semibold">
                {{ $current->displayLabel() }}
            </p>

            <v-masterdata-tree-level
                :hierarchy-id="{{ $current->id }}"
                role="tree"
                aria-label="{{ $current->displayLabel() }}"
            ></v-masterdata-tree-level>
        @endif
    </div>

    @pushOnce('scripts')
        <script
            type="text/x-template"
            id="v-masterdata-tree-level-template"
        >
            <div>
                <p
                    v-if="loading"
                    class="py-2 text-sm text-gray-500 dark:text-gray-300"
                >
                    @lang('masterdata::app.hierarchies.tree.loading')
                </p>

                <p
                    v-else-if="! nodes.length && ! parent"
                    class="py-2 text-sm text-gray-500 dark:text-gray-300"
                >
                    @lang('masterdata::app.hierarchies.tree.no-nodes')
                </p>

                <ul :role="parent ? 'group' : null">
                    <li
                        v-for="node in nodes"
                        :key="node.uuid"
                        role="treeitem"
                        :aria-expanded="node.children ? String(!! expanded[node.uuid]) : null"
                    >
                        <div class="flex items-center gap-1.5 py-1 text-sm text-gray-600 dark:text-gray-300">
                            <button
                                v-if="node.children"
                                type="button"
                                class="rounded-md text-2xl transition-all hover:bg-primary-100 dark:hover:bg-gray-800"
                                :class="expanded[node.uuid] ? 'icon-chevron-down' : 'icon-chevron-right'"
                                :aria-label="(expanded[node.uuid] ? '@lang('masterdata::app.hierarchies.tree.collapse')' : '@lang('masterdata::app.hierarchies.tree.expand')') + ' ' + node.code"
                                @click="toggle(node)"
                            ></button>

                            <span
                                v-else
                                class="inline-block w-6"
                                aria-hidden="true"
                            ></span>

                            <a
                                :href="node.url"
                                class="text-primary-600 hover:text-primary-700 dark:text-primary-400"
                                v-text="node.code"
                            ></a>

                            <span v-if="node.label" v-text="node.label"></span>

                            <span class="text-xs text-gray-500 dark:text-gray-400" v-text="node.status"></span>

                            <span
                                v-if="node.children"
                                class="text-xs text-gray-500 dark:text-gray-400"
                                v-text="'(' + node.children + ')'"
                            ></span>
                        </div>

                        <div
                            v-if="expanded[node.uuid]"
                            class="ltr:pl-6 rtl:pr-6 ltr:border-l rtl:border-r border-gray-200 dark:border-cherry-800"
                        >
                            <v-masterdata-tree-level
                                :hierarchy-id="hierarchyId"
                                :parent="node.uuid"
                            ></v-masterdata-tree-level>
                        </div>
                    </li>
                </ul>

                <p
                    v-if="limited"
                    class="py-1 text-xs text-gray-500 dark:text-gray-400"
                >
                    {{ trans('masterdata::app.hierarchies.tree.limited', ['count' => \Webkul\MasterDataManagement\Services\HierarchyPresenter::TREE_LIMIT]) }}
                </p>
            </div>
        </script>

        <script type="module">
            app.component('v-masterdata-tree-level', {
                template: '#v-masterdata-tree-level-template',

                props: {
                    hierarchyId: {
                        type: Number,
                        required: true,
                    },

                    parent: {
                        type: String,
                        default: null,
                    },
                },

                data() {
                    return {
                        nodes: [],

                        expanded: {},

                        limited: false,

                        loading: true,
                    };
                },

                mounted() {
                    const params = { hierarchy: this.hierarchyId };

                    if (this.parent) {
                        params.parent = this.parent;
                    }

                    this.$axios.get("{{ route('admin.masterdata.records.tree.nodes', $entityType->code) }}", { params })
                        .then((response) => {
                            this.nodes = response.data.nodes;

                            this.limited = response.data.limited;
                        })
                        .catch((error) => {
                            this.$emitter.emit('add-flash', { type: 'error', message: error.response?.data?.message });
                        })
                        .finally(() => {
                            this.loading = false;
                        });
                },

                methods: {
                    toggle(node) {
                        this.expanded[node.uuid] = ! this.expanded[node.uuid];
                    },
                },
            });
        </script>
    @endPushOnce
</x-admin::layouts>
