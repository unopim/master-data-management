<x-admin::layouts>
    <x-slot:title>
        @lang('masterdata::app.matching.index.title')
    </x-slot>

    <x-admin::layouts.page-header :title="trans('masterdata::app.matching.index.title')">
        <x-slot:actions>
            @if ($canSubmit)
                <x-admin::form
                    :action="route('admin.masterdata.matching.store')"
                    ajax="true"
                    :track-dirty="false"
                >
                    <x-admin::modal>
                        <x-slot:toggle>
                            <button
                                type="button"
                                class="primary-button"
                            >
                                @lang('masterdata::app.matching.index.add-btn')
                            </button>
                        </x-slot>

                        <x-slot:header>
                            <p class="text-lg text-gray-800 dark:text-white font-bold">
                                @lang('masterdata::app.matching.add.title')
                            </p>
                        </x-slot>

                        <x-slot:content>
                            <p class="mb-4 text-sm text-gray-600 dark:text-gray-300">
                                @lang('masterdata::app.matching.add.info')
                            </p>

                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label class="required">
                                    @lang('masterdata::app.matching.fields.system')
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="select"
                                    name="source_system_id"
                                    rules="required"
                                    :label="trans('masterdata::app.matching.fields.system')"
                                    :placeholder="trans('masterdata::app.crosswalk.fields.select')"
                                    async="true"
                                    :list-route="route('admin.masterdata.source_systems.options')"
                                    track-by="id"
                                    label-by="label"
                                />

                                <x-admin::form.control-group.help>
                                    @lang('masterdata::app.help.matching.system')
                                </x-admin::form.control-group.help>

                                <x-admin::form.control-group.error control-name="source_system_id" />
                            </x-admin::form.control-group>

                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label class="required">
                                    @lang('masterdata::app.matching.fields.entity-type')
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="select"
                                    name="entity_type_id"
                                    rules="required"
                                    :label="trans('masterdata::app.matching.fields.entity-type')"
                                    :placeholder="trans('masterdata::app.crosswalk.fields.select')"
                                    :options="json_encode($typeOptions)"
                                    track-by="id"
                                    label-by="label"
                                />

                                <x-admin::form.control-group.help>
                                    @lang('masterdata::app.help.matching.entity-type')
                                </x-admin::form.control-group.help>

                                <x-admin::form.control-group.error control-name="entity_type_id" />
                            </x-admin::form.control-group>

                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label class="required">
                                    @lang('masterdata::app.matching.fields.external-id')
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="text"
                                    name="external_id"
                                    rules="required|max:255"
                                    :label="trans('masterdata::app.matching.fields.external-id')"
                                    :placeholder="trans('masterdata::app.matching.fields.external-id')"
                                />

                                <x-admin::form.control-group.help>
                                    @lang('masterdata::app.help.matching.external-id')
                                </x-admin::form.control-group.help>

                                <x-admin::form.control-group.error control-name="external_id" />
                            </x-admin::form.control-group>

                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label>
                                    @lang('masterdata::app.matching.fields.code')
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="text"
                                    name="code"
                                    rules="max:255"
                                    :label="trans('masterdata::app.matching.fields.code')"
                                    :placeholder="trans('masterdata::app.matching.fields.code')"
                                />

                                <x-admin::form.control-group.help>
                                    @lang('masterdata::app.help.matching.code')
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
                                    :label="trans('masterdata::app.matching.fields.label')"
                                    :placeholder="trans('masterdata::app.matching.fields.label')"
                                />

                                <x-admin::form.control-group.help>
                                    @lang('masterdata::app.help.matching.label')
                                </x-admin::form.control-group.help>

                                <x-admin::form.control-group.error control-name="label" />
                            </x-admin::form.control-group>
                        </x-slot>

                        <x-slot:footer>
                            <button
                                type="submit"
                                class="primary-button"
                            >
                                @lang('masterdata::app.matching.add.save-btn')
                            </button>
                        </x-slot>
                    </x-admin::modal>
                </x-admin::form>
            @endif
        </x-slot>
    </x-admin::layouts.page-header>

    <p class="mt-3.5 text-sm text-gray-600 dark:text-gray-300">
        @lang('masterdata::app.matching.index.info')
    </p>

    <x-admin::datagrid :src="route('admin.masterdata.matching.index')" />
</x-admin::layouts>
