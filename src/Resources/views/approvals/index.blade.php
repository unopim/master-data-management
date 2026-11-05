<x-admin::layouts>
    <x-slot:title>
        @lang('masterdata::app.approvals.index.title')
    </x-slot>

    <x-admin::layouts.page-header :title="trans('masterdata::app.approvals.index.title')" />

    <p class="mt-3.5 text-sm text-gray-600 dark:text-gray-300">
        @lang('masterdata::app.approvals.index.info')
    </p>

    <x-admin::datagrid :src="route('admin.masterdata.approvals.index')" />
</x-admin::layouts>
