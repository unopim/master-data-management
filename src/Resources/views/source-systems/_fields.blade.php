<div class="flex gap-2.5 mt-3.5 max-xl:flex-wrap">
    <div class="flex flex-col gap-2 flex-1 max-xl:flex-auto">
        <div class="p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
            <p class="mb-4 text-base text-gray-800 dark:text-white font-semibold">
                @lang('masterdata::app.source-systems.form.general')
            </p>

            @if ($sourceSystem->exists)
                <x-admin::form.control-group>
                    <x-admin::form.control-group.label>
                        @lang('masterdata::app.source-systems.form.code')
                    </x-admin::form.control-group.label>

                    <x-admin::form.control-group.control
                        type="text"
                        name="code_display"
                        :value="$sourceSystem->code"
                        :label="trans('masterdata::app.source-systems.form.code')"
                        disabled="disabled"
                    />

                    <x-admin::form.control-group.help>
                        @lang('masterdata::app.help.source-systems.code')
                    </x-admin::form.control-group.help>
                </x-admin::form.control-group>
            @else

                <x-admin::form.control-group>
                    <x-admin::form.control-group.label class="required">
                        @lang('masterdata::app.source-systems.form.code')
                    </x-admin::form.control-group.label>

                    <x-admin::form.control-group.control
                        type="text"
                        name="code"
                        rules="required"
                        :value="old('code')"
                        :label="trans('masterdata::app.source-systems.form.code')"
                        :placeholder="trans('masterdata::app.source-systems.form.code')"
                    />

                    <x-admin::form.control-group.help>
                        @lang('masterdata::app.help.source-systems.code')
                    </x-admin::form.control-group.help>

                    <x-admin::form.control-group.error control-name="code" />
                </x-admin::form.control-group>
            @endif

            <x-admin::form.control-group>
                <x-admin::form.control-group.label class="required">
                    @lang('masterdata::app.source-systems.form.name')
                </x-admin::form.control-group.label>

                <x-admin::form.control-group.control
                    type="text"
                    name="name"
                    rules="required"
                    :value="old('name') ?? $sourceSystem->name"
                    :label="trans('masterdata::app.source-systems.form.name')"
                    :placeholder="trans('masterdata::app.source-systems.form.name')"
                />

                <x-admin::form.control-group.help>
                    @lang('masterdata::app.help.source-systems.name')
                </x-admin::form.control-group.help>

                <x-admin::form.control-group.error control-name="name" />
            </x-admin::form.control-group>

            <x-admin::form.control-group>
                <x-admin::form.control-group.label class="required">
                    @lang('masterdata::app.source-systems.form.type')
                </x-admin::form.control-group.label>

                <x-admin::form.control-group.control
                    type="select"
                    name="type"
                    rules="required"
                    :value="old('type') ?? $sourceSystem->type?->value ?? 'other'"
                    :label="trans('masterdata::app.source-systems.form.type')"
                    :options="json_encode($typeOptions)"
                    track-by="id"
                    label-by="label"
                />

                <x-admin::form.control-group.help>
                    @lang('masterdata::app.help.source-systems.type')
                </x-admin::form.control-group.help>

                <x-admin::form.control-group.error control-name="type" />
            </x-admin::form.control-group>

            <x-admin::form.control-group>
                <x-admin::form.control-group.label class="required">
                    @lang('masterdata::app.source-systems.form.priority')
                </x-admin::form.control-group.label>

                <x-admin::form.control-group.control
                    type="number"
                    name="priority"
                    rules="required"
                    :value="old('priority') ?? $sourceSystem->priority"
                    :label="trans('masterdata::app.source-systems.form.priority')"
                    :placeholder="trans('masterdata::app.source-systems.form.priority')"
                />

                <x-admin::form.control-group.help>
                    @lang('masterdata::app.help.source-systems.priority')
                </x-admin::form.control-group.help>

                <x-admin::form.control-group.error control-name="priority" />
            </x-admin::form.control-group>

            <x-admin::form.control-group class="!mb-0">
                <x-admin::form.control-group.label>
                    @lang('masterdata::app.source-systems.form.contact-owner')
                </x-admin::form.control-group.label>

                <x-admin::form.control-group.control
                    type="text"
                    name="contact_owner"
                    :value="old('contact_owner') ?? $sourceSystem->contact_owner"
                    :label="trans('masterdata::app.source-systems.form.contact-owner')"
                    :placeholder="trans('masterdata::app.source-systems.form.contact-owner')"
                />

                <x-admin::form.control-group.help>
                    @lang('masterdata::app.help.source-systems.contact-owner')
                </x-admin::form.control-group.help>

                <x-admin::form.control-group.error control-name="contact_owner" />
            </x-admin::form.control-group>
        </div>
    </div>

    <div class="flex flex-col gap-2 w-[360px] max-w-full max-sm:w-full">
        <div class="p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
            <p class="mb-4 text-base text-gray-800 dark:text-white font-semibold">
                @lang('masterdata::app.source-systems.form.settings')
            </p>

            <x-admin::form.control-group>
                <x-admin::form.control-group.label>
                    @lang('masterdata::app.source-systems.form.is-active')
                </x-admin::form.control-group.label>

                <x-admin::form.control-group.control
                    type="hidden"
                    name="is_active"
                    value="0"
                />

                <x-admin::form.control-group.control
                    type="switch"
                    name="is_active"
                    value="1"
                    :label="trans('masterdata::app.source-systems.form.is-active')"
                    :checked="(bool) (old('is_active') ?? $sourceSystem->is_active)"
                />

                <x-admin::form.control-group.help>
                    @lang('masterdata::app.help.source-systems.is-active')
                </x-admin::form.control-group.help>
            </x-admin::form.control-group>

            <x-admin::form.control-group>
                <x-admin::form.control-group.label>
                    @lang('masterdata::app.source-systems.form.requires-approval')
                </x-admin::form.control-group.label>

                <x-admin::form.control-group.control
                    type="hidden"
                    name="requires_approval"
                    value="0"
                />

                <x-admin::form.control-group.control
                    type="switch"
                    name="requires_approval"
                    value="1"
                    :label="trans('masterdata::app.source-systems.form.requires-approval')"
                    :checked="(bool) (old('requires_approval') ?? $sourceSystem->requires_approval)"
                />

                <x-admin::form.control-group.help>
                    @lang('masterdata::app.help.source-systems.requires-approval')
                </x-admin::form.control-group.help>
            </x-admin::form.control-group>

            <x-admin::form.control-group class="!mb-0">
                <x-admin::form.control-group.label>
                    @lang('masterdata::app.source-systems.form.external-id-is-personal')
                </x-admin::form.control-group.label>

                <x-admin::form.control-group.control
                    type="hidden"
                    name="external_id_is_personal"
                    value="0"
                />

                <x-admin::form.control-group.control
                    type="switch"
                    name="external_id_is_personal"
                    value="1"
                    :label="trans('masterdata::app.source-systems.form.external-id-is-personal')"
                    :checked="(bool) (old('external_id_is_personal') ?? $sourceSystem->external_id_is_personal)"
                />

                <x-admin::form.control-group.help>
                    @lang('masterdata::app.help.source-systems.external-id-is-personal')
                </x-admin::form.control-group.help>
            </x-admin::form.control-group>
        </div>
    </div>
</div>
