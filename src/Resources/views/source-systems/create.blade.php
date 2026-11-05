<x-admin::layouts>
    <x-slot:title>
        @lang('masterdata::app.source-systems.create.title')
    </x-slot>

    <x-admin::form
        id="source-system-create-form"
        :action="route('admin.masterdata.source_systems.store')"
        method="POST"
        ajax="true"
    >
        <x-admin::layouts.edit-page-header
            :title="trans('masterdata::app.source-systems.create.title')"
            :back-url="route('admin.masterdata.source_systems.index')"
            :back-label="trans('admin::app.account.edit.back-btn')"
            :save-label="trans('masterdata::app.source-systems.create.save-btn')"
            form="source-system-create-form"
            :sticky="false"
        />

        @include('masterdata::source-systems._fields')
    </x-admin::form>
</x-admin::layouts>
