<div class="mt-3.5 p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
    <p class="text-base text-gray-800 dark:text-white font-semibold">
        @lang('masterdata::app.entity-types.permissions.title')
    </p>

    <p class="mb-4 text-xs text-gray-500 dark:text-gray-300">
        @lang('masterdata::app.entity-types.permissions.info')
    </p>

    @if ($roles->isEmpty())
        <x-admin::list.empty-state :title="trans('masterdata::app.entity-types.permissions.empty')" />
    @else
        <div class="overflow-x-auto">
            <x-admin::table>
                <x-admin::table.thead class="text-sm font-medium dark:bg-gray-800">
                    <x-admin::table.thead.tr>
                        <x-admin::table.th>@lang('masterdata::app.entity-types.permissions.role')</x-admin::table.th>

                        @foreach ($recordActions as $action)
                            <x-admin::table.th class="text-center">@lang('masterdata::app.entity-types.permissions.actions.'.$action->value)</x-admin::table.th>
                        @endforeach
                    </x-admin::table.thead.tr>
                </x-admin::table.thead>

                <x-admin::table.tbody>
                    @foreach ($roles as $role)
                        <x-admin::table.tbody.tr>
                            <x-admin::table.td>{{ $role->name }}</x-admin::table.td>

                            @foreach ($recordActions as $action)
                                @php
                                    $controlId = 'permission_'.$role->id.'_'.$action->value;
                                @endphp

                                <x-admin::table.td class="text-center">
                                    <x-admin::form.control-group class="!mb-0 flex justify-center">
                                        <x-admin::form.control-group.control
                                            type="checkbox"
                                            :id="$controlId"
                                            :name="'permissions['.$role->id.']['.$action->value.']'"
                                            :value="$action->value"
                                            :for="$controlId"
                                            :label="trans('masterdata::app.entity-types.permissions.control-label', [
                                                'role'   => $role->name,
                                                'action' => trans('masterdata::app.entity-types.permissions.actions.'.$action->value),
                                            ])"
                                            :checked="in_array($action->value, $grants[$role->id] ?? [], true)"
                                        />
                                    </x-admin::form.control-group>
                                </x-admin::table.td>
                            @endforeach
                        </x-admin::table.tbody.tr>
                    @endforeach
                </x-admin::table.tbody>
            </x-admin::table>
        </div>
    @endif
</div>
