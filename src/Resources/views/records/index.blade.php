<x-admin::layouts>
    <x-slot:title>
        {{ trans('masterdata::app.records.index.title', ['type' => $entityType->name ?: $entityType->code]) }}
    </x-slot>

    <x-admin::layouts.edit-page-header
        :title="trans('masterdata::app.records.index.title', ['type' => $entityType->name ?: $entityType->code])"
        :sticky="false"
    >
        @if ($switchTypes->count() > 1)
            <x-slot:heading>
                <x-admin::dropdown position="bottom-left">
                    <x-slot:toggle>
                        <button
                            type="button"
                            class="flex items-center gap-1 rounded-md hover:text-primary-700 dark:hover:text-primary-400"
                            aria-label="{{ trans('masterdata::app.records.index.switch-type') }}"
                            aria-haspopup="menu"
                        >
                            <span>{{ trans('masterdata::app.records.index.title', ['type' => $entityType->name ?: $entityType->code]) }}</span>

                            <span class="icon-chevron-down text-2xl" aria-hidden="true"></span>
                        </button>
                    </x-slot>

                    <x-slot:menu class="!py-1 max-h-80 min-w-56 overflow-y-auto">
                        @foreach ($switchTypes as $type)
                            <li>
                                <a
                                    href="{{ route('admin.masterdata.records.index', $type->code) }}"
                                    class="block px-4 py-2 text-sm font-normal hover:bg-primary-50 dark:hover:bg-cherry-800 {{ $type->id === $entityType->id ? 'font-semibold text-primary-700 dark:text-primary-400' : 'text-gray-600 dark:text-gray-300' }}"
                                    @if ($type->id === $entityType->id) aria-current="page" @endif
                                >
                                    {{ $type->name ?: $type->code }}
                                </a>
                            </li>
                        @endforeach
                    </x-slot>
                </x-admin::dropdown>
            </x-slot>
        @endif

        <x-slot:actions>
            @if ($entityType->is_hierarchical)
                <a
                    href="{{ route('admin.masterdata.records.tree', $entityType->code) }}"
                    class="secondary-button"
                >
                    @lang('masterdata::app.hierarchies.tree.open')
                </a>
            @endif

            @if ($topLevelUrl)
                <a
                    href="{{ $topLevelUrl }}"
                    class="secondary-button"
                >
                    @lang('masterdata::app.records.index.top-level')
                </a>
            @endif

            @if ($canCreate)
                <a
                    href="{{ route('admin.masterdata.records.create', $entityType->code) }}"
                    class="primary-button"
                >
                    @lang('masterdata::app.records.index.create-btn')
                </a>
            @endif
        </x-slot>
    </x-admin::layouts.edit-page-header>

    <x-admin::datagrid :src="route('admin.masterdata.records.index', $entityType->code)" />
</x-admin::layouts>
