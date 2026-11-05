@if ($relations['types']['outbound'] === [] && $relations['types']['inbound'] === [])
    <x-admin::list.empty-state
        :title="trans('masterdata::app.records.relations.none-configured', ['type' => $record->entityType->name ?: $record->entityType->code])"
        :description="trans('masterdata::app.records.relations.none-configured-info')"
    />
@else
    @foreach (['outbound' => $relations['outbound'], 'inbound' => $relations['inbound']] as $direction => $items)
        <div @class(['mt-6' => ! $loop->first])>
            <p class="mb-2 text-sm text-gray-800 dark:text-white font-semibold">
                @lang('masterdata::app.records.relations.'.$direction)
            </p>

            @if ($items === [])
                <x-admin::list.empty-state
                    :title="trans('masterdata::app.records.relations.empty-'.$direction)"
                    :description="$relations['types'][$direction] === []
                        ? trans('masterdata::app.records.relations.no-types-'.$direction)
                        : trans('masterdata::app.records.relations.possible', ['types' => implode(', ', $relations['types'][$direction])])"
                />
            @else
                <x-admin::table>
                    <x-admin::table.thead class="text-sm font-medium dark:bg-gray-800">
                        <x-admin::table.thead.tr>
                            <x-admin::table.th>@lang('masterdata::app.records.relations.type')</x-admin::table.th>
                            <x-admin::table.th>@lang('masterdata::app.records.fields.code')</x-admin::table.th>
                            <x-admin::table.th>@lang('masterdata::app.records.fields.label')</x-admin::table.th>
                        </x-admin::table.thead.tr>
                    </x-admin::table.thead>

                    <x-admin::table.tbody>
                        @foreach ($items as $item)
                            <x-admin::table.tbody.tr>
                                <x-admin::table.td>{{ $item['type'] }}</x-admin::table.td>

                                <x-admin::table.td>
                                    <a
                                        href="{{ route('admin.masterdata.records.edit', $item['uuid']) }}"
                                        class="text-primary-600 hover:text-primary-700 dark:text-primary-400"
                                    >
                                        {{ $item['code'] }}
                                    </a>
                                </x-admin::table.td>

                                <x-admin::table.td>{{ $item['label'] }}</x-admin::table.td>
                            </x-admin::table.tbody.tr>
                        @endforeach
                    </x-admin::table.tbody>
                </x-admin::table>
            @endif
        </div>
    @endforeach
@endif
