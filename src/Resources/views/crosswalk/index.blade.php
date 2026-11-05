<x-admin::layouts>
    <x-slot:title>
        @lang('masterdata::app.crosswalk.title')
    </x-slot>

    <x-admin::layouts.page-header :title="trans('masterdata::app.crosswalk.title')" />

    <div class="mt-3.5 p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
        <p class="mb-4 text-sm text-gray-600 dark:text-gray-300">
            @lang('masterdata::app.crosswalk.info')
        </p>

        <x-admin::form
            :action="route('admin.masterdata.crosswalk.index')"
            method="GET"
        >
            <div class="grid grid-cols-3 gap-4 max-lg:grid-cols-1">
                <x-admin::form.control-group>
                    <x-admin::form.control-group.label class="required">
                        @lang('masterdata::app.crosswalk.fields.system')
                    </x-admin::form.control-group.label>

                    <x-admin::form.control-group.control
                        type="select"
                        name="source_system_id"
                        rules="required"
                        :value="$lookup['source_system_id'] ?? null"
                        :label="trans('masterdata::app.crosswalk.fields.system')"
                        :placeholder="trans('masterdata::app.crosswalk.fields.select')"
                        async="true"
                        :list-route="route('admin.masterdata.source_systems.options')"
                        query-params='{"inactive":1}'
                        track-by="id"
                        label-by="label"
                    />

                    <x-admin::form.control-group.help>
                        @lang('masterdata::app.help.crosswalk.system')
                    </x-admin::form.control-group.help>

                    <x-admin::form.control-group.error control-name="source_system_id" />
                </x-admin::form.control-group>

                <x-admin::form.control-group>
                    <x-admin::form.control-group.label class="required">
                        @lang('masterdata::app.crosswalk.fields.entity-type')
                    </x-admin::form.control-group.label>

                    <x-admin::form.control-group.control
                        type="select"
                        name="entity_type_id"
                        rules="required"
                        :value="$lookup['entity_type_id'] ?? null"
                        :label="trans('masterdata::app.crosswalk.fields.entity-type')"
                        :placeholder="trans('masterdata::app.crosswalk.fields.select')"
                        :options="json_encode($typeOptions)"
                        track-by="id"
                        label-by="label"
                    />

                    <x-admin::form.control-group.help>
                        @lang('masterdata::app.help.crosswalk.entity-type')
                    </x-admin::form.control-group.help>

                    <x-admin::form.control-group.error control-name="entity_type_id" />
                </x-admin::form.control-group>

                <x-admin::form.control-group>
                    <x-admin::form.control-group.label class="required">
                        @lang('masterdata::app.crosswalk.fields.external-id')
                    </x-admin::form.control-group.label>

                    <x-admin::form.control-group.control
                        type="text"
                        name="external_id"
                        rules="required|max:255"
                        :value="$lookup['external_id'] ?? null"
                        :label="trans('masterdata::app.crosswalk.fields.external-id')"
                        :placeholder="trans('masterdata::app.crosswalk.fields.external-id')"
                    />

                    <x-admin::form.control-group.help>
                        @lang('masterdata::app.help.crosswalk.external-id')
                    </x-admin::form.control-group.help>

                    <x-admin::form.control-group.error control-name="external_id" />
                </x-admin::form.control-group>
            </div>

            <button
                type="submit"
                class="primary-button"
            >
                @lang('masterdata::app.crosswalk.lookup-btn')
            </button>
        </x-admin::form>
    </div>

    @if ($result !== null)
        <div class="mt-3.5 p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
            @if ($result['record'] === null)
                <p
                    class="text-sm text-gray-600 dark:text-gray-300"
                    role="status"
                >
                    {{ trans('masterdata::app.crosswalk.not-found', [
                        'id'     => $lookup['external_id'],
                        'system' => $result['system'],
                    ]) }}
                </p>
            @else
                @php($record = $result['record'])

                <div class="flex flex-wrap items-center justify-between gap-2.5 mb-4">
                    <div>
                        <p class="text-base text-gray-800 dark:text-white font-semibold">
                            {{ trans('masterdata::app.crosswalk.found-label', ['label' => $record->label ?: $record->code]) }}
                        </p>

                        <p class="text-sm text-gray-600 dark:text-gray-300">
                            {{ trans('masterdata::app.crosswalk.summary', [
                                'code'   => $record->code,
                                'type'   => $record->entityType->name ?: $record->entityType->code,
                                'status' => trans('masterdata::app.records.statuses.'.$record->status->value),
                            ]) }}
                        </p>
                    </div>

                    <a
                        href="{{ route('admin.masterdata.records.edit', $record->uuid) }}"
                        class="secondary-button"
                    >
                        @lang('masterdata::app.crosswalk.open-record')
                    </a>
                </div>

                <p class="mb-2 text-sm text-gray-800 dark:text-white font-semibold">
                    @lang('masterdata::app.crosswalk.ids')
                </p>

                <x-admin::table>
                    <x-admin::table.thead class="text-sm font-medium dark:bg-gray-800">
                        <x-admin::table.thead.tr>
                            <x-admin::table.th>@lang('masterdata::app.records.xrefs.system')</x-admin::table.th>
                            <x-admin::table.th>@lang('masterdata::app.records.xrefs.external-id')</x-admin::table.th>
                        </x-admin::table.thead.tr>
                    </x-admin::table.thead>

                    <x-admin::table.tbody>
                        @foreach ($result['ids'] as $row)
                            <x-admin::table.tbody.tr>
                                <x-admin::table.td>{{ $row['name'] }} ({{ $row['code'] }})</x-admin::table.td>
                                <x-admin::table.td class="!whitespace-normal">{{ implode(', ', $row['ids']) }}</x-admin::table.td>
                            </x-admin::table.tbody.tr>
                        @endforeach
                    </x-admin::table.tbody>
                </x-admin::table>
            @endif
        </div>
    @endif
</x-admin::layouts>
