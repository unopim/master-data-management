<x-admin::layouts>
    <x-slot:title>
        @lang('masterdata::app.access.index.title')
    </x-slot>

    <x-admin::layouts.page-header :title="trans('masterdata::app.access.index.title')">
        <x-slot:actions>
            @if (bouncer()->hasPermission('masterdata.access.create'))
                <a
                    href="{{ route('admin.masterdata.access.create') }}"
                    class="primary-button"
                >
                    @lang('masterdata::app.access.index.create-btn')
                </a>
            @endif
        </x-slot>
    </x-admin::layouts.page-header>

    <p class="mb-4 text-sm text-gray-600 dark:text-gray-300">
        @lang('masterdata::app.access.index.info')
    </p>

    <x-admin::datagrid :src="route('admin.masterdata.access.index')" />
</x-admin::layouts>
