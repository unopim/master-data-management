@use('Webkul\MasterDataManagement\Enums\QualityMetric')
@use('Webkul\MasterDataManagement\Enums\WorkloadAgeBucket')
@use('Webkul\MasterDataManagement\Quality\TrendChart')

<x-admin::layouts>
    <x-slot:title>
        @lang('masterdata::app.quality-dashboard.index.title')
    </x-slot>

    <x-admin::layouts.page-header :title="trans('masterdata::app.quality-dashboard.index.title')" />

    <p class="mt-3.5 text-sm text-gray-600 dark:text-gray-300">
        @lang('masterdata::app.quality-dashboard.index.info')
    </p>

    @if ($types->isEmpty())
        <div class="mt-3.5 p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
            <x-admin::list.empty-state :title="trans('masterdata::app.quality-dashboard.index.empty')" />
        </div>
    @else
        <x-admin::heading
            class="mt-6 mb-3.5"
            :title="trans('masterdata::app.quality-dashboard.index.scorecards')"
        />

        <div class="grid grid-cols-3 gap-4 max-xl:grid-cols-2 max-sm:grid-cols-1">
            @foreach ($types as $type)
                @php
                    $latest = $scorecards[$type->id]['latest'] ?? null;
                    $previous = $scorecards[$type->id]['previous'] ?? null;
                @endphp

                <x-admin::card
                    :url="route('admin.masterdata.quality_dashboard.index', ['entity_type' => $type->id, 'days' => $range->value])"
                    target="_self"
                    :icon="$type->icon ?: 'icon-dashboard'"
                    :icon-url="$type->libraryIcon?->url()"
                    :icon-mask="$type->libraryIcon?->isVector() ?? true"
                    :title="$type->name ?: $type->code"
                    :class="$selected?->id === $type->id ? '!border-primary-600' : ''"
                >
                    <span class="block mb-2 text-xs font-semibold text-gray-600 dark:text-gray-300">{{ $type->code }}</span>

                    @if ($latest === null)
                        <span class="block">@lang('masterdata::app.quality-dashboard.index.no-snapshot')</span>
                    @else
                        @foreach (QualityMetric::cases() as $metric)
                            @php
                                $delta = $metric->delta($latest, $previous);
                            @endphp

                            <span class="flex items-center justify-between gap-2 py-1">
                                <span>{{ $metric->label() }}</span>

                                <span class="flex items-center gap-1.5">
                                    <span class="font-semibold text-gray-800 dark:text-white">
                                        @if ($latest[$metric->value] === null)
                                            @lang('masterdata::app.quality-dashboard.index.not-available')
                                        @else
                                            {{ trans('masterdata::app.quality-dashboard.index.percent', ['value' => number_format($latest[$metric->value], 1)]) }}
                                        @endif
                                    </span>

                                    @if ($delta !== null)
                                        <x-admin::badge :variant="$metric->deltaVariant($delta)">
                                            {{ trans('masterdata::app.quality-dashboard.index.delta', ['value' => sprintf('%+.1f', $delta)]) }}
                                        </x-admin::badge>
                                    @endif
                                </span>
                            </span>
                        @endforeach

                        <span class="flex flex-wrap items-center gap-x-2 gap-y-1 mt-2 text-xs">
                            <span>{{ trans('masterdata::app.quality-dashboard.index.records', ['count' => $latest['record_count']]) }}</span>

                            <span aria-hidden="true">&middot;</span>

                            <span>{{ trans('masterdata::app.quality-dashboard.index.open-tasks', ['count' => $latest['open_tasks']]) }}</span>

                            <span aria-hidden="true">&middot;</span>

                            <span>{{ trans('masterdata::app.quality-dashboard.index.snapshot-date', ['date' => $latest['snapshot_date']]) }}</span>
                        </span>
                    @endif
                </x-admin::card>
            @endforeach
        </div>

        <div class="mt-6 p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
            <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                <p class="text-base text-gray-800 dark:text-white font-semibold">
                    {{ trans('masterdata::app.quality-dashboard.index.trend', ['type' => $selected?->name ?: $selected?->code]) }}
                </p>

                <div class="flex flex-wrap gap-2">
                    @foreach ($ranges as $option)
                        <a
                            href="{{ route('admin.masterdata.quality_dashboard.index', ['entity_type' => $selected?->id, 'days' => $option->value]) }}"
                            class="{{ $option === $range ? 'primary-button' : 'secondary-button' }}"
                            @if ($option === $range) aria-current="true" @endif
                        >
                            {{ $option->label() }}
                        </a>
                    @endforeach
                </div>
            </div>

            @if ($chart->isEmpty())
                <x-admin::list.empty-state :title="trans('masterdata::app.quality-dashboard.index.trend-empty')" />
            @else
                @php
                    [$from, $to] = $chart->span();
                @endphp

                <svg
                    viewBox="0 0 {{ TrendChart::WIDTH }} {{ TrendChart::HEIGHT }}"
                    class="w-full h-auto"
                    role="img"
                    aria-label="{{ trans('masterdata::app.quality-dashboard.index.chart-label', ['type' => $selected?->name ?: $selected?->code, 'from' => $from, 'to' => $to]) }}"
                >
                    @foreach (TrendChart::GRID as $line)
                        <line
                            x1="{{ TrendChart::PAD_LEFT }}"
                            x2="{{ TrendChart::WIDTH - TrendChart::PAD_RIGHT }}"
                            y1="{{ $chart->y($line) }}"
                            y2="{{ $chart->y($line) }}"
                            style="stroke: var(--chart-muted)"
                            stroke-opacity="0.4"
                            stroke-width="1"
                        />

                        <text
                            x="{{ TrendChart::PAD_LEFT - 8 }}"
                            y="{{ $chart->y($line) + 4 }}"
                            text-anchor="end"
                            font-size="11"
                            class="fill-current text-gray-500 dark:text-gray-400"
                        >{{ trans('masterdata::app.quality-dashboard.index.percent', ['value' => $line]) }}</text>
                    @endforeach

                    @foreach ($chart->series() as $series)
                        @foreach ($series['segments'] as $segment)
                            <polyline
                                points="{{ $segment }}"
                                fill="none"
                                style="stroke: var({{ $series['metric']->chartToken() }})"
                                stroke-width="2"
                                stroke-linejoin="round"
                                stroke-linecap="round"
                            />
                        @endforeach

                        @foreach ($series['points'] as $point)
                            <circle
                                cx="{{ $point['x'] }}"
                                cy="{{ $point['y'] }}"
                                r="3"
                                style="fill: var({{ $series['metric']->chartToken() }})"
                            >
                                <title>{{ $series['metric']->label() }} &middot; {{ $point['date'] }}: {{ trans('masterdata::app.quality-dashboard.index.percent', ['value' => number_format($point['value'], 1)]) }}</title>
                            </circle>
                        @endforeach
                    @endforeach
                </svg>

                <div class="flex flex-wrap items-center justify-between gap-3 mt-2 text-xs text-gray-600 dark:text-gray-300">
                    <span>{{ $from }}</span>

                    <span class="flex flex-wrap gap-x-4 gap-y-1">
                        @foreach ($chart->series() as $series)
                            <span class="flex items-center gap-1.5">
                                <span
                                    class="inline-block w-3 h-3 rounded-sm"
                                    style="background: var({{ $series['metric']->chartToken() }})"
                                    aria-hidden="true"
                                ></span>

                                {{ $series['metric']->label() }}

                                @if ($series['latest'] !== null)
                                    <span class="font-semibold">{{ trans('masterdata::app.quality-dashboard.index.percent', ['value' => number_format($series['latest'], 1)]) }}</span>
                                @endif
                            </span>
                        @endforeach
                    </span>

                    <span>{{ $to }}</span>
                </div>
            @endif
        </div>
    @endif

    <div class="mt-6 p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
        <p class="mb-4 text-base text-gray-800 dark:text-white font-semibold">
            @lang('masterdata::app.quality-dashboard.index.workload')
        </p>

        @if ($workload === [])
            <x-admin::list.empty-state :title="trans('masterdata::app.quality-dashboard.workload.empty')" />
        @else
            <div class="overflow-x-auto">
                <x-admin::table>
                    <x-admin::table.thead class="text-sm font-medium dark:bg-gray-800">
                        <x-admin::table.thead.tr>
                            <x-admin::table.th>@lang('masterdata::app.quality-dashboard.workload.steward')</x-admin::table.th>
                            <x-admin::table.th>@lang('masterdata::app.quality-dashboard.workload.change-requests')</x-admin::table.th>
                            <x-admin::table.th>@lang('masterdata::app.quality-dashboard.workload.match-candidates')</x-admin::table.th>

                            @foreach (WorkloadAgeBucket::cases() as $bucket)
                                <x-admin::table.th>{{ $bucket->label() }}</x-admin::table.th>
                            @endforeach
                        </x-admin::table.thead.tr>
                    </x-admin::table.thead>

                    <x-admin::table.tbody>
                        @foreach ($workload as $row)
                            <x-admin::table.tbody.tr>
                                <x-admin::table.td>
                                    {{ $row['steward_name'] ?? trans('masterdata::app.quality-dashboard.workload.unassigned') }}
                                </x-admin::table.td>

                                <x-admin::table.td>{{ $row['change_requests'] }}</x-admin::table.td>

                                <x-admin::table.td>{{ $row['match_candidates'] }}</x-admin::table.td>

                                @foreach (WorkloadAgeBucket::cases() as $bucket)
                                    <x-admin::table.td>
                                        <x-admin::badge :variant="$bucket === WorkloadAgeBucket::Older && $row['buckets'][$bucket->value] > 0 ? 'danger' : 'neutral'">
                                            {{ $row['buckets'][$bucket->value] }}
                                        </x-admin::badge>
                                    </x-admin::table.td>
                                @endforeach
                            </x-admin::table.tbody.tr>
                        @endforeach
                    </x-admin::table.tbody>
                </x-admin::table>
            </div>
        @endif
    </div>
</x-admin::layouts>
