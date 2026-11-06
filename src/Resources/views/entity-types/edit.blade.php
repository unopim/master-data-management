@php
    $activeTab = match (true) {
        request()->has('history')     => 'history',
        request()->has('permissions') => 'permissions',
        request()->has('fields')      => 'fields',
        request()->has('relations')   => 'relations',
        request()->has('matching')    => 'matching',
        request()->has('survivorship') => 'survivorship',
        request()->has('validation')   => 'validation',
        default                       => 'general',
    };

    $saveForm = $activeTab === 'permissions' ? 'entity-type-permissions-form' : 'entity-type-edit-form';

    $hasSaveButton = ! in_array($activeTab, ['history', 'fields', 'relations', 'matching', 'survivorship', 'validation'], true);
@endphp

<x-admin::layouts.with-history
    :active-tab="$activeTab"
    entity-name="masterdata_entity_type"
    :history-id="$entityType->id"
    :tab-items="[
        [
            'key'   => 'general',
            'url'   => route('admin.masterdata.entity_types.edit', $entityType->id),
            'label' => 'masterdata::app.entity-types.form.general',
        ],
        [
            'key'   => 'fields',
            'url'   => route('admin.masterdata.entity_types.edit', ['id' => $entityType->id, 'fields' => 1]),
            'label' => 'masterdata::app.fields.tab',
        ],
        [
            'key'   => 'relations',
            'url'   => route('admin.masterdata.entity_types.edit', ['id' => $entityType->id, 'relations' => 1]),
            'label' => 'masterdata::app.relation-types.tab',
        ],
        [
            'key'   => 'matching',
            'url'   => route('admin.masterdata.entity_types.edit', ['id' => $entityType->id, 'matching' => 1]),
            'label' => 'masterdata::app.match-rules.tab',
        ],
        [
            'key'   => 'survivorship',
            'url'   => route('admin.masterdata.entity_types.edit', ['id' => $entityType->id, 'survivorship' => 1]),
            'label' => 'masterdata::app.survivorship-rules.tab',
        ],
        [
            'key'   => 'validation',
            'url'   => route('admin.masterdata.entity_types.edit', ['id' => $entityType->id, 'validation' => 1]),
            'label' => 'masterdata::app.validation-rules.tab',
        ],
        [
            'key'   => 'permissions',
            'url'   => route('admin.masterdata.entity_types.edit', ['id' => $entityType->id, 'permissions' => 1]),
            'label' => 'masterdata::app.entity-types.permissions.tab',
        ],
    ]"
>
    <x-slot:title>
        @lang('masterdata::app.entity-types.edit.title')
    </x-slot>

    <x-slot:pageHeader>
        <x-admin::layouts.edit-page-header
            :title="trans('masterdata::app.entity-types.edit.title')"
            :back-url="route('admin.masterdata.entity_types.index')"
            :back-label="trans('admin::app.account.edit.back-btn')"
            :save-label="$hasSaveButton ? trans('masterdata::app.entity-types.edit.save-btn') : null"
            :form="$saveForm"
            :sticky="false"
        >
            <x-slot:heading>
                <span class="flex flex-wrap items-center gap-2.5">
                    <span>{{ trans('masterdata::app.entity-types.edit.title') }}</span>

                    <span class="{{ $entityType->status->badgeClass() }}">{{ trans('masterdata::app.entity-types.statuses.'.$entityType->status->value) }}</span>

                    <span class="text-sm font-normal text-gray-600 dark:text-gray-300">{{ trans('masterdata::app.entity-types.edit.schema-version', ['version' => $entityType->schema_version]) }}</span>
                </span>
            </x-slot>

            @if ($activeTab !== 'history')
                <x-slot:actions>
                    <v-masterdata-publish-schema></v-masterdata-publish-schema>
                </x-slot>
            @endif
        </x-admin::layouts.edit-page-header>
    </x-slot>

    <x-admin::form
        id="entity-type-edit-form"
        :action="route('admin.masterdata.entity_types.update', $entityType->id)"
        method="PUT"
        ajax="true"
    >
        @include('masterdata::entity-types._fields')
    </x-admin::form>

    <x-slot:tabContents>
        @if ($activeTab === 'fields')
            @include('masterdata::entity-types.fields._tab')
        @endif

        @if ($activeTab === 'relations')
            @include('masterdata::entity-types.relation-types._tab')
        @endif

        @if ($activeTab === 'matching')
            @include('masterdata::entity-types.match-rules._tab')
        @endif

        @if ($activeTab === 'survivorship')
            @include('masterdata::entity-types.survivorship-rules._tab')
        @endif

        @if ($activeTab === 'validation')
            @include('masterdata::entity-types.validation-rules._tab')

            @include('masterdata::entity-types.field-transforms._tab')
        @endif

        @if ($activeTab === 'permissions')
            <x-admin::form
                id="entity-type-permissions-form"
                :action="route('admin.masterdata.entity_types.permissions.update', $entityType->id)"
                method="PUT"
                ajax="true"
            >
                @include('masterdata::entity-types._permissions')
            </x-admin::form>
        @endif
    </x-slot>

    @pushOnce('scripts')
        <script
            type="text/x-template"
            id="v-masterdata-publish-schema-template"
        >
            <button
                type="button"
                class="secondary-button"
                @click="confirmPublish"
            >
                @lang('masterdata::app.entity-types.publish.btn')
            </button>
        </script>

        <script type="module">
            app.component('v-masterdata-publish-schema', {
                template: '#v-masterdata-publish-schema-template',

                methods: {
                    confirmPublish() {
                        this.$emitter.emit('open-confirm-modal', {
                            title: "@lang('masterdata::app.entity-types.publish.confirm-title')",
                            message: "@lang('masterdata::app.entity-types.publish.confirm-message', ['version' => $entityType->schema_version + 1])",
                            agree: () => this.publish(),
                        });
                    },

                    publish() {
                        this.$axios.post("{{ route('admin.masterdata.entity_types.publish', $entityType->id) }}")
                            .then((response) => this.$navigate(response.data.redirect_url))
                            .catch((error) => {
                                this.$emitter.emit('add-flash', { type: 'error', message: error.response?.data?.message });
                            });
                    },
                },
            });
        </script>
    @endPushOnce
</x-admin::layouts.with-history>
