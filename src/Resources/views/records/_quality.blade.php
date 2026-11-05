@php
    $severityVariants = ['error' => 'danger', 'warning' => 'warning', 'info' => 'info'];
@endphp

<div class="grid gap-6">
    <section>
        <p class="mb-2 text-base text-gray-800 dark:text-white font-semibold">
            @lang('masterdata::app.quality.completeness.title')
        </p>

        @if ($completeness->score === null)
            <x-admin::list.empty-state :title="trans('masterdata::app.quality.completeness.none')" />
        @else
            <div class="flex flex-wrap items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
                <x-admin::badge :variant="\Webkul\MasterDataManagement\Quality\Completeness::variant($completeness->score)">
                    {{ trans('masterdata::app.quality.completeness.percent', ['score' => $completeness->score]) }}
                </x-admin::badge>

                <span>
                    {{ trans('masterdata::app.quality.completeness.summary', ['filled' => $completeness->filled, 'total' => $completeness->total]) }}
                </span>
            </div>

            @if ($completeness->missing !== [])
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                    {{ trans('masterdata::app.quality.completeness.missing-list', ['fields' => implode(', ', $completeness->missing)]) }}
                </p>
            @endif
        @endif
    </section>

    <section>
        <p class="mb-2 text-base text-gray-800 dark:text-white font-semibold">
            @lang('masterdata::app.quality.record.issues')
        </p>

        @if ($issues === [])
            <x-admin::list.empty-state :title="trans('masterdata::app.quality.record.no-issues')" />
        @else
            <x-admin::table>
                <x-admin::table.thead class="text-sm font-medium dark:bg-gray-800">
                    <x-admin::table.thead.tr>
                        <x-admin::table.th>@lang('masterdata::app.quality.datagrid.rule')</x-admin::table.th>
                        <x-admin::table.th>@lang('masterdata::app.quality.datagrid.severity')</x-admin::table.th>
                        <x-admin::table.th>@lang('masterdata::app.quality.datagrid.message')</x-admin::table.th>
                        <x-admin::table.th>@lang('masterdata::app.quality.datagrid.status')</x-admin::table.th>
                        <x-admin::table.th>@lang('masterdata::app.quality.datagrid.detected-at')</x-admin::table.th>

                        @if ($canDecide)
                            <x-admin::table.th>@lang('masterdata::app.quality.index.actions')</x-admin::table.th>
                        @endif
                    </x-admin::table.thead.tr>
                </x-admin::table.thead>

                <x-admin::table.tbody>
                    @foreach ($issues as $issue)
                        <x-admin::table.tbody.tr>
                            <x-admin::table.td>{{ $issue['rule'] }}</x-admin::table.td>

                            <x-admin::table.td>
                                <x-admin::badge :variant="$severityVariants[$issue['severity']] ?? 'neutral'">
                                    @lang('masterdata::app.quality.severities.'.$issue['severity'])
                                </x-admin::badge>
                            </x-admin::table.td>

                            <x-admin::table.td class="!whitespace-normal">
                                @if ($issue['other_uuid'])
                                    <a
                                        href="{{ route('admin.masterdata.records.edit', $issue['other_uuid']) }}"
                                        class="text-primary-600 hover:text-primary-700 dark:text-primary-400"
                                    >
                                        {{ $issue['message'] }}
                                    </a>
                                @else
                                    {{ $issue['message'] }}
                                @endif
                            </x-admin::table.td>

                            <x-admin::table.td>@lang('masterdata::app.quality.statuses.'.$issue['status'])</x-admin::table.td>

                            <x-admin::table.td>
                                {{ \Webkul\MasterDataManagement\Support\AdminDate::format($issue['detected_at']) }}
                            </x-admin::table.td>

                            @if ($canDecide)
                                <x-admin::table.td>
                                    @if ($issue['open'])
                                        <div class="flex gap-2">
                                            <v-masterdata-confirm-action
                                                url="{{ route('admin.masterdata.quality.issues.resolve', $issue['id']) }}"
                                                :payload="{ return_to: 'record', record: '{{ $record->uuid }}' }"
                                                label="@lang('masterdata::app.quality.actions.resolve')"
                                                title="@lang('masterdata::app.quality.actions.resolve-confirm-title')"
                                                message="@lang('masterdata::app.quality.actions.resolve-confirm-message')"
                                            ></v-masterdata-confirm-action>

                                            <v-masterdata-confirm-action
                                                url="{{ route('admin.masterdata.quality.issues.ignore', $issue['id']) }}"
                                                :payload="{ return_to: 'record', record: '{{ $record->uuid }}' }"
                                                label="@lang('masterdata::app.quality.actions.ignore')"
                                                title="@lang('masterdata::app.quality.actions.ignore-confirm-title')"
                                                message="@lang('masterdata::app.quality.actions.ignore-confirm-message')"
                                                button-class="transparent-button"
                                            ></v-masterdata-confirm-action>
                                        </div>
                                    @endif
                                </x-admin::table.td>
                            @endif
                        </x-admin::table.tbody.tr>
                    @endforeach
                </x-admin::table.tbody>
            </x-admin::table>
        @endif
    </section>
</div>
