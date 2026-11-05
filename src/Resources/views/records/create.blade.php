<x-admin::layouts>
    <x-slot:title>
        {{ trans('masterdata::app.records.create.title', ['type' => $entityType->name ?: $entityType->code]) }}
    </x-slot>

    <x-admin::form
        id="record-create-form"
        :action="route('admin.masterdata.records.store', $entityType->code)"
        method="POST"
        ajax="true"
    >
        <x-admin::layouts.edit-page-header
            :title="trans('masterdata::app.records.create.title', ['type' => $entityType->name ?: $entityType->code])"
            :back-url="route('admin.masterdata.records.index', $entityType->code)"
            :back-label="trans('admin::app.account.edit.back-btn')"
            :save-label="trans('masterdata::app.records.create.save-btn')"
            form="record-create-form"
            :sticky="false"
        />

        @include('masterdata::records._form', ['record' => null])
    </x-admin::form>
</x-admin::layouts>
