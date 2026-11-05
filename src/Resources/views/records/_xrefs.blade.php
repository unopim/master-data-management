@if ($canLinkXrefs)
    <div class="flex justify-end mb-3">
        <x-admin::form
            :action="route('admin.masterdata.records.xrefs.store', $record->uuid)"
            ajax="true"
            :track-dirty="false"
        >
            <x-admin::modal>
                <x-slot:toggle>
                    <button
                        type="button"
                        class="secondary-button"
                    >
                        @lang('masterdata::app.records.xrefs.link-btn')
                    </button>
                </x-slot>

                <x-slot:header>
                    <p class="text-lg text-gray-800 dark:text-white font-bold">
                        @lang('masterdata::app.records.xrefs.link-title')
                    </p>
                </x-slot>

                <x-slot:content>
                    <p class="mb-4 text-sm text-gray-600 dark:text-gray-300">
                        @lang('masterdata::app.records.xrefs.link-info')
                    </p>

                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label class="required">
                            @lang('masterdata::app.records.xrefs.system')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="select"
                            name="source_system_id"
                            rules="required"
                            :label="trans('masterdata::app.records.xrefs.system')"
                            :placeholder="trans('masterdata::app.records.xrefs.select-system')"
                            async="true"
                            :list-route="route('admin.masterdata.source_systems.options')"
                            track-by="id"
                            label-by="label"
                        />

                        <x-admin::form.control-group.help>
                            @lang('masterdata::app.help.records.xref-system')
                        </x-admin::form.control-group.help>

                        <x-admin::form.control-group.error control-name="source_system_id" />
                    </x-admin::form.control-group>

                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label class="required">
                            @lang('masterdata::app.records.xrefs.external-id')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="text"
                            name="external_id"
                            rules="required|max:255"
                            :label="trans('masterdata::app.records.xrefs.external-id')"
                            :placeholder="trans('masterdata::app.records.xrefs.external-id')"
                        />

                        <x-admin::form.control-group.help>
                            @lang('masterdata::app.help.records.xref-external-id')
                        </x-admin::form.control-group.help>

                        <x-admin::form.control-group.error control-name="external_id" />
                    </x-admin::form.control-group>
                </x-slot>

                <x-slot:footer>
                    <button
                        type="submit"
                        class="primary-button"
                    >
                        @lang('masterdata::app.records.xrefs.save')
                    </button>
                </x-slot>
            </x-admin::modal>
        </x-admin::form>
    </div>
@endif

@if ($xrefs === [])
    <x-admin::list.empty-state :title="trans('masterdata::app.records.empty-xrefs')" />
@else
    <x-admin::table>
        <x-admin::table.thead class="text-sm font-medium dark:bg-gray-800">
            <x-admin::table.thead.tr>
                <x-admin::table.th>@lang('masterdata::app.records.xrefs.system')</x-admin::table.th>
                <x-admin::table.th>@lang('masterdata::app.records.xrefs.external-id')</x-admin::table.th>
                <x-admin::table.th>@lang('masterdata::app.records.xrefs.status')</x-admin::table.th>
                <x-admin::table.th>@lang('masterdata::app.records.xrefs.last-seen')</x-admin::table.th>

                @if ($canLinkXrefs)
                    <x-admin::table.th>@lang('masterdata::app.records.xrefs.actions')</x-admin::table.th>
                @endif
            </x-admin::table.thead.tr>
        </x-admin::table.thead>

        <x-admin::table.tbody>
            @foreach ($xrefs as $xref)
                <x-admin::table.tbody.tr>
                    <x-admin::table.td>{{ $xref->code }}</x-admin::table.td>
                    <x-admin::table.td>{{ $xref->external_id }}</x-admin::table.td>
                    <x-admin::table.td>
                        <x-admin::badge :variant="$xref->status === \Webkul\MasterDataManagement\Enums\XrefStatus::Active->value ? 'success' : 'neutral'">
                            @lang('masterdata::app.records.xref-statuses.'.$xref->status)
                        </x-admin::badge>
                    </x-admin::table.td>
                    <x-admin::table.td>{{ $xref->last_seen_at }}</x-admin::table.td>

                    @if ($canLinkXrefs)
                        <x-admin::table.td>
                            @if ($xref->status === \Webkul\MasterDataManagement\Enums\XrefStatus::Active->value)
                                <v-masterdata-confirm-action
                                    url="{{ route('admin.masterdata.records.xrefs.destroy', [$record->uuid, $xref->id]) }}"
                                    method="delete"
                                    label="@lang('masterdata::app.records.xrefs.unlink')"
                                    title="@lang('masterdata::app.records.xrefs.unlink-confirm-title')"
                                    message="@lang('masterdata::app.records.xrefs.unlink-confirm-message')"
                                ></v-masterdata-confirm-action>
                            @endif
                        </x-admin::table.td>
                    @endif
                </x-admin::table.tbody.tr>
            @endforeach
        </x-admin::table.tbody>
    </x-admin::table>
@endif
