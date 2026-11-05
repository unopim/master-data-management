@if ($changeRequests->isEmpty())
    <x-admin::list.empty-state :title="trans('masterdata::app.approvals.record.empty')" />
@else
    <x-admin::table>
        <x-admin::table.thead class="text-sm font-medium dark:bg-gray-800">
            <x-admin::table.thead.tr>
                <x-admin::table.th>@lang('masterdata::app.approvals.datagrid.id')</x-admin::table.th>
                <x-admin::table.th>@lang('masterdata::app.approvals.datagrid.operation')</x-admin::table.th>
                <x-admin::table.th>@lang('masterdata::app.approvals.datagrid.status')</x-admin::table.th>
                <x-admin::table.th>@lang('masterdata::app.approvals.datagrid.requested-by')</x-admin::table.th>
                <x-admin::table.th>@lang('masterdata::app.approvals.datagrid.requested-at')</x-admin::table.th>
                <x-admin::table.th>@lang('masterdata::app.approvals.datagrid.decided-by')</x-admin::table.th>
                <x-admin::table.th>@lang('masterdata::app.approvals.fields.comment')</x-admin::table.th>
            </x-admin::table.thead.tr>
        </x-admin::table.thead>

        <x-admin::table.tbody>
            @foreach ($changeRequests as $changeRequest)
                <x-admin::table.tbody.tr>
                    <x-admin::table.td>
                        @if ($canViewApprovals)
                            <a
                                href="{{ route('admin.masterdata.approvals.show', $changeRequest->id) }}"
                                class="text-primary-600 hover:text-primary-700 dark:text-primary-400"
                            >
                                #{{ $changeRequest->id }}
                            </a>
                        @else
                            #{{ $changeRequest->id }}
                        @endif
                    </x-admin::table.td>

                    <x-admin::table.td>@lang('masterdata::app.approvals.operations.'.$changeRequest->operation->value)</x-admin::table.td>

                    <x-admin::table.td>
                        <x-admin::badge :variant="$changeRequest->status->badgeVariant()">
                            @lang('masterdata::app.approvals.statuses.'.$changeRequest->status->value)
                        </x-admin::badge>
                    </x-admin::table.td>

                    <x-admin::table.td>{{ $changeRequest->requester?->name ?? trans('masterdata::app.lineage.system') }}</x-admin::table.td>

                    <x-admin::table.td>{{ \Webkul\MasterDataManagement\Support\AdminDate::format($changeRequest->created_at) }}</x-admin::table.td>

                    <x-admin::table.td>
                        @if ($changeRequest->decided_at !== null)
                            {{ $changeRequest->decider?->name ?? trans('masterdata::app.lineage.system') }}
                            <span class="block text-xs text-gray-500 dark:text-gray-400">
                                {{ \Webkul\MasterDataManagement\Support\AdminDate::format($changeRequest->decided_at) }}
                            </span>
                        @endif
                    </x-admin::table.td>

                    <x-admin::table.td class="!whitespace-normal">{{ $changeRequest->comment }}</x-admin::table.td>
                </x-admin::table.tbody.tr>
            @endforeach
        </x-admin::table.tbody>
    </x-admin::table>
@endif
