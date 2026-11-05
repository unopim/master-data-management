<x-admin::layouts>
    <x-slot:title>
        @lang('masterdata::app.icons.index.title')
    </x-slot>

    <x-admin::layouts.page-header :title="trans('masterdata::app.icons.index.title')">
        <x-slot:actions>
            @if (bouncer()->hasPermission('masterdata.icons.create'))
                <a
                    href="{{ route('admin.masterdata.icons.create') }}"
                    class="primary-button"
                >
                    @lang('masterdata::app.icons.index.create-btn')
                </a>
            @endif
        </x-slot>
    </x-admin::layouts.page-header>

    <x-admin::datagrid :src="route('admin.masterdata.icons.index')" />
</x-admin::layouts>
