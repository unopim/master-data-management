<x-admin::layouts>
    <x-slot:title>
        @lang('masterdata::app.code-lists.index.title')
    </x-slot>

    <x-admin::layouts.page-header :title="trans('masterdata::app.code-lists.index.title')">
        <x-slot:actions>
            @if (bouncer()->hasPermission('masterdata.code_lists.create'))
                <a
                    href="{{ route('admin.masterdata.code_lists.create') }}"
                    class="primary-button"
                >
                    @lang('masterdata::app.code-lists.index.create-btn')
                </a>
            @endif
        </x-slot>
    </x-admin::layouts.page-header>

    @include('masterdata::code-lists._tabs', ['activeReferenceTab' => 'code-lists'])

    <x-admin::datagrid :src="route('admin.masterdata.code_lists.index')" />
</x-admin::layouts>
