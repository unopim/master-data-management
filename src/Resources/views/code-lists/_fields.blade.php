@php
    $defaultLocaleCode = config('masterdata.default_locale');
@endphp

<div class="flex gap-2.5 mt-3.5 max-xl:flex-wrap">
    <div class="flex flex-col gap-2 flex-1 max-xl:flex-auto">
        <div class="p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
            <p class="mb-4 text-base text-gray-800 dark:text-white font-semibold">
                @lang('masterdata::app.code-lists.form.general')
            </p>

            @if ($codeList->exists)
                <x-admin::form.control-group class="!mb-0">
                    <x-admin::form.control-group.label>
                        @lang('masterdata::app.code-lists.form.code')
                    </x-admin::form.control-group.label>

                    <x-admin::form.control-group.control
                        type="text"
                        name="code_display"
                        :value="$codeList->code"
                        :label="trans('masterdata::app.code-lists.form.code')"
                        disabled="disabled"
                    />

                    <x-admin::form.control-group.help>
                        @lang('masterdata::app.help.code-lists.code')
                    </x-admin::form.control-group.help>
                </x-admin::form.control-group>
            @else
                <x-admin::form.control-group class="!mb-0">
                    <x-admin::form.control-group.label class="required">
                        @lang('masterdata::app.code-lists.form.code')
                    </x-admin::form.control-group.label>

                    <x-admin::form.control-group.control
                        type="text"
                        name="code"
                        rules="required"
                        :value="old('code')"
                        :label="trans('masterdata::app.code-lists.form.code')"
                        :placeholder="trans('masterdata::app.code-lists.form.code')"
                    />

                    <x-admin::form.control-group.help>
                        @lang('masterdata::app.help.code-lists.code')
                    </x-admin::form.control-group.help>

                    <x-admin::form.control-group.error control-name="code" />
                </x-admin::form.control-group>
            @endif
        </div>

        @foreach ($locales as $locale)
            @php
                $translation = $codeList->translations->firstWhere('locale', $locale->code);
                $isDefaultLocale = $locale->code === $defaultLocaleCode;
            @endphp

            <div class="p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
                <p class="mb-4 text-base text-gray-800 dark:text-white font-semibold">
                    {{ $locale->name }}
                </p>

                <x-admin::form.control-group>
                    <x-admin::form.control-group.label class="{{ $isDefaultLocale ? 'required' : '' }}">
                        @lang('masterdata::app.code-lists.form.name')
                    </x-admin::form.control-group.label>

                    <x-admin::form.control-group.control
                        type="text"
                        name="translations[{{ $locale->code }}][name]"
                        :rules="$isDefaultLocale ? 'required' : ''"
                        :value="old('translations.'.$locale->code.'.name') ?? $translation?->name"
                        :label="trans('masterdata::app.code-lists.form.name')"
                        :placeholder="trans('masterdata::app.code-lists.form.name')"
                    />

                    @if ($loop->first)
                        <x-admin::form.control-group.help>
                            @lang('masterdata::app.help.code-lists.name')
                        </x-admin::form.control-group.help>
                    @endif

                    <x-admin::form.control-group.error control-name="translations[{{ $locale->code }}][name]" />
                </x-admin::form.control-group>

                <x-admin::form.control-group class="!mb-0">
                    <x-admin::form.control-group.label>
                        @lang('masterdata::app.code-lists.form.description')
                    </x-admin::form.control-group.label>

                    <x-admin::form.control-group.control
                        type="textarea"
                        name="translations[{{ $locale->code }}][description]"
                        :value="old('translations.'.$locale->code.'.description') ?? $translation?->description"
                        :label="trans('masterdata::app.code-lists.form.description')"
                        :placeholder="trans('masterdata::app.code-lists.form.description')"
                    />

                    @if ($loop->first)
                        <x-admin::form.control-group.help>
                            @lang('masterdata::app.help.code-lists.description')
                        </x-admin::form.control-group.help>
                    @endif

                    <x-admin::form.control-group.error control-name="translations[{{ $locale->code }}][description]" />
                </x-admin::form.control-group>
            </div>
        @endforeach
    </div>

    <div class="flex flex-col gap-2 w-[360px] max-w-full max-sm:w-full">
        <div class="p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
            <p class="mb-4 text-base text-gray-800 dark:text-white font-semibold">
                @lang('masterdata::app.code-lists.form.settings')
            </p>

            <x-admin::form.control-group>
                <x-admin::form.control-group.label>
                    @lang('masterdata::app.code-lists.form.owner')
                </x-admin::form.control-group.label>

                <x-admin::form.control-group.control
                    type="text"
                    name="owner"
                    :value="old('owner') ?? $codeList->owner"
                    :label="trans('masterdata::app.code-lists.form.owner')"
                    :placeholder="trans('masterdata::app.code-lists.form.owner')"
                />

                <x-admin::form.control-group.help>
                    @lang('masterdata::app.help.code-lists.owner')
                </x-admin::form.control-group.help>

                <x-admin::form.control-group.error control-name="owner" />
            </x-admin::form.control-group>

            <x-admin::form.control-group class="!mb-0">
                <x-admin::form.control-group.label>
                    @lang('masterdata::app.code-lists.form.is-versioned')
                </x-admin::form.control-group.label>

                <x-admin::form.control-group.control
                    type="hidden"
                    name="is_versioned"
                    value="0"
                />

                <x-admin::form.control-group.control
                    type="switch"
                    name="is_versioned"
                    value="1"
                    :label="trans('masterdata::app.code-lists.form.is-versioned')"
                    :checked="(bool) (old('is_versioned') ?? $codeList->is_versioned)"
                />

                <x-admin::form.control-group.help>
                    @lang('masterdata::app.help.code-lists.is-versioned')
                </x-admin::form.control-group.help>
            </x-admin::form.control-group>
        </div>
    </div>
</div>
