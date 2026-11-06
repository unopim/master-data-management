@if (bouncer()->hasPermission('masterdata.entity_types.model_export'))
    <a
        href="{{ route('admin.masterdata.model_transfer.export') }}"
        class="secondary-button"
    >
        @lang('masterdata::app.model-transfer.export.button')
    </a>
@endif

@if (bouncer()->hasPermission('masterdata.entity_types.model_import'))
    <x-admin::form
        :action="route('admin.masterdata.model_transfer.import')"
        enctype="multipart/form-data"
        ajax="true"
        :track-dirty="false"
    >
        <x-admin::modal>
            <x-slot:toggle>
                <button
                    type="button"
                    class="secondary-button"
                >
                    @lang('masterdata::app.model-transfer.import.button')
                </button>
            </x-slot>

            <x-slot:header>
                <p class="text-lg text-gray-800 dark:text-white font-bold">
                    @lang('masterdata::app.model-transfer.import.title')
                </p>
            </x-slot>

            <x-slot:content>
                <x-admin::form.control-group>
                    <x-admin::form.control-group.label class="required">
                        @lang('masterdata::app.model-transfer.import.file')
                    </x-admin::form.control-group.label>

                    <x-admin::form.control-group.control
                        type="file"
                        name="file"
                        rules="required"
                        :label="trans('masterdata::app.model-transfer.import.file')"
                        :info="trans('masterdata::app.model-transfer.import.file-info', ['size' => (int) config('masterdata.model_transfer.max_file_kb', 5120)])"
                    />

                    <x-admin::form.control-group.error control-name="file" />
                </x-admin::form.control-group>

                <x-admin::form.control-group class="!mb-0">
                    <x-admin::form.control-group.label>
                        @lang('masterdata::app.model-transfer.import.publish')
                    </x-admin::form.control-group.label>

                    <x-admin::form.control-group.control
                        type="switch"
                        name="publish"
                        value="1"
                        :label="trans('masterdata::app.model-transfer.import.publish')"
                        :checked="false"
                    />

                    <x-admin::form.control-group.help>
                        @lang('masterdata::app.model-transfer.import.publish-info')
                    </x-admin::form.control-group.help>
                </x-admin::form.control-group>
            </x-slot>

            <x-slot:footer>
                <button
                    type="submit"
                    class="primary-button"
                >
                    @lang('masterdata::app.model-transfer.import.save-btn')
                </button>
            </x-slot>
        </x-admin::modal>
    </x-admin::form>
@endif
