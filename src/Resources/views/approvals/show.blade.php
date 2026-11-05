<x-admin::layouts>
    <x-slot:title>
        {{ trans('masterdata::app.approvals.show.title', ['id' => $changeRequest->id]) }}
    </x-slot>

    <x-admin::layouts.edit-page-header
        :title="trans('masterdata::app.approvals.show.title', ['id' => $changeRequest->id])"
        :back-url="route('admin.masterdata.approvals.index')"
        :back-label="trans('admin::app.account.edit.back-btn')"
        :sticky="false"
    >
        @if ($canDecide)
            <x-slot:actions>
                @foreach (['reject', 'approve'] as $decision)
                    <x-admin::form
                        :action="route('admin.masterdata.approvals.'.$decision, $changeRequest->id)"
                        ajax="true"
                        :track-dirty="false"
                    >
                        <x-admin::modal>
                            <x-slot:toggle>
                                <button
                                    type="button"
                                    class="{{ $decision === 'approve' ? 'primary-button' : 'secondary-button' }}"
                                >
                                    @lang('masterdata::app.approvals.show.'.$decision)
                                </button>
                            </x-slot>

                            <x-slot:header>
                                <p class="text-lg text-gray-800 dark:text-white font-bold">
                                    @lang('masterdata::app.approvals.show.'.$decision.'-title')
                                </p>
                            </x-slot>

                            <x-slot:content>
                                <p class="mb-4 text-sm text-gray-600 dark:text-gray-300">
                                    @lang('masterdata::app.approvals.show.'.$decision.'-info')
                                </p>

                                <x-admin::form.control-group class="!mb-0">
                                    <x-admin::form.control-group.label :class="$decision === 'reject' ? 'required' : ''">
                                        @lang('masterdata::app.approvals.fields.comment')
                                    </x-admin::form.control-group.label>

                                    <x-admin::form.control-group.control
                                        type="textarea"
                                        name="comment"
                                        :rules="$decision === 'reject' ? 'required|max:2000' : 'max:2000'"
                                        :label="trans('masterdata::app.approvals.fields.comment')"
                                        :placeholder="trans('masterdata::app.approvals.fields.comment-placeholder')"
                                    />

                                    <x-admin::form.control-group.help>
                                        @lang('masterdata::app.help.approvals.comment')
                                    </x-admin::form.control-group.help>

                                    <x-admin::form.control-group.error control-name="comment" />
                                </x-admin::form.control-group>
                            </x-slot>

                            <x-slot:footer>
                                <button
                                    type="submit"
                                    class="primary-button"
                                >
                                    @lang('masterdata::app.approvals.show.'.$decision.'-btn')
                                </button>
                            </x-slot>
                        </x-admin::modal>
                    </x-admin::form>
                @endforeach
            </x-slot>
        @endif
    </x-admin::layouts.edit-page-header>

    @if ($conflict)
        <div class="mt-3.5 p-4 rounded box-shadow bg-warning/10 text-warning text-sm font-semibold">
            {{ trans('masterdata::app.approvals.show.conflict', [
                'base'    => (int) $changeRequest->base_revision,
                'current' => $record?->revision ?? '-',
            ]) }}
        </div>
    @endif

    <div class="flex gap-2.5 mt-3.5 max-xl:flex-wrap">
        <div class="flex flex-col gap-2 flex-1 max-xl:flex-auto">
            <div class="p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
                <p class="mb-4 text-base text-gray-800 dark:text-white font-semibold">
                    @lang('masterdata::app.approvals.show.changes')
                </p>

                @if ($rows === [])
                    <x-admin::list.empty-state :title="trans('masterdata::app.approvals.show.no-changes')" />
                @else
                    <x-admin::table>
                        <x-admin::table.thead class="text-sm font-medium dark:bg-gray-800">
                            <x-admin::table.thead.tr>
                                <x-admin::table.th>@lang('masterdata::app.approvals.diff.field')</x-admin::table.th>
                                <x-admin::table.th>@lang('masterdata::app.approvals.diff.before')</x-admin::table.th>
                                <x-admin::table.th>@lang('masterdata::app.approvals.diff.after')</x-admin::table.th>
                            </x-admin::table.thead.tr>
                        </x-admin::table.thead>

                        <x-admin::table.tbody>
                            @foreach ($rows as $row)
                                <x-admin::table.tbody.tr>
                                    <x-admin::table.td class="font-semibold">{{ $row['label'] }}</x-admin::table.td>

                                    <x-admin::table.td class="!whitespace-normal">
                                        @if ($row['before'] !== '')
                                            <span class="text-danger">{{ $row['before'] }}</span>
                                        @endif
                                    </x-admin::table.td>

                                    <x-admin::table.td class="!whitespace-normal">
                                        <span class="text-success">{{ $row['after'] }}</span>
                                    </x-admin::table.td>
                                </x-admin::table.tbody.tr>
                            @endforeach
                        </x-admin::table.tbody>
                    </x-admin::table>
                @endif
            </div>
        </div>

        <div class="flex flex-col gap-2 w-[360px] max-w-full max-sm:w-full">
            <div class="p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
                <p class="mb-4 text-base text-gray-800 dark:text-white font-semibold">
                    @lang('masterdata::app.approvals.show.details')
                </p>

                <dl class="grid grid-cols-[1fr_2fr] gap-x-4 gap-y-3 text-sm">
                    <dt class="font-semibold text-gray-800 dark:text-white">@lang('masterdata::app.approvals.datagrid.status')</dt>
                    <dd>
                        <x-admin::badge :variant="$changeRequest->status->badgeVariant()">
                            @lang('masterdata::app.approvals.statuses.'.$changeRequest->status->value)
                        </x-admin::badge>
                    </dd>

                    <dt class="font-semibold text-gray-800 dark:text-white">@lang('masterdata::app.approvals.datagrid.operation')</dt>
                    <dd class="text-gray-600 dark:text-gray-300">@lang('masterdata::app.approvals.operations.'.$changeRequest->operation->value)</dd>

                    <dt class="font-semibold text-gray-800 dark:text-white">@lang('masterdata::app.approvals.datagrid.entity-type')</dt>
                    <dd class="text-gray-600 dark:text-gray-300">{{ $typeName }}</dd>

                    <dt class="font-semibold text-gray-800 dark:text-white">@lang('masterdata::app.approvals.datagrid.record')</dt>
                    <dd class="text-gray-600 dark:text-gray-300 break-all">
                        @if ($record)
                            <a
                                href="{{ route('admin.masterdata.records.edit', $record->uuid) }}"
                                class="text-primary-600 hover:text-primary-700 dark:text-primary-400"
                            >
                                {{ trans('masterdata::app.records.code-label', ['code' => $record->code, 'label' => $record->label]) }}
                            </a>
                        @else
                            @lang('masterdata::app.approvals.new-record')
                        @endif
                    </dd>

                    <dt class="font-semibold text-gray-800 dark:text-white">@lang('masterdata::app.approvals.fields.data-owner')</dt>
                    <dd class="text-gray-600 dark:text-gray-300">{{ $changeRequest->entityType->dataOwner?->name ?? trans('masterdata::app.entity-types.form.no-owner') }}</dd>

                    <dt class="font-semibold text-gray-800 dark:text-white">@lang('masterdata::app.approvals.datagrid.requested-by')</dt>
                    <dd class="text-gray-600 dark:text-gray-300">{{ $changeRequest->requester?->name ?? trans('masterdata::app.lineage.system') }}</dd>

                    <dt class="font-semibold text-gray-800 dark:text-white">@lang('masterdata::app.approvals.datagrid.requested-at')</dt>
                    <dd class="text-gray-600 dark:text-gray-300">{{ \Webkul\MasterDataManagement\Support\AdminDate::format($changeRequest->created_at) }}</dd>

                    @if ($changeRequest->base_revision !== null)
                        <dt class="font-semibold text-gray-800 dark:text-white">@lang('masterdata::app.approvals.fields.base-revision')</dt>
                        <dd class="text-gray-600 dark:text-gray-300">{{ $changeRequest->base_revision }}</dd>
                    @endif

                    @if ($changeRequest->decided_at !== null)
                        <dt class="font-semibold text-gray-800 dark:text-white">@lang('masterdata::app.approvals.datagrid.decided-by')</dt>
                        <dd class="text-gray-600 dark:text-gray-300">{{ $changeRequest->decider?->name ?? trans('masterdata::app.lineage.system') }}</dd>

                        <dt class="font-semibold text-gray-800 dark:text-white">@lang('masterdata::app.approvals.fields.decided-at')</dt>
                        <dd class="text-gray-600 dark:text-gray-300">{{ \Webkul\MasterDataManagement\Support\AdminDate::format($changeRequest->decided_at) }}</dd>
                    @endif

                    @if ($changeRequest->comment)
                        <dt class="font-semibold text-gray-800 dark:text-white">@lang('masterdata::app.approvals.fields.comment')</dt>
                        <dd class="text-gray-600 dark:text-gray-300 whitespace-pre-line">{{ $changeRequest->comment }}</dd>
                    @endif
                </dl>

                @if ($changeRequest->status === \Webkul\MasterDataManagement\Enums\ChangeRequestStatus::Stale)
                    <p class="mt-4 text-sm text-gray-600 dark:text-gray-300">
                        @lang('masterdata::app.approvals.show.stale-info')
                    </p>
                @elseif ($changeRequest->isPending() && ! $canDecide)
                    <p class="mt-4 text-sm text-gray-600 dark:text-gray-300">
                        @lang('masterdata::app.approvals.show.waiting')
                    </p>
                @endif
            </div>
        </div>
    </div>
</x-admin::layouts>
