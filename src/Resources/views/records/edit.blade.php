@php
    $openIssues = count(array_filter($issues, fn (array $issue): bool => $issue['open']));

    $activeTab = request()->has('history') ? 'history' : 'general';
@endphp

<x-admin::layouts.with-history
    :active-tab="$activeTab"
    entity-name="masterdata_record"
    :history-id="$record->id"
    :tab-items="[
        [
            'key'   => 'general',
            'url'   => route('admin.masterdata.records.edit', $record->uuid),
            'label' => 'masterdata::app.records.tabs.general',
        ],
    ]"
>
    <x-slot:title>
        {{ trans('masterdata::app.records.edit.title', ['code' => $record->code]) }}
    </x-slot>

    <x-slot:pageHeader>
        <x-admin::layouts.edit-page-header
            :title="trans('masterdata::app.records.edit.title', ['code' => $record->code])"
            :back-url="route('admin.masterdata.records.index', $record->entityType->code)"
            :back-label="trans('admin::app.account.edit.back-btn')"
            :save-label="$editable && $activeTab === 'general' ? trans('masterdata::app.records.edit.save-btn') : null"
            :form="$editable && $activeTab === 'general' ? 'record-edit-form' : null"
            :sticky="false"
        >
            @if ($locales !== [] || $completeness->score !== null || $dataOwner !== null || $pendingChange !== null)
                <x-slot:beforeActions>
                    @if ($locales !== [] && $activeTab === 'general')
                        <x-admin::locale-switcher
                            :locales="$locales"
                            :current="$locale"
                        />
                    @endif

                    @if ($pendingChange !== null)
                        @if ($canViewApprovals)
                            <a
                                href="{{ route('admin.masterdata.approvals.show', $pendingChange->id) }}"
                                title="{{ trans('masterdata::app.approvals.badge.open') }}"
                            >
                                <x-admin::badge variant="warning" class="!text-sm">
                                    {{ trans_choice('masterdata::app.approvals.badge.pending', $pendingCount, ['count' => $pendingCount]) }}
                                </x-admin::badge>
                            </a>
                        @else
                            <x-admin::badge variant="warning" class="!text-sm">
                                {{ trans_choice('masterdata::app.approvals.badge.pending', $pendingCount, ['count' => $pendingCount]) }}
                            </x-admin::badge>
                        @endif
                    @endif

                    @if ($dataOwner !== null)
                        <x-admin::badge variant="info" class="!text-sm">
                            {{ trans('masterdata::app.records.owner-badge', ['name' => $dataOwner]) }}
                        </x-admin::badge>
                    @endif

                    @if ($completeness->score !== null)
                        <x-admin::badge
                            :variant="\Webkul\MasterDataManagement\Quality\Completeness::variant($completeness->score)"
                            class="!text-sm"
                        >
                            {{ trans('masterdata::app.quality.completeness.badge', ['score' => $completeness->score]) }}
                        </x-admin::badge>
                    @endif
                </x-slot>
            @endif

            @if (($retirable || $reactivatable || $erasable || $mergeable) && $activeTab === 'general')
                <x-slot:actions>
                    @if ($mergeable)
                        <a
                            href="{{ route('admin.masterdata.records.merge.create', $record->uuid) }}"
                            class="secondary-button"
                        >
                            @lang('masterdata::app.records.merge.btn')
                        </a>
                    @endif

                    @if ($retirable)
                        <v-masterdata-confirm-action
                            url="{{ route('admin.masterdata.records.retire', $record->uuid) }}"
                            :payload="{ revision: {{ (int) $record->revision }} }"
                            label="@lang('masterdata::app.records.retire.btn')"
                            title="@lang('masterdata::app.records.retire.confirm-title')"
                            message="@lang('masterdata::app.records.retire.confirm-message')"
                        ></v-masterdata-confirm-action>
                    @else
                        @if ($erasable)
                            <v-masterdata-confirm-action
                                url="{{ route('admin.masterdata.records.erase', $record->uuid) }}"
                                :payload="{ revision: {{ (int) $record->revision }} }"
                                label="@lang('masterdata::app.records.erase.btn')"
                                title="@lang('masterdata::app.records.erase.confirm-title')"
                                message="@lang('masterdata::app.records.erase.confirm-message')"
                            ></v-masterdata-confirm-action>
                        @endif

                        @if ($reactivatable)
                            <v-masterdata-confirm-action
                                url="{{ route('admin.masterdata.records.reactivate', $record->uuid) }}"
                                :payload="{ revision: {{ (int) $record->revision }} }"
                                label="@lang('masterdata::app.records.reactivate.btn')"
                                title="@lang('masterdata::app.records.reactivate.confirm-title')"
                                message="@lang('masterdata::app.records.reactivate.confirm-message')"
                                button-class="primary-button"
                            ></v-masterdata-confirm-action>
                        @endif
                    @endif
                </x-slot>
            @endif
        </x-admin::layouts.edit-page-header>
    </x-slot>

    @if ($editable)
        <x-admin::form
            id="record-edit-form"
            :action="route('admin.masterdata.records.update', array_filter(['uuid' => $record->uuid, 'locale' => $locales !== [] ? $locale : null]))"
            method="PUT"
            ajax="true"
        >
            @include('masterdata::records._form', ['entityType' => $record->entityType])
        </x-admin::form>
    @elseif (! in_array($record->status, [\Webkul\MasterDataManagement\Enums\RecordStatus::Draft, \Webkul\MasterDataManagement\Enums\RecordStatus::Active], true))
        <div class="mt-3.5 p-4 bg-white dark:bg-cherry-900 rounded box-shadow text-sm text-gray-600 dark:text-gray-300">
            {{ trans('masterdata::app.records.edit.read-only', ['status' => trans('masterdata::app.records.statuses.'.$record->status->value)]) }}
        </div>
    @endif

    @if ($hierarchy !== null)
        @include('masterdata::records._hierarchy', ['record' => $record, 'hierarchy' => $hierarchy, 'childTypes' => $childTypes])
    @endif

    <div class="mt-3.5 bg-white dark:bg-cherry-900 rounded box-shadow">
        <x-admin::tabs position="left">
            @unless ($editable)
                <x-admin::tabs.item
                    :title="trans('masterdata::app.records.tabs.general')"
                    :is-selected="true"
                >
                    <dl class="grid grid-cols-[1fr_2fr] gap-x-6 gap-y-3 text-sm">
                        <dt class="font-semibold text-gray-800 dark:text-white">@lang('masterdata::app.records.fields.type')</dt>
                        <dd class="text-gray-600 dark:text-gray-300">{{ $record->entityType->name ?: $record->entityType->code }}</dd>

                        <dt class="font-semibold text-gray-800 dark:text-white">@lang('masterdata::app.records.fields.code')</dt>
                        <dd class="text-gray-600 dark:text-gray-300">{{ $record->code }}</dd>

                        <dt class="font-semibold text-gray-800 dark:text-white">@lang('masterdata::app.records.fields.label')</dt>
                        <dd class="text-gray-600 dark:text-gray-300">{{ $record->label }}</dd>

                        <dt class="font-semibold text-gray-800 dark:text-white">@lang('masterdata::app.records.fields.status')</dt>
                        <dd class="text-gray-600 dark:text-gray-300">@lang('masterdata::app.records.statuses.'.$record->status->value)</dd>

                        <dt class="font-semibold text-gray-800 dark:text-white">@lang('masterdata::app.records.fields.updated-at')</dt>
                        <dd class="text-gray-600 dark:text-gray-300">{{ $record->updated_at }}</dd>
                    </dl>
                </x-admin::tabs.item>

                <x-admin::tabs.item :title="trans('masterdata::app.records.tabs.values')">
                    @if ($valueRows === [])
                        <x-admin::list.empty-state :title="trans('masterdata::app.records.empty-values')" />
                    @else
                        <x-admin::table>
                            <x-admin::table.thead class="text-sm font-medium dark:bg-gray-800">
                                <x-admin::table.thead.tr>
                                    <x-admin::table.th>@lang('masterdata::app.records.values.key')</x-admin::table.th>
                                    <x-admin::table.th>@lang('masterdata::app.records.values.locale')</x-admin::table.th>
                                    <x-admin::table.th>@lang('masterdata::app.records.values.value')</x-admin::table.th>
                                </x-admin::table.thead.tr>
                            </x-admin::table.thead>

                            <x-admin::table.tbody>
                                @foreach ($valueRows as $row)
                                    <x-admin::table.tbody.tr>
                                        <x-admin::table.td>{{ $row['key'] }}</x-admin::table.td>
                                        <x-admin::table.td>{{ $row['locale'] }}</x-admin::table.td>
                                        <x-admin::table.td class="!whitespace-normal">{{ $row['value'] }}</x-admin::table.td>
                                    </x-admin::table.tbody.tr>
                                @endforeach
                            </x-admin::table.tbody>
                        </x-admin::table>
                    @endif
                </x-admin::tabs.item>
            @endunless

            <x-admin::tabs.item
                :title="trans('masterdata::app.records.tabs.cross-references')"
                :is-selected="$editable"
            >
                @include('masterdata::records._xrefs')
            </x-admin::tabs.item>

            <x-admin::tabs.item :title="trans('masterdata::app.records.tabs.relations')">
                @include('masterdata::records._relations')
            </x-admin::tabs.item>

            <x-admin::tabs.item :title="$merges->isNotEmpty() ? trans('masterdata::app.records.tabs.merges-count', ['count' => $merges->count()]) : trans('masterdata::app.records.tabs.merges')">
                @include('masterdata::records._merges')
            </x-admin::tabs.item>

            <x-admin::tabs.item :title="trans('masterdata::app.hierarchies.record.tab')">
                @include('masterdata::records._hierarchies')
            </x-admin::tabs.item>

            <x-admin::tabs.item :title="trans('masterdata::app.lineage.title')">
                @include('masterdata::records._lineage', ['lineage' => $lineage])
            </x-admin::tabs.item>

            <x-admin::tabs.item :title="$openIssues > 0 ? trans('masterdata::app.records.tabs.quality-count', ['count' => $openIssues]) : trans('masterdata::app.records.tabs.quality')">
                @include('masterdata::records._quality')
            </x-admin::tabs.item>

            <x-admin::tabs.item :title="$pendingCount > 0 ? trans('masterdata::app.records.tabs.approvals-count', ['count' => $pendingCount]) : trans('masterdata::app.records.tabs.approvals')">
                @include('masterdata::records._change-requests')
            </x-admin::tabs.item>
        </x-admin::tabs>
    </div>

    @include('masterdata::partials.confirm-action')
</x-admin::layouts.with-history>
