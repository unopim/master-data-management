<div class="p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
    <x-admin::heading
        class="mb-4"
        :title="trans('masterdata::app.webhooks.card.title')"
        :subtitle="trans('masterdata::app.webhooks.card.info')"
    />

    <x-admin::form.control-group.control
        type="hidden"
        name="masterdata_webhook[present]"
        value="1"
    />

    <x-admin::form.control-group>
        <x-admin::form.control-group.label>
            @lang('masterdata::app.webhooks.card.entity-types')
        </x-admin::form.control-group.label>

        <x-admin::form.control-group.control
            type="multiselect"
            name="masterdata_webhook[entity_types]"
            :value="json_encode(old('masterdata_webhook.entity_types', $masterdataFilters['entity_types']))"
            :label="trans('masterdata::app.webhooks.card.entity-types')"
            :placeholder="trans('masterdata::app.webhooks.card.all-entity-types')"
            :options="json_encode($entityTypeOptions)"
            track-by="id"
            label-by="label"
        />

        <x-admin::form.control-group.error control-name="masterdata_webhook.entity_types" />
    </x-admin::form.control-group>

    <x-admin::form.control-group class="!mb-0">
        <x-admin::form.control-group.label>
            @lang('masterdata::app.webhooks.card.ignore-source-systems')
        </x-admin::form.control-group.label>

        <x-admin::form.control-group.control
            type="multiselect"
            name="masterdata_webhook[ignore_source_systems]"
            :value="json_encode(old('masterdata_webhook.ignore_source_systems', $masterdataFilters['ignore_source_systems']))"
            :label="trans('masterdata::app.webhooks.card.ignore-source-systems')"
            :placeholder="trans('masterdata::app.webhooks.card.no-source-systems')"
            :options="json_encode($sourceSystemOptions)"
            track-by="id"
            label-by="label"
        />

        <x-admin::form.control-group.error control-name="masterdata_webhook.ignore_source_systems" />

        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
            @lang('masterdata::app.webhooks.card.ignore-source-systems-hint')
        </p>
    </x-admin::form.control-group>
</div>
