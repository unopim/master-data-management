<x-admin::layouts>
    <x-slot:title>
        @lang('masterdata::app.icons.create.title')
    </x-slot>

    <x-admin::form
        id="icon-create-form"
        :action="route('admin.masterdata.icons.store')"
        method="POST"
        ajax="true"
    >
        <x-admin::layouts.edit-page-header
            :title="trans('masterdata::app.icons.create.title')"
            :back-url="route('admin.masterdata.icons.index')"
            :back-label="trans('admin::app.account.edit.back-btn')"
            :save-label="trans('masterdata::app.icons.create.save-btn')"
            form="icon-create-form"
            :sticky="false"
        />

        @include('masterdata::icons._fields')
    </x-admin::form>
</x-admin::layouts>
