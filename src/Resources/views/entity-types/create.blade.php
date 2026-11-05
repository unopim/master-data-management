<x-admin::layouts>
    <x-slot:title>
        @lang('masterdata::app.entity-types.create.title')
    </x-slot>

    <x-admin::form
        id="entity-type-create-form"
        :action="route('admin.masterdata.entity_types.store')"
        method="POST"
        ajax="true"
    >
        <x-admin::layouts.edit-page-header
            :title="trans('masterdata::app.entity-types.create.title')"
            :back-url="route('admin.masterdata.entity_types.index')"
            :back-label="trans('admin::app.account.edit.back-btn')"
            :save-label="trans('masterdata::app.entity-types.create.save-btn')"
            form="entity-type-create-form"
            :sticky="false"
        />

        @include('masterdata::entity-types._fields')
    </x-admin::form>
</x-admin::layouts>
