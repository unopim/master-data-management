<x-admin::layouts>
    <x-slot:title>
        @lang('masterdata::app.access.create.title')
    </x-slot>

    <x-admin::form
        id="access-grant-create-form"
        :action="route('admin.masterdata.access.store')"
        method="POST"
        ajax="true"
    >
        <x-admin::layouts.edit-page-header
            :title="trans('masterdata::app.access.create.title')"
            :back-url="route('admin.masterdata.access.index')"
            :back-label="trans('admin::app.account.edit.back-btn')"
            :save-label="trans('masterdata::app.access.form.save-btn')"
            form="access-grant-create-form"
            :sticky="false"
        />

        @include('masterdata::access._fields')
    </x-admin::form>
</x-admin::layouts>
