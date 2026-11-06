<div class="flex flex-wrap items-center justify-between gap-2.5 mb-3">
    <div>
        <p class="text-base text-gray-800 dark:text-white font-semibold">
            @lang('masterdata::app.hierarchies.record.title')
        </p>

        <p class="text-xs text-gray-500 dark:text-gray-300">
            @lang('masterdata::app.hierarchies.record.info')
        </p>
    </div>

    <a
        href="{{ route('admin.masterdata.records.tree', $record->entityType->code) }}"
        class="text-sm text-primary-600 hover:text-primary-700 dark:text-primary-400"
    >
        @lang('masterdata::app.hierarchies.tree.open')
    </a>
</div>

@if ($placements === [])
    <x-admin::list.empty-state
        :title="trans('masterdata::app.hierarchies.record.empty')"
        :description="trans('masterdata::app.hierarchies.record.empty-info')"
    />
@else
    <x-admin::table>
        <x-admin::table.thead class="text-sm font-medium dark:bg-gray-800">
            <x-admin::table.thead.tr>
                <x-admin::table.th>@lang('masterdata::app.hierarchies.record.hierarchy')</x-admin::table.th>
                <x-admin::table.th>@lang('masterdata::app.hierarchies.record.path')</x-admin::table.th>

                @if ($canPlace)
                    <x-admin::table.th>@lang('masterdata::app.hierarchies.record.actions')</x-admin::table.th>
                @endif
            </x-admin::table.thead.tr>
        </x-admin::table.thead>

        <x-admin::table.tbody>
            @foreach ($placements as $placement)
                <x-admin::table.tbody.tr>
                    <x-admin::table.td>{{ $placement['hierarchy']->displayLabel() }}</x-admin::table.td>

                    <x-admin::table.td class="!whitespace-normal">
                        @if (! $placement['placed'])
                            <span class="text-gray-500 dark:text-gray-400">@lang('masterdata::app.hierarchies.record.not-placed')</span>
                        @elseif ($placement['trail'] === [])
                            <span class="text-gray-600 dark:text-gray-300">@lang('masterdata::app.hierarchy.root')</span>
                        @else
                            <nav
                                aria-label="{{ trans('masterdata::app.hierarchies.record.path') }}"
                                class="flex flex-wrap items-center gap-1.5 text-sm"
                            >
                                @foreach ($placement['trail'] as $item)
                                    <a
                                        href="{{ route('admin.masterdata.records.edit', $item['uuid']) }}"
                                        class="text-primary-600 hover:text-primary-700 dark:text-primary-400"
                                    >
                                        {{ $item['label'] ?: $item['code'] }}
                                    </a>

                                    <span
                                        class="text-gray-400 dark:text-gray-500"
                                        aria-hidden="true"
                                    >
                                        @lang('masterdata::app.hierarchy.trail-separator')
                                    </span>
                                @endforeach

                                <span
                                    class="font-semibold text-gray-800 dark:text-white"
                                    aria-current="page"
                                >
                                    {{ $record->label ?: $record->code }}
                                </span>
                            </nav>
                        @endif
                    </x-admin::table.td>

                    @if ($canPlace)
                        <x-admin::table.td>
                            <div class="flex flex-wrap items-center gap-2.5">
                                <x-admin::form
                                    :action="route('admin.masterdata.records.hierarchies.update', [$record->uuid, $placement['hierarchy']->id])"
                                    method="PUT"
                                    ajax="true"
                                    :track-dirty="false"
                                >
                                    <x-admin::modal>
                                        <x-slot:toggle>
                                            <button
                                                type="button"
                                                class="secondary-button"
                                            >
                                                {{ $placement['placed'] ? trans('masterdata::app.hierarchy.change-parent') : trans('masterdata::app.hierarchies.record.place') }}
                                            </button>
                                        </x-slot>

                                        <x-slot:header>
                                            <p class="text-lg text-gray-800 dark:text-white font-bold">
                                                {{ trans('masterdata::app.hierarchies.record.place-title', ['hierarchy' => $placement['hierarchy']->displayLabel()]) }}
                                            </p>
                                        </x-slot>

                                        <x-slot:content>
                                            <x-admin::form.control-group>
                                                <x-admin::form.control-group.label>
                                                    @lang('masterdata::app.hierarchy.parent')
                                                </x-admin::form.control-group.label>

                                                <x-admin::form.control-group.control
                                                    type="select"
                                                    name="parent"
                                                    async="true"
                                                    :list-route="route('admin.masterdata.records.hierarchies.options', [$record->uuid, $placement['hierarchy']->id])"
                                                    :value="json_encode($placement['parent'])"
                                                    :label="trans('masterdata::app.hierarchy.parent')"
                                                    :placeholder="trans('masterdata::app.hierarchy.select-parent')"
                                                    track-by="id"
                                                    label-by="label"
                                                />

                                                <x-admin::form.control-group.help>
                                                    @lang('masterdata::app.hierarchies.record.parent-info')
                                                </x-admin::form.control-group.help>

                                                <x-admin::form.control-group.error control-name="parent" />
                                            </x-admin::form.control-group>
                                        </x-slot>

                                        <x-slot:footer>
                                            <button
                                                type="submit"
                                                class="primary-button"
                                            >
                                                @lang('masterdata::app.hierarchy.save')
                                            </button>
                                        </x-slot>
                                    </x-admin::modal>
                                </x-admin::form>

                                @if ($placement['placed'])
                                    <v-masterdata-confirm-action
                                        url="{{ route('admin.masterdata.records.hierarchies.destroy', [$record->uuid, $placement['hierarchy']->id]) }}"
                                        method="delete"
                                        label="@lang('masterdata::app.hierarchies.record.remove')"
                                        title="@lang('masterdata::app.hierarchies.record.remove-confirm-title')"
                                        message="@lang('masterdata::app.hierarchies.record.remove-confirm-message')"
                                    ></v-masterdata-confirm-action>
                                @endif
                            </div>
                        </x-admin::table.td>
                    @endif
                </x-admin::table.tbody.tr>
            @endforeach
        </x-admin::table.tbody>
    </x-admin::table>
@endif
