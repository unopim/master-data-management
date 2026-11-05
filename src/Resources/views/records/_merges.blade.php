@if ($merges->isEmpty())
    <x-admin::list.empty-state
        :title="trans('masterdata::app.records.merge.empty')"
        :description="trans('masterdata::app.records.merge.empty-info')"
    />
@else
    <x-admin::table>
        <x-admin::table.thead class="text-sm font-medium dark:bg-gray-800">
            <x-admin::table.thead.tr>
                <x-admin::table.th>@lang('masterdata::app.records.fields.code')</x-admin::table.th>
                <x-admin::table.th>@lang('masterdata::app.records.fields.label')</x-admin::table.th>
                <x-admin::table.th>@lang('masterdata::app.records.merge.merged-at')</x-admin::table.th>

                @if ($canUnmerge)
                    <x-admin::table.th>@lang('masterdata::app.records.xrefs.actions')</x-admin::table.th>
                @endif
            </x-admin::table.thead.tr>
        </x-admin::table.thead>

        <x-admin::table.tbody>
            @foreach ($merges as $merge)
                <x-admin::table.tbody.tr>
                    <x-admin::table.td>{{ $merge->merged?->code }}</x-admin::table.td>
                    <x-admin::table.td>{{ $merge->merged?->label }}</x-admin::table.td>
                    <x-admin::table.td>{{ $merge->merged_at }}</x-admin::table.td>

                    @if ($canUnmerge)
                        <x-admin::table.td>
                            <v-masterdata-confirm-action
                                url="{{ route('admin.masterdata.records.merge.destroy', [$record->uuid, $merge->id]) }}"
                                method="delete"
                                label="@lang('masterdata::app.records.merge.unmerge')"
                                title="@lang('masterdata::app.records.merge.unmerge-confirm-title')"
                                message="@lang('masterdata::app.records.merge.unmerge-confirm-message')"
                            ></v-masterdata-confirm-action>
                        </x-admin::table.td>
                    @endif
                </x-admin::table.tbody.tr>
            @endforeach
        </x-admin::table.tbody>
    </x-admin::table>
@endif
