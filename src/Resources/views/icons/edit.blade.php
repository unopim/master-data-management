<x-admin::layouts>
    <x-slot:title>
        @lang('masterdata::app.icons.edit.title')
    </x-slot>

    <x-admin::form
        id="icon-edit-form"
        :action="route('admin.masterdata.icons.update', $icon->id)"
        method="PUT"
        ajax="true"
    >
        <x-admin::layouts.edit-page-header
            :title="trans('masterdata::app.icons.edit.title')"
            :back-url="route('admin.masterdata.icons.index')"
            :back-label="trans('admin::app.account.edit.back-btn')"
            :save-label="trans('masterdata::app.icons.edit.save-btn')"
            form="icon-edit-form"
            :sticky="false"
        />

        @include('masterdata::icons._fields')
    </x-admin::form>
</x-admin::layouts>
