@php
    $sides = collect([
        ['id' => \Webkul\MasterDataManagement\Services\SurvivorshipResolver::SURVIVOR, 'label' => trans('masterdata::app.records.merge.keep-survivor')],
        ['id' => \Webkul\MasterDataManagement\Services\SurvivorshipResolver::MERGED, 'label' => trans('masterdata::app.records.merge.keep-merged')],
    ]);
@endphp

<x-admin::layouts>
    <x-slot:title>
        {{ trans('masterdata::app.records.merge.title', ['code' => $survivor->code]) }}
    </x-slot>

    <x-admin::layouts.edit-page-header
        :title="trans('masterdata::app.records.merge.title', ['code' => $survivor->code])"
        :back-url="route('admin.masterdata.records.edit', $survivor->uuid)"
        :back-label="trans('admin::app.account.edit.back-btn')"
        :save-label="$merged !== null ? trans('masterdata::app.records.merge.submit') : null"
        :form="$merged !== null ? 'record-merge-form' : null"
        :sticky="false"
    />

    <div class="mt-3.5 p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
        <p class="mb-4 text-sm text-gray-600 dark:text-gray-300">
            {{ trans('masterdata::app.records.merge.info', ['code' => $survivor->code]) }}
        </p>

        <x-admin::form
            method="GET"
            :action="route('admin.masterdata.records.merge.create', $survivor->uuid)"
        >
            <x-admin::form.control-group>
                <x-admin::form.control-group.label class="required">
                    @lang('masterdata::app.records.merge.duplicate')
                </x-admin::form.control-group.label>

                <x-admin::form.control-group.control
                    type="select"
                    name="with"
                    rules="required"
                    :value="$merged?->uuid"
                    :label="trans('masterdata::app.records.merge.duplicate')"
                    :placeholder="trans('masterdata::app.records.merge.select-duplicate')"
                    async="true"
                    :list-route="route('admin.masterdata.records.merge.options', $survivor->uuid)"
                    track-by="id"
                    label-by="label"
                />

                <x-admin::form.control-group.help>
                    @lang('masterdata::app.help.records.merge-duplicate')
                </x-admin::form.control-group.help>

                <x-admin::form.control-group.error control-name="with" />
            </x-admin::form.control-group>

            <button
                type="submit"
                class="secondary-button"
            >
                @lang('masterdata::app.records.merge.compare')
            </button>
        </x-admin::form>
    </div>

    @if ($merged !== null)
        <x-admin::form
            id="record-merge-form"
            :action="route('admin.masterdata.records.merge.store', $survivor->uuid)"
            ajax="true"
            :track-dirty="false"
        >
            <x-admin::form.control-group.control type="hidden" name="merged" :value="$merged->uuid" />
            <x-admin::form.control-group.control type="hidden" name="survivor_revision" :value="$survivor->revision" />
            <x-admin::form.control-group.control type="hidden" name="merged_revision" :value="$merged->revision" />

            <div class="mt-3.5 p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
                <p class="mb-4 text-sm text-gray-600 dark:text-gray-300">
                    {{ trans('masterdata::app.records.merge.moves-info', ['survivor' => $survivor->code, 'merged' => $merged->code]) }}
                </p>

                <x-admin::form.control-group.error control-name="merged" />

                @if ($preview === [])
                    <x-admin::list.empty-state :title="trans('masterdata::app.records.merge.no-values')" />
                @else
                    <x-admin::table>
                        <x-admin::table.thead class="text-sm font-medium dark:bg-gray-800">
                            <x-admin::table.thead.tr>
                                <x-admin::table.th>@lang('masterdata::app.records.merge.field')</x-admin::table.th>
                                <x-admin::table.th>{{ trans('masterdata::app.records.merge.survivor-value', ['code' => $survivor->code]) }}</x-admin::table.th>
                                <x-admin::table.th>{{ trans('masterdata::app.records.merge.merged-value', ['code' => $merged->code]) }}</x-admin::table.th>
                                <x-admin::table.th>@lang('masterdata::app.records.merge.keep')</x-admin::table.th>
                            </x-admin::table.thead.tr>
                        </x-admin::table.thead>

                        <x-admin::table.tbody>
                            @foreach ($preview as $code => $row)
                                <x-admin::table.tbody.tr>
                                    <x-admin::table.td>{{ $fieldLabels[$code] ?? $code }}</x-admin::table.td>
                                    <x-admin::table.td class="!whitespace-normal">{{ $row['survivor'] }}</x-admin::table.td>
                                    <x-admin::table.td class="!whitespace-normal">{{ $row['merged'] }}</x-admin::table.td>
                                    <x-admin::table.td class="min-w-[220px]">
                                        <x-admin::form.control-group class="!mb-0">
                                            <x-admin::form.control-group.control
                                                type="select"
                                                :name="'choices['.$code.']'"
                                                :value="$row['suggested']"
                                                :label="trans('masterdata::app.records.merge.keep')"
                                                :options="json_encode($sides)"
                                                track-by="id"
                                                label-by="label"
                                            />
                                        </x-admin::form.control-group>
                                    </x-admin::table.td>
                                </x-admin::table.tbody.tr>
                            @endforeach
                        </x-admin::table.tbody>
                    </x-admin::table>
                @endif
            </div>
        </x-admin::form>
    @endif
</x-admin::layouts>
