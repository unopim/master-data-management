<div class="p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
    <x-admin::heading
        class="mb-4"
        :title="trans('masterdata::app.access.role-card.title')"
        :subtitle="trans('masterdata::app.access.role-card.info')"
    />

    <x-admin::form.control-group.control
        type="hidden"
        name="masterdata_access[present]"
        value="1"
    />

    @if ($accessGrants->isEmpty())
        <x-admin::list.empty-state :title="trans('masterdata::app.access.role-card.empty')" />
    @else
        <div class="mb-4 overflow-x-auto">
            <x-admin::table>
                <x-admin::table.thead class="text-sm font-medium dark:bg-gray-800">
                    <x-admin::table.thead.tr>
                        <x-admin::table.th>@lang('masterdata::app.access.form.root')</x-admin::table.th>
                        <x-admin::table.th>@lang('masterdata::app.access.form.entity-type')</x-admin::table.th>
                        <x-admin::table.th>@lang('masterdata::app.access.form.actions')</x-admin::table.th>

                        @if ($canRemoveGrant)
                            <x-admin::table.th class="text-center">@lang('masterdata::app.access.role-card.remove')</x-admin::table.th>
                        @endif
                    </x-admin::table.thead.tr>
                </x-admin::table.thead>

                <x-admin::table.tbody>
                    @foreach ($accessGrants as $accessGrant)
                        <x-admin::table.tbody.tr>
                            <x-admin::table.td>{{ trim(($accessGrant->record?->code ?? '').' '.($accessGrant->record?->label ?? '')) }}</x-admin::table.td>
                            <x-admin::table.td>{{ $accessGrant->entityType?->name ?: ($accessGrant->entityType?->code ?? trans('masterdata::app.access.form.any-entity-type')) }}</x-admin::table.td>
                            <x-admin::table.td>{{ collect($accessGrant->actions)->map(fn ($action) => trans('masterdata::app.access.actions.'.$action))->implode(', ') }}</x-admin::table.td>

                            @if ($canRemoveGrant)
                                @php
                                    $controlId = 'masterdata_access_remove_'.$accessGrant->id;
                                @endphp

                                <x-admin::table.td class="text-center">
                                    <x-admin::form.control-group class="!mb-0 flex justify-center">
                                        <x-admin::form.control-group.control
                                            type="checkbox"
                                            :id="$controlId"
                                            :name="'masterdata_access[remove]['.$accessGrant->id.']'"
                                            :value="$accessGrant->id"
                                            :for="$controlId"
                                            :label="trans('masterdata::app.access.role-card.remove')"
                                        />
                                    </x-admin::form.control-group>
                                </x-admin::table.td>
                            @endif
                        </x-admin::table.tbody.tr>
                    @endforeach
                </x-admin::table.tbody>
            </x-admin::table>
        </div>
    @endif

    @if ($canAddGrant)
        <p class="mb-2 text-sm font-semibold text-gray-800 dark:text-white">
            @lang('masterdata::app.access.role-card.add')
        </p>

        <x-admin::form.control-group>
            <x-admin::form.control-group.label>
                @lang('masterdata::app.access.form.root')
            </x-admin::form.control-group.label>

            <x-admin::form.control-group.control
                type="select"
                name="masterdata_access[record]"
                async="true"
                :list-route="route('admin.masterdata.access.options')"
                :label="trans('masterdata::app.access.form.root')"
                :placeholder="trans('masterdata::app.access.form.root')"
                track-by="id"
                label-by="label"
            />

            <x-admin::form.control-group.error control-name="masterdata_access.record" />
        </x-admin::form.control-group>

        <x-admin::form.control-group>
            <x-admin::form.control-group.label>
                @lang('masterdata::app.access.form.entity-type')
            </x-admin::form.control-group.label>

            <x-admin::form.control-group.control
                type="select"
                name="masterdata_access[entity_type_id]"
                :label="trans('masterdata::app.access.form.entity-type')"
                :placeholder="trans('masterdata::app.access.form.any-entity-type')"
                :options="json_encode($entityTypeOptions)"
                track-by="id"
                label-by="label"
            />
        </x-admin::form.control-group>

        <x-admin::form.control-group class="!mb-0">
            <x-admin::form.control-group.label>
                @lang('masterdata::app.access.form.actions')
            </x-admin::form.control-group.label>

            <x-admin::form.control-group.control
                type="multiselect"
                name="masterdata_access[actions]"
                :label="trans('masterdata::app.access.form.actions')"
                :placeholder="trans('masterdata::app.access.form.actions')"
                :options="json_encode($actionOptions)"
                track-by="id"
                label-by="label"
            />

            <x-admin::form.control-group.error control-name="masterdata_access.actions" />
        </x-admin::form.control-group>
    @endif
</div>
