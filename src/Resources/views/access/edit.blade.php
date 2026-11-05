<x-admin::layouts>
    <x-slot:title>
        @lang('masterdata::app.access.edit.title')
    </x-slot>

    <x-admin::form
        id="access-grant-edit-form"
        :action="route('admin.masterdata.access.update', $grant->id)"
        method="PUT"
        ajax="true"
    >
        <x-admin::layouts.edit-page-header
            :title="trans('masterdata::app.access.edit.title')"
            :back-url="route('admin.masterdata.access.index')"
            :back-label="trans('admin::app.account.edit.back-btn')"
            :save-label="trans('masterdata::app.access.form.save-btn')"
            form="access-grant-edit-form"
            :sticky="false"
        />

        @include('masterdata::access._fields')
    </x-admin::form>
</x-admin::layouts>
