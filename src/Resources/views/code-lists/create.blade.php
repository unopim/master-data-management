<x-admin::layouts>
    <x-slot:title>
        @lang('masterdata::app.code-lists.create.title')
    </x-slot>

    <x-admin::form
        id="code-list-create-form"
        :action="route('admin.masterdata.code_lists.store')"
        method="POST"
        ajax="true"
    >
        <x-admin::layouts.edit-page-header
            :title="trans('masterdata::app.code-lists.create.title')"
            :back-url="route('admin.masterdata.code_lists.index')"
            :back-label="trans('admin::app.account.edit.back-btn')"
            :save-label="trans('masterdata::app.code-lists.create.save-btn')"
            form="code-list-create-form"
            :sticky="false"
        />

        @include('masterdata::code-lists._fields')
    </x-admin::form>
</x-admin::layouts>
