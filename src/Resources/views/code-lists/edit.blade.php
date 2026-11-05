<x-admin::layouts>
    <x-slot:title>
        @lang('masterdata::app.code-lists.edit.title')
    </x-slot>

    <x-admin::layouts.edit-page-header
        :title="trans('masterdata::app.code-lists.edit.title')"
        :back-url="route('admin.masterdata.code_lists.index')"
        :back-label="trans('admin::app.account.edit.back-btn')"
        :save-label="trans('masterdata::app.code-lists.edit.save-btn')"
        form="code-list-edit-form"
        :sticky="false"
    />

    <x-admin::form
        id="code-list-edit-form"
        :action="route('admin.masterdata.code_lists.update', $codeList->id)"
        method="PUT"
        ajax="true"
    >
        @include('masterdata::code-lists._fields')
    </x-admin::form>

    @if ($version)
        @include('masterdata::code-lists._codes')
    @endif
</x-admin::layouts>
