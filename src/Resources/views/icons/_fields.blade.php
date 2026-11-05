<div class="flex gap-2.5 mt-3.5 max-xl:flex-wrap">
    <div class="flex flex-col gap-2 flex-1 max-xl:flex-auto">
        <div class="p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
            <x-admin::form.control-group>
                <x-admin::form.control-group.label @class(['required' => ! $icon->exists])>
                    @lang('masterdata::app.icons.form.code')
                </x-admin::form.control-group.label>

                @if ($icon->exists)
                    <x-admin::form.control-group.control
                        type="text"
                        name="code_display"
                        :value="$icon->code"
                        :label="trans('masterdata::app.icons.form.code')"
                        disabled="disabled"
                    />

                    <x-admin::form.control-group.help>
                        @lang('masterdata::app.help.icons.code')
                    </x-admin::form.control-group.help>
                @else
                    <x-admin::form.control-group.control
                        type="text"
                        name="code"
                        rules="required"
                        :value="old('code')"
                        :label="trans('masterdata::app.icons.form.code')"
                        :placeholder="trans('masterdata::app.icons.form.code')"
                    />

                    <x-admin::form.control-group.help>
                        @lang('masterdata::app.help.icons.code')
                    </x-admin::form.control-group.help>

                    <x-admin::form.control-group.error control-name="code" />
                @endif
            </x-admin::form.control-group>

            <x-admin::form.control-group>
                <x-admin::form.control-group.label class="required">
                    @lang('masterdata::app.icons.form.name')
                </x-admin::form.control-group.label>

                <x-admin::form.control-group.control
                    type="text"
                    name="name"
                    rules="required"
                    :value="old('name') ?? $icon->name"
                    :label="trans('masterdata::app.icons.form.name')"
                    :placeholder="trans('masterdata::app.icons.form.name')"
                />

                <x-admin::form.control-group.help>
                    @lang('masterdata::app.help.icons.name')
                </x-admin::form.control-group.help>

                <x-admin::form.control-group.error control-name="name" />
            </x-admin::form.control-group>

            <x-admin::form.control-group class="!mb-0">
                <x-admin::form.control-group.label class="required">
                    @lang('masterdata::app.icons.form.file')
                </x-admin::form.control-group.label>

                <x-admin::media.image
                    name="file"
                    :show-suggestions="false"
                    :accepted-extensions="\Webkul\MasterDataManagement\Services\IconStore::EXTENSIONS"
                    width="96px"
                    height="96px"
                    object-fit="contain"
                    :instructions="trans('masterdata::app.icons.form.file-info', ['size' => \Webkul\MasterDataManagement\Services\IconStore::MAX_KILOBYTES])"
                    :uploaded-images="$icon->exists ? [['id' => 'file', 'url' => $icon->url(), 'value' => $icon->path]] : []"
                />

                <x-admin::form.control-group.error control-name="file" />
            </x-admin::form.control-group>
        </div>
    </div>

    @if ($icon->exists)
        <div class="flex flex-col gap-2 w-[360px] max-w-full max-sm:w-full">
            <div class="p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
                <p class="mb-4 text-base text-gray-800 dark:text-white font-semibold">
                    @lang('masterdata::app.icons.form.preview')
                </p>

                <div class="flex items-center gap-4">
                    <span class="flex items-center justify-center w-[46px] h-[46px] rounded-xl bg-primary-50 dark:bg-cherry-800">
                        @include('masterdata::icons._mask', ['url' => $icon->url(), 'vector' => $icon->isVector(), 'class' => 'w-7 h-7 text-primary-600 dark:text-primary-400'])
                    </span>

                    <p class="text-sm text-gray-600 dark:text-gray-300">
                        {{ trans_choice('masterdata::app.icons.form.usage', $usageCount, ['count' => $usageCount]) }}
                    </p>
                </div>
            </div>
        </div>
    @endif
</div>
