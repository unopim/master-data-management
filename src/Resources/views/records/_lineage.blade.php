@php
    $formatDate = fn (?\Carbon\CarbonInterface $date): string => \Webkul\MasterDataManagement\Support\AdminDate::format($date, trans('masterdata::app.lineage.never'));

    $schema = $lineage['schema'];

    $canEditExports = bouncer()->hasPermission('data_transfer.export.edit');

    $consumerVariants = [
        'never_run'  => 'neutral',
        'pending'    => 'warning',
        'included'   => 'success',
        'up_to_date' => 'success',
    ];
@endphp

<div class="grid gap-6">
    <section>
        <p class="mb-2 text-base text-gray-800 dark:text-white font-semibold">
            @lang('masterdata::app.lineage.path.title')
        </p>

        @include('masterdata::records._trail', [
            'items'     => $lineage['path'],
            'label'     => trans('masterdata::app.lineage.path.title'),
            'rootLabel' => trans('masterdata::app.lineage.path.root'),
            'withTypes' => true,
        ])
    </section>

    <div class="grid gap-6 md:grid-cols-2">
        <section>
            <p class="mb-2 text-base text-gray-800 dark:text-white font-semibold">
                @lang('masterdata::app.lineage.authorship.title')
            </p>

            <dl class="grid grid-cols-2 gap-x-6 gap-y-3 text-sm">
                <dt class="font-semibold text-gray-800 dark:text-white">@lang('masterdata::app.lineage.authorship.created-at')</dt>
                <dd class="text-gray-600 dark:text-gray-300">{{ $formatDate($lineage['authorship']['created_at']) }}</dd>

                <dt class="font-semibold text-gray-800 dark:text-white">@lang('masterdata::app.lineage.authorship.created-by')</dt>
                <dd class="text-gray-600 dark:text-gray-300">{{ $lineage['authorship']['created_by'] ?? trans('masterdata::app.lineage.system') }}</dd>

                <dt class="font-semibold text-gray-800 dark:text-white">@lang('masterdata::app.lineage.authorship.updated-at')</dt>
                <dd class="text-gray-600 dark:text-gray-300">{{ $formatDate($lineage['authorship']['updated_at']) }}</dd>

                <dt class="font-semibold text-gray-800 dark:text-white">@lang('masterdata::app.lineage.authorship.updated-by')</dt>
                <dd class="text-gray-600 dark:text-gray-300">{{ $lineage['authorship']['updated_by'] ?? trans('masterdata::app.lineage.system') }}</dd>
            </dl>
        </section>

        <section>
            <p class="mb-2 text-base text-gray-800 dark:text-white font-semibold">
                @lang('masterdata::app.lineage.schema.title')
            </p>

            <dl class="grid grid-cols-2 gap-x-6 gap-y-3 text-sm">
                <dt class="font-semibold text-gray-800 dark:text-white">@lang('masterdata::app.lineage.schema.validated')</dt>
                <dd class="flex flex-wrap items-center gap-2 text-gray-600 dark:text-gray-300">
                    @if ($schema['version'] === null)
                        @lang('masterdata::app.lineage.schema.none')
                    @else
                        {{ trans('masterdata::app.lineage.schema.version', ['version' => $schema['version']]) }}

                        <x-admin::badge :variant="$schema['is_current'] ? 'success' : 'warning'">
                            @lang($schema['is_current'] ? 'masterdata::app.lineage.schema.current' : 'masterdata::app.lineage.schema.outdated')
                        </x-admin::badge>
                    @endif
                </dd>

                <dt class="font-semibold text-gray-800 dark:text-white">@lang('masterdata::app.lineage.schema.current-version')</dt>
                <dd class="text-gray-600 dark:text-gray-300">
                    @if ($schema['current_version'] === null)
                        @lang('masterdata::app.lineage.schema.unpublished')
                    @else
                        {{ trans('masterdata::app.lineage.schema.version', ['version' => $schema['current_version']]) }}
                    @endif
                </dd>

                @if ($schema['version'] !== null)
                    <dt class="font-semibold text-gray-800 dark:text-white">@lang('masterdata::app.lineage.schema.published-at')</dt>
                    <dd class="text-gray-600 dark:text-gray-300">{{ $formatDate($schema['published_at']) }}</dd>

                    <dt class="font-semibold text-gray-800 dark:text-white">@lang('masterdata::app.lineage.schema.published-by')</dt>
                    <dd class="text-gray-600 dark:text-gray-300">{{ $schema['published_by'] ?? trans('masterdata::app.lineage.system') }}</dd>
                @endif
            </dl>
        </section>
    </div>

    <section>
        <p class="mb-2 text-base text-gray-800 dark:text-white font-semibold">
            @lang('masterdata::app.lineage.sources.title')
        </p>

        @if ($lineage['sources'] === [])
            <x-admin::list.empty-state :title="trans('masterdata::app.lineage.sources.empty')" />
        @else
            <x-admin::table>
                <x-admin::table.thead class="text-sm font-medium dark:bg-gray-800">
                    <x-admin::table.thead.tr>
                        <x-admin::table.th>@lang('masterdata::app.lineage.sources.system')</x-admin::table.th>
                        <x-admin::table.th>@lang('masterdata::app.lineage.sources.external-id')</x-admin::table.th>
                        <x-admin::table.th>@lang('masterdata::app.lineage.sources.status')</x-admin::table.th>
                        <x-admin::table.th>@lang('masterdata::app.lineage.sources.attached-at')</x-admin::table.th>
                        <x-admin::table.th>@lang('masterdata::app.lineage.sources.last-seen-at')</x-admin::table.th>
                    </x-admin::table.thead.tr>
                </x-admin::table.thead>

                <x-admin::table.tbody>
                    @foreach ($lineage['sources'] as $source)
                        @foreach ($source['ids'] as $id)
                            <x-admin::table.tbody.tr>
                                <x-admin::table.td>
                                    @if ($loop->first)
                                        <span class="flex flex-wrap items-center gap-2">
                                            <span class="font-semibold text-gray-800 dark:text-white">{{ $source['name'] }}</span>

                                            @if (trans()->has('masterdata::app.source-systems.types.'.$source['type']) && strcasecmp(trans('masterdata::app.source-systems.types.'.$source['type']), $source['name']) !== 0)
                                                <x-admin::badge variant="info">@lang('masterdata::app.source-systems.types.'.$source['type'])</x-admin::badge>
                                            @endif
                                        </span>
                                    @endif
                                </x-admin::table.td>

                                <x-admin::table.td>{{ $id['external_id'] }}</x-admin::table.td>

                                <x-admin::table.td>
                                    <x-admin::badge :variant="$id['status'] === 'active' ? 'success' : 'neutral'">
                                        @lang('masterdata::app.records.xref-statuses.'.$id['status'])
                                    </x-admin::badge>
                                </x-admin::table.td>

                                <x-admin::table.td>{{ $formatDate($id['attached_at']) }}</x-admin::table.td>

                                <x-admin::table.td>{{ $formatDate($id['last_seen_at']) }}</x-admin::table.td>
                            </x-admin::table.tbody.tr>
                        @endforeach
                    @endforeach
                </x-admin::table.tbody>
            </x-admin::table>
        @endif
    </section>

    <section>
        <p class="mb-2 text-base text-gray-800 dark:text-white font-semibold">
            @lang('masterdata::app.lineage.consumers.title')
        </p>

        @if ($lineage['consumers'] === [])
            <x-admin::list.empty-state :title="trans('masterdata::app.lineage.consumers.empty')" />
        @else
            <x-admin::table>
                <x-admin::table.thead class="text-sm font-medium dark:bg-gray-800">
                    <x-admin::table.thead.tr>
                        <x-admin::table.th>@lang('masterdata::app.lineage.consumers.job')</x-admin::table.th>
                        <x-admin::table.th>@lang('masterdata::app.lineage.consumers.format')</x-admin::table.th>
                        <x-admin::table.th>@lang('masterdata::app.lineage.consumers.mode')</x-admin::table.th>
                        <x-admin::table.th>@lang('masterdata::app.lineage.consumers.last-run-at')</x-admin::table.th>
                        <x-admin::table.th>@lang('masterdata::app.lineage.consumers.record-state')</x-admin::table.th>
                    </x-admin::table.thead.tr>
                </x-admin::table.thead>

                <x-admin::table.tbody>
                    @foreach ($lineage['consumers'] as $consumer)
                        <x-admin::table.tbody.tr>
                            <x-admin::table.td>
                                @if ($canEditExports)
                                    <a
                                        href="{{ route('admin.settings.data_transfer.exports.edit', $consumer['id']) }}"
                                        class="text-primary-600 hover:text-primary-700 dark:text-primary-400"
                                    >
                                        {{ $consumer['code'] }}
                                    </a>
                                @else
                                    {{ $consumer['code'] }}
                                @endif
                            </x-admin::table.td>

                            <x-admin::table.td>@lang('masterdata::app.exports.records.formats.'.$consumer['format'])</x-admin::table.td>

                            <x-admin::table.td>@lang($consumer['delta'] ? 'masterdata::app.lineage.consumers.delta' : 'masterdata::app.lineage.consumers.full')</x-admin::table.td>

                            <x-admin::table.td>{{ $consumer['last_run_at'] === null ? trans('masterdata::app.lineage.consumers.no-run') : $formatDate($consumer['last_run_at']) }}</x-admin::table.td>

                            <x-admin::table.td>
                                <x-admin::badge :variant="$consumerVariants[$consumer['state']] ?? 'neutral'">
                                    @lang('masterdata::app.lineage.consumers.states.'.$consumer['state'])
                                </x-admin::badge>
                            </x-admin::table.td>
                        </x-admin::table.tbody.tr>
                    @endforeach
                </x-admin::table.tbody>
            </x-admin::table>
        @endif
    </section>
</div>
