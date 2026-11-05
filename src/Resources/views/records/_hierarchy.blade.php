<div class="mt-3.5 p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
    <div class="flex flex-wrap items-center justify-between gap-2.5 mb-3">
        <p class="text-base text-gray-800 dark:text-white font-semibold">
            @lang('masterdata::app.hierarchy.title')
        </p>

        @if ($hierarchy['canMove'])
            <x-admin::form
                :action="route('admin.masterdata.records.hierarchy.update', $record->uuid)"
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
                            @lang('masterdata::app.hierarchy.change-parent')
                        </button>
                    </x-slot>

                    <x-slot:header>
                        <p class="text-lg text-gray-800 dark:text-white font-bold">
                            @lang('masterdata::app.hierarchy.change-parent')
                        </p>
                    </x-slot>

                    <x-slot:content>
                        <x-admin::form.control-group.control
                            type="hidden"
                            name="revision"
                            :value="$record->revision"
                        />

                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label>
                                @lang('masterdata::app.hierarchy.parent')
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="select"
                                name="parent"
                                async="true"
                                :list-route="route('admin.masterdata.records.options', ['entity_type' => $record->entityType->code, 'record' => $record->uuid])"
                                :value="json_encode($hierarchy['currentParent'])"
                                :label="trans('masterdata::app.hierarchy.parent')"
                                :placeholder="trans('masterdata::app.hierarchy.select-parent')"
                                track-by="id"
                                label-by="label"
                            />

                            <x-admin::form.control-group.help>
                                @lang('masterdata::app.help.records.parent')
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
        @endif
    </div>

    @include('masterdata::records._trail', [
        'items'     => $lineage['path'],
        'label'     => trans('masterdata::app.hierarchy.ancestors'),
        'rootLabel' => trans('masterdata::app.hierarchy.root'),
        'class'     => 'mb-4',
    ])

    <div class="flex flex-wrap items-center justify-between gap-2.5 mb-2">
        <p class="text-sm text-gray-800 dark:text-white font-semibold">
            @lang('masterdata::app.hierarchy.children')
        </p>

        @if ($childTypes !== [])
            <div class="flex flex-wrap items-center gap-3 text-sm">
                @foreach ($childTypes as $childType)
                    <a
                        href="{{ $childType['url'] }}"
                        class="text-primary-600 hover:text-primary-700 dark:text-primary-400"
                    >
                        {{ $childType['label'] }}
                    </a>
                @endforeach
            </div>
        @endif
    </div>

    @if ($hierarchy['children']->isEmpty())
        <x-admin::list.empty-state
            :title="trans('masterdata::app.hierarchy.empty-children')"
            :description="$childTypes === []
                ? trans('masterdata::app.hierarchy.empty-children-none', ['type' => $record->entityType->name ?: $record->entityType->code])
                : trans('masterdata::app.hierarchy.empty-children-types', ['children' => implode(', ', array_column($childTypes, 'name')), 'type' => $record->entityType->name ?: $record->entityType->code])"
        />
    @else
        <x-admin::table>
            <x-admin::table.thead class="text-sm font-medium dark:bg-gray-800">
                <x-admin::table.thead.tr>
                    <x-admin::table.th>@lang('masterdata::app.records.fields.code')</x-admin::table.th>
                    <x-admin::table.th>@lang('masterdata::app.records.fields.label')</x-admin::table.th>
                    <x-admin::table.th>@lang('masterdata::app.records.fields.type')</x-admin::table.th>
                    <x-admin::table.th>@lang('masterdata::app.records.fields.status')</x-admin::table.th>
                </x-admin::table.thead.tr>
            </x-admin::table.thead>

            <x-admin::table.tbody>
                @foreach ($hierarchy['children'] as $child)
                    <x-admin::table.tbody.tr>
                        <x-admin::table.td>
                            <a
                                href="{{ route('admin.masterdata.records.edit', $child->uuid) }}"
                                class="text-primary-600 hover:text-primary-700 dark:text-primary-400"
                            >
                                {{ $child->code }}
                            </a>
                        </x-admin::table.td>

                        <x-admin::table.td>{{ $child->label }}</x-admin::table.td>

                        <x-admin::table.td>
                            <x-admin::badge variant="info">
                                {{ $child->entityType?->name ?: $child->entityType?->code }}
                            </x-admin::badge>
                        </x-admin::table.td>

                        <x-admin::table.td>
                            <x-admin::badge :variant="$child->status === \Webkul\MasterDataManagement\Enums\RecordStatus::Active ? 'success' : 'neutral'">
                                @lang('masterdata::app.records.statuses.'.$child->status->value)
                            </x-admin::badge>
                        </x-admin::table.td>
                    </x-admin::table.tbody.tr>
                @endforeach
            </x-admin::table.tbody>
        </x-admin::table>

        @if ($hierarchy['children']->count() >= $hierarchy['limit'])
            <p class="mt-2 text-xs text-gray-600 dark:text-gray-300">
                {{ trans('masterdata::app.hierarchy.children-limited', ['count' => $hierarchy['limit']]) }}
            </p>
        @endif
    @endif
</div>
