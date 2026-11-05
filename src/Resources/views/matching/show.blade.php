<x-admin::layouts>
    <x-slot:title>
        {{ trans('masterdata::app.matching.show.title', ['id' => $candidate->external_id]) }}
    </x-slot>

    <x-admin::layouts.edit-page-header
        :title="trans('masterdata::app.matching.show.title', ['id' => $candidate->external_id])"
        :back-url="route('admin.masterdata.matching.index')"
        :back-label="trans('admin::app.account.edit.back-btn')"
        :sticky="false"
    >
        @if ($canCreate || $canReject)
            <x-slot:actions>
                @if ($canReject)
                    <v-masterdata-confirm-action
                        url="{{ route('admin.masterdata.matching.reject', $candidate->id) }}"
                        label="@lang('masterdata::app.matching.show.reject')"
                        title="@lang('masterdata::app.matching.show.reject-title')"
                        message="@lang('masterdata::app.matching.show.reject-message')"
                    ></v-masterdata-confirm-action>
                @endif

                @if ($canCreate)
                    <x-admin::form
                        :action="route('admin.masterdata.matching.create_record', $candidate->id)"
                        ajax="true"
                        :track-dirty="false"
                    >
                        <x-admin::modal>
                            <x-slot:toggle>
                                <button
                                    type="button"
                                    class="primary-button"
                                >
                                    @lang('masterdata::app.matching.show.create')
                                </button>
                            </x-slot>

                            <x-slot:header>
                                <p class="text-lg text-gray-800 dark:text-white font-bold">
                                    @lang('masterdata::app.matching.show.create-title')
                                </p>
                            </x-slot>

                            <x-slot:content>
                                <p class="mb-4 text-sm text-gray-600 dark:text-gray-300">
                                    @lang('masterdata::app.matching.show.create-info')
                                </p>

                                <x-admin::form.control-group>
                                    <x-admin::form.control-group.label :class="$codeMode['generated'] ? '' : 'required'">
                                        @lang('masterdata::app.matching.fields.code')
                                    </x-admin::form.control-group.label>

                                    @if ($codeMode['generated'])
                                        <x-admin::form.control-group.control
                                            type="text"
                                            name="code_preview"
                                            :value="$codeMode['hint']"
                                            :label="trans('masterdata::app.matching.fields.code')"
                                            disabled="disabled"
                                        />
                                    @else
                                        <x-admin::form.control-group.control
                                            type="text"
                                            name="code"
                                            rules="required|max:255"
                                            :value="$candidate->payload['code'] ?? $candidate->external_id"
                                            :label="trans('masterdata::app.matching.fields.code')"
                                            :placeholder="trans('masterdata::app.matching.fields.code')"
                                        />
                                    @endif

                                    <x-admin::form.control-group.help>
                                        @lang('masterdata::app.help.matching.create-code')
                                    </x-admin::form.control-group.help>

                                    <x-admin::form.control-group.error control-name="code" />
                                </x-admin::form.control-group>

                                <x-admin::form.control-group>
                                    <x-admin::form.control-group.label>
                                        @lang('masterdata::app.matching.fields.label')
                                    </x-admin::form.control-group.label>

                                    <x-admin::form.control-group.control
                                        type="text"
                                        name="label"
                                        rules="max:255"
                                        :value="$candidate->label"
                                        :label="trans('masterdata::app.matching.fields.label')"
                                        :placeholder="trans('masterdata::app.matching.fields.label')"
                                    />

                                    <x-admin::form.control-group.help>
                                        @lang('masterdata::app.help.matching.create-label')
                                    </x-admin::form.control-group.help>

                                    <x-admin::form.control-group.error control-name="label" />
                                </x-admin::form.control-group>

                                <x-admin::form.control-group.error control-name="values" />
                                <x-admin::form.control-group.error control-name="status" />
                            </x-slot>

                            <x-slot:footer>
                                <button
                                    type="submit"
                                    class="primary-button"
                                >
                                    @lang('masterdata::app.matching.show.create-btn')
                                </button>
                            </x-slot>
                        </x-admin::modal>
                    </x-admin::form>
                @endif
            </x-slot>
        @endif
    </x-admin::layouts.edit-page-header>

    <div class="flex gap-2.5 mt-3.5 max-xl:flex-wrap">
        <div class="flex flex-col gap-2 flex-1 max-xl:flex-auto">
            <div class="p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
                <p class="mb-4 text-base text-gray-800 dark:text-white font-semibold">
                    @lang($candidate->isPending() ? 'masterdata::app.matching.show.suggestions' : 'masterdata::app.matching.show.decision')
                </p>

                @if (! $candidate->isPending())
                    @if ($matched)
                        <p class="text-sm text-gray-600 dark:text-gray-300">
                            <a
                                href="{{ route('admin.masterdata.records.edit', $matched->uuid) }}"
                                class="text-primary-600 hover:text-primary-700 dark:text-primary-400"
                            >
                                {{ trans('masterdata::app.matching.show.resolved-record-label', [
                                    'record' => trans('masterdata::app.records.code-label', ['code' => $matched->code, 'label' => $matched->label]),
                                ]) }}
                            </a>
                        </p>
                    @endif
                @elseif ($suggestions === [])
                    <x-admin::list.empty-state :title="trans('masterdata::app.matching.show.no-suggestions')" />
                @else
                    <x-admin::table>
                        <x-admin::table.thead class="text-sm font-medium dark:bg-gray-800">
                            <x-admin::table.thead.tr>
                                <x-admin::table.th>@lang('masterdata::app.records.fields.code')</x-admin::table.th>
                                <x-admin::table.th>@lang('masterdata::app.records.fields.label')</x-admin::table.th>
                                <x-admin::table.th>@lang('masterdata::app.matching.show.score')</x-admin::table.th>
                                <x-admin::table.th>@lang('masterdata::app.matching.show.reason')</x-admin::table.th>
                                <x-admin::table.th></x-admin::table.th>
                            </x-admin::table.thead.tr>
                        </x-admin::table.thead>

                        <x-admin::table.tbody>
                            @foreach ($suggestions as $row)
                                @php($suggestion = $row['suggestion'])

                                <x-admin::table.tbody.tr>
                                    <x-admin::table.td>
                                        <a
                                            href="{{ route('admin.masterdata.records.edit', $suggestion->record->uuid) }}"
                                            class="text-primary-600 hover:text-primary-700 dark:text-primary-400"
                                        >
                                            {{ $suggestion->record->code }}
                                        </a>
                                    </x-admin::table.td>

                                    <x-admin::table.td>{{ $suggestion->record->label }}</x-admin::table.td>

                                    <x-admin::table.td>
                                        <x-admin::badge :variant="$suggestion->score >= 0.9 ? 'success' : ($suggestion->score >= 0.7 ? 'info' : 'neutral')">
                                            {{ trans('masterdata::app.quality.completeness.percent', ['score' => round($suggestion->score * 100)]) }}
                                        </x-admin::badge>
                                    </x-admin::table.td>

                                    <x-admin::table.td>{{ $suggestion->reasonLabel() }}</x-admin::table.td>

                                    <x-admin::table.td>
                                        @if ($row['canAccept'])
                                            <v-masterdata-confirm-action
                                                url="{{ route('admin.masterdata.matching.accept', $candidate->id) }}"
                                                :payload="{{ json_encode(['record' => $suggestion->record->uuid]) }}"
                                                label="@lang('masterdata::app.matching.show.accept')"
                                                title="@lang('masterdata::app.matching.show.accept-title')"
                                                message="{{ trans('masterdata::app.matching.show.accept-message', ['id' => $candidate->external_id, 'system' => $candidate->sourceSystem->code, 'code' => $suggestion->record->code]) }}"
                                            ></v-masterdata-confirm-action>
                                        @endif
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
                    @lang('masterdata::app.matching.show.details')
                </p>

                <dl class="grid grid-cols-[1fr_2fr] gap-x-4 gap-y-3 text-sm">
                    <dt class="font-semibold text-gray-800 dark:text-white">@lang('masterdata::app.matching.fields.status')</dt>
                    <dd>
                        <x-admin::badge :variant="$candidate->status->badgeVariant()">
                            @lang('masterdata::app.matching.statuses.'.$candidate->status->value)
                        </x-admin::badge>

                        @if ($candidate->auto_matched)
                            <x-admin::badge variant="info">
                                @lang('masterdata::app.matching.show.auto-matched')
                            </x-admin::badge>
                        @endif
                    </dd>

                    <dt class="font-semibold text-gray-800 dark:text-white">@lang('masterdata::app.matching.fields.system')</dt>
                    <dd class="text-gray-600 dark:text-gray-300">{{ $candidate->sourceSystem->name }} ({{ $candidate->sourceSystem->code }})</dd>

                    <dt class="font-semibold text-gray-800 dark:text-white">@lang('masterdata::app.matching.fields.entity-type')</dt>
                    <dd class="text-gray-600 dark:text-gray-300">{{ $candidate->entityType->name ?: $candidate->entityType->code }}</dd>

                    <dt class="font-semibold text-gray-800 dark:text-white">@lang('masterdata::app.matching.fields.external-id')</dt>
                    <dd class="text-gray-600 dark:text-gray-300 break-all">{{ $candidate->external_id }}</dd>

                    <dt class="font-semibold text-gray-800 dark:text-white">@lang('masterdata::app.matching.fields.code')</dt>
                    <dd class="text-gray-600 dark:text-gray-300 break-all">{{ $candidate->payload['code'] ?? '' }}</dd>

                    <dt class="font-semibold text-gray-800 dark:text-white">@lang('masterdata::app.matching.fields.label')</dt>
                    <dd class="text-gray-600 dark:text-gray-300">{{ $candidate->label }}</dd>

                    <dt class="font-semibold text-gray-800 dark:text-white">@lang('masterdata::app.matching.fields.submitted')</dt>
                    <dd class="text-gray-600 dark:text-gray-300">{{ $candidate->created_at }}</dd>
                </dl>

                @if ($candidate->isPending() && $candidate->auto_matched)
                    <p class="mt-4 text-sm text-gray-600 dark:text-gray-300">
                        @lang('masterdata::app.matching.show.auto-pending')
                    </p>
                @endif

                @unless ($candidate->isPending())
                    <p class="mt-4 text-sm text-gray-600 dark:text-gray-300">
                        {{ trans('masterdata::app.matching.show.resolved', [
                            'status' => trans('masterdata::app.matching.statuses.'.$candidate->status->value),
                            'admin'  => $candidate->resolvedBy?->name ?? trans('masterdata::app.matching.show.unknown-admin'),
                            'date'   => $candidate->resolved_at,
                        ]) }}
                    </p>
                @endunless
            </div>
        </div>
    </div>

    @include('masterdata::partials.confirm-action')
</x-admin::layouts>
