@php
    $isNew = $record === null;
@endphp

<div class="flex gap-2.5 mt-3.5 max-xl:flex-wrap">
    <div class="flex flex-col gap-2 flex-1 max-xl:flex-auto">
        @if ($groups === [])
            <div class="p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
                <p class="mb-2 text-base text-gray-800 dark:text-white font-semibold">
                    @lang('masterdata::app.records.form.values')
                </p>

                <x-admin::list.empty-state :title="trans('masterdata::app.records.form.no-fields')" />
            </div>
        @endif

        @foreach ($groups as $group)
            <div class="p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
                <p class="mb-4 text-base text-gray-800 dark:text-white font-semibold">
                    {{ $group['group'] ?? (count($groups) > 1 ? trans('masterdata::app.records.form.other-group') : trans('masterdata::app.records.form.values')) }}
                </p>

                @foreach ($group['fields'] as $field)
                    @include('masterdata::records._value-control', ['field' => $field])
                @endforeach
            </div>
        @endforeach

        @if (! $isNew && $otherValues !== [])
            <div class="p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
                <p class="mb-1 text-base text-gray-800 dark:text-white font-semibold">
                    @lang('masterdata::app.records.form.other-values')
                </p>

                <p class="mb-3 text-xs text-gray-600 dark:text-gray-300">
                    @lang('masterdata::app.records.form.other-values-info')
                </p>

                <x-admin::table>
                    <x-admin::table.thead class="text-sm font-medium dark:bg-gray-800">
                        <x-admin::table.thead.tr>
                            <x-admin::table.th>@lang('masterdata::app.records.values.key')</x-admin::table.th>
                            <x-admin::table.th>@lang('masterdata::app.records.values.locale')</x-admin::table.th>
                            <x-admin::table.th>@lang('masterdata::app.records.values.value')</x-admin::table.th>
                        </x-admin::table.thead.tr>
                    </x-admin::table.thead>

                    <x-admin::table.tbody>
                        @foreach ($otherValues as $row)
                            <x-admin::table.tbody.tr>
                                <x-admin::table.td>{{ $row['key'] }}</x-admin::table.td>
                                <x-admin::table.td>{{ $row['locale'] }}</x-admin::table.td>
                                <x-admin::table.td class="!whitespace-normal">{{ $row['value'] }}</x-admin::table.td>
                            </x-admin::table.tbody.tr>
                        @endforeach
                    </x-admin::table.tbody>
                </x-admin::table>
            </div>
        @endif
    </div>

    <div class="flex flex-col gap-2 w-[360px] max-w-full max-sm:w-full">
        <div class="p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
            <p class="mb-4 text-base text-gray-800 dark:text-white font-semibold">
                @lang('masterdata::app.records.form.general')
            </p>

            @unless ($isNew)
                <x-admin::form.control-group.control
                    type="hidden"
                    name="revision"
                    :value="$record->revision"
                />
            @endunless

            <x-admin::form.control-group>
                <x-admin::form.control-group.label :class="$isNew && ! $codeMode['generated'] ? 'required' : ''">
                    @lang('masterdata::app.records.fields.code')
                </x-admin::form.control-group.label>

                @if (! $isNew)
                    <x-admin::form.control-group.control
                        type="text"
                        name="code_display"
                        :value="$record->code"
                        :label="trans('masterdata::app.records.fields.code')"
                        disabled="disabled"
                    />

                    <x-admin::form.control-group.help>
                        @lang('masterdata::app.help.records.code')
                    </x-admin::form.control-group.help>
                @elseif ($codeMode['generated'])
                    <p class="text-sm text-gray-600 dark:text-gray-300">
                        {{ $codeMode['hint'] }}
                    </p>
                @else
                    <x-admin::form.control-group.control
                        type="text"
                        name="code"
                        rules="required"
                        :label="trans('masterdata::app.records.fields.code')"
                        :placeholder="trans('masterdata::app.records.fields.code')"
                    />

                    <x-admin::form.control-group.help>
                        @lang('masterdata::app.help.records.code')
                    </x-admin::form.control-group.help>

                    <x-admin::form.control-group.error control-name="code" />
                @endif
            </x-admin::form.control-group>

            <x-admin::form.control-group>
                <x-admin::form.control-group.label>
                    @lang('masterdata::app.records.fields.label')
                </x-admin::form.control-group.label>

                @if ($labelField)
                    @unless ($isNew)
                        <x-admin::form.control-group.control
                            type="text"
                            name="label_display"
                            :value="$record->label"
                            :label="trans('masterdata::app.records.fields.label')"
                            disabled="disabled"
                        />
                    @endunless

                    <x-admin::form.control-group.help>
                        {{ trans('masterdata::app.records.form.label-from-field', ['field' => $labelField->label ?: $labelField->code]) }}
                    </x-admin::form.control-group.help>
                @else
                    <x-admin::form.control-group.control
                        type="text"
                        name="label"
                        :value="$record?->label"
                        :label="trans('masterdata::app.records.fields.label')"
                        :placeholder="trans('masterdata::app.records.fields.label')"
                    />

                    <x-admin::form.control-group.help>
                        @lang('masterdata::app.help.records.label')
                    </x-admin::form.control-group.help>

                    <x-admin::form.control-group.error control-name="label" />
                @endif
            </x-admin::form.control-group>

            <x-admin::form.control-group>
                <x-admin::form.control-group.label class="required">
                    @lang('masterdata::app.records.fields.status')
                </x-admin::form.control-group.label>

                <x-admin::form.control-group.control
                    type="select"
                    name="status"
                    rules="required"
                    :value="$record?->status?->value ?? \Webkul\MasterDataManagement\Enums\RecordStatus::Draft->value"
                    :label="trans('masterdata::app.records.fields.status')"
                    :options="json_encode([
                        ['id' => 'draft', 'label' => trans('masterdata::app.records.statuses.draft')],
                        ['id' => 'active', 'label' => trans('masterdata::app.records.statuses.active')],
                    ])"
                    track-by="id"
                    label-by="label"
                />

                <x-admin::form.control-group.help>
                    @lang('masterdata::app.help.records.status')
                </x-admin::form.control-group.help>

                <x-admin::form.control-group.error control-name="status" />
            </x-admin::form.control-group>

            @if ($isNew && $entityType->is_hierarchical)
                <x-admin::form.control-group class="!mb-0">
                    <x-admin::form.control-group.label>
                        @lang('masterdata::app.hierarchy.parent')
                    </x-admin::form.control-group.label>

                    <x-admin::form.control-group.control
                        type="select"
                        name="parent"
                        async="true"
                        :list-route="route('admin.masterdata.records.options', $entityType->code)"
                        :value="json_encode($rootOption)"
                        :label="trans('masterdata::app.hierarchy.parent')"
                        :placeholder="trans('masterdata::app.hierarchy.select-parent')"
                        track-by="id"
                        label-by="label"
                    />

                    <x-admin::form.control-group.help>
                        @lang('masterdata::app.help.records.parent')
                    </x-admin::form.control-group.help>

                    <x-admin::form.control-group.error control-name="parent" />
                </x-admin::form.control-group>
            @endif
        </div>
    </div>
</div>
