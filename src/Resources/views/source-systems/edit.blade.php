<x-admin::layouts.with-history
    :active-tab="request()->has('history') ? 'history' : 'general'"
    entity-name="masterdata_source_system"
    :history-id="$sourceSystem->id"
    :tab-items="[
        [
            'key'   => 'general',
            'url'   => route('admin.masterdata.source_systems.edit', $sourceSystem->id),
            'label' => 'masterdata::app.source-systems.form.general',
        ],
    ]"
>
    <x-slot:title>
        @lang('masterdata::app.source-systems.edit.title')
    </x-slot>

    <x-slot:pageHeader>
        <x-admin::layouts.edit-page-header
            :title="trans('masterdata::app.source-systems.edit.title')"
            :back-url="route('admin.masterdata.source_systems.index')"
            :back-label="trans('admin::app.account.edit.back-btn')"
            :save-label="request()->has('history') ? null : trans('masterdata::app.source-systems.edit.save-btn')"
            form="source-system-edit-form"
            :sticky="false"
        />
    </x-slot>

    <x-admin::form
        id="source-system-edit-form"
        :action="route('admin.masterdata.source_systems.update', $sourceSystem->id)"
        method="PUT"
        ajax="true"
    >
        @include('masterdata::source-systems._fields')
    </x-admin::form>
</x-admin::layouts.with-history>
