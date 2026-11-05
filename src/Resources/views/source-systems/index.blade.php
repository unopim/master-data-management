<x-admin::layouts>
    <x-slot:title>
        @lang('masterdata::app.source-systems.index.title')
    </x-slot>

    <x-admin::layouts.page-header :title="trans('masterdata::app.source-systems.index.title')">
        <x-slot:actions>
            @if (bouncer()->hasPermission('masterdata.source_systems.create'))
                <a
                    href="{{ route('admin.masterdata.source_systems.create') }}"
                    class="primary-button"
                >
                    @lang('masterdata::app.source-systems.index.create-btn')
                </a>
            @endif
        </x-slot>
    </x-admin::layouts.page-header>

    <x-admin::datagrid :src="route('admin.masterdata.source_systems.index')" />
</x-admin::layouts>
