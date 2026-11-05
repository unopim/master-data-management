<x-admin::layouts>
    <x-slot:title>
        @lang('masterdata::app.quality.index.title')
    </x-slot>

    <x-admin::layouts.page-header :title="trans('masterdata::app.quality.index.title')" />

    <p class="mt-3.5 text-sm text-gray-600 dark:text-gray-300">
        @lang('masterdata::app.quality.index.info')
    </p>

    <div class="mt-3.5 p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
        <p class="mb-4 text-base text-gray-800 dark:text-white font-semibold">
            @lang('masterdata::app.quality.index.summary')
        </p>

        @if ($summary === [])
            <x-admin::list.empty-state :title="trans('masterdata::app.quality.index.empty')" />
        @else
            <x-admin::table>
                <x-admin::table.thead class="text-sm font-medium dark:bg-gray-800">
                    <x-admin::table.thead.tr>
                        <x-admin::table.th>@lang('masterdata::app.quality.index.entity-type')</x-admin::table.th>
                        <x-admin::table.th>@lang('masterdata::app.quality.index.records')</x-admin::table.th>
                        <x-admin::table.th>@lang('masterdata::app.quality.index.average')</x-admin::table.th>
                        <x-admin::table.th>@lang('masterdata::app.quality.index.complete')</x-admin::table.th>
                        <x-admin::table.th>@lang('masterdata::app.quality.index.open-issues')</x-admin::table.th>
                        <x-admin::table.th>@lang('masterdata::app.quality.index.last-scan')</x-admin::table.th>

                        @if ($canScan)
                            <x-admin::table.th>@lang('masterdata::app.quality.index.actions')</x-admin::table.th>
                        @endif
                    </x-admin::table.thead.tr>
                </x-admin::table.thead>

                <x-admin::table.tbody>
                    @foreach ($summary as $row)
                        <x-admin::table.tbody.tr>
                            <x-admin::table.td>
                                <a
                                    href="{{ route('admin.masterdata.records.index', $row['type']->code) }}"
                                    class="text-primary-600 hover:text-primary-700 dark:text-primary-400"
                                >
                                    {{ $row['type']->name ?: $row['type']->code }}
                                </a>
                            </x-admin::table.td>

                            <x-admin::table.td>{{ $row['records'] }}</x-admin::table.td>

                            <x-admin::table.td>
                                @if ($row['average'] === null)
                                    @lang('masterdata::app.quality.completeness.not-scored')
                                @else
                                    <x-admin::badge :variant="\Webkul\MasterDataManagement\Quality\Completeness::variant($row['average'])">
                                        {{ trans('masterdata::app.quality.completeness.percent', ['score' => $row['average']]) }}
                                    </x-admin::badge>
                                @endif
                            </x-admin::table.td>

                            <x-admin::table.td>
                                {{ trans('masterdata::app.quality.index.complete-of', ['complete' => $row['complete'], 'records' => $row['records']]) }}
                            </x-admin::table.td>

                            <x-admin::table.td>
                                <div class="flex flex-wrap gap-1.5">
                                    <x-admin::badge :variant="$row['errors'] > 0 ? 'danger' : 'neutral'">
                                        {{ trans('masterdata::app.quality.index.errors', ['count' => $row['errors']]) }}
                                    </x-admin::badge>

                                    <x-admin::badge :variant="$row['warnings'] > 0 ? 'warning' : 'neutral'">
                                        {{ trans('masterdata::app.quality.index.warnings', ['count' => $row['warnings']]) }}
                                    </x-admin::badge>
                                </div>
                            </x-admin::table.td>

                            <x-admin::table.td>
                                {{ \Webkul\MasterDataManagement\Support\AdminDate::format($row['scanned_at'], trans('masterdata::app.quality.index.never')) }}
                            </x-admin::table.td>

                            @if ($canScan)
                                <x-admin::table.td>
                                    <v-masterdata-confirm-action
                                        url="{{ route('admin.masterdata.quality.scan', $row['type']->id) }}"
                                        label="@lang('masterdata::app.quality.scan.btn')"
                                        title="@lang('masterdata::app.quality.scan.confirm-title')"
                                        message="@lang('masterdata::app.quality.scan.confirm-message')"
                                    ></v-masterdata-confirm-action>
                                </x-admin::table.td>
                            @endif
                        </x-admin::table.tbody.tr>
                    @endforeach
                </x-admin::table.tbody>
            </x-admin::table>
        @endif
    </div>

    <x-admin::heading
        class="mt-6 mb-3.5"
        :title="trans('masterdata::app.quality.index.issues')"
    />

    <x-admin::datagrid :src="route('admin.masterdata.quality.index')" />

    @include('masterdata::partials.confirm-action')
</x-admin::layouts>
