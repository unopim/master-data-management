@php
    $defaultLocaleCode = config('masterdata.default_locale');

    $validationTypeOptions = collect(\Webkul\Attribute\Rules\ValidationTypes::VALIDATION_TYPES)
        ->map(fn (string $type): array => ['id' => $type, 'label' => trans('masterdata::app.fields.validation-types.'.$type)])
        ->values();
@endphp

<x-admin::form
    ::action="formAction"
    method="POST"
    ajax="true"
    :track-dirty="false"
>
    <x-admin::modal
        ref="fieldModal"
        type="large"
    >
        <x-slot:header>
            <p class="text-lg text-gray-800 dark:text-white font-bold">
                <span v-if="current.id">@lang('masterdata::app.fields.edit.title')</span>

                <span v-else>@lang('masterdata::app.fields.create.title')</span>
            </p>
        </x-slot>

        <x-slot:content>
            <x-admin::form.control-group.control
                type="hidden"
                name="_method"
                ::value="current.id ? 'PUT' : 'POST'"
            />

            <p class="mb-4 text-base text-gray-800 dark:text-white font-semibold">
                @lang('masterdata::app.fields.form.general')
            </p>

            <div class="grid grid-cols-2 gap-x-4 max-sm:grid-cols-1">
                <x-admin::form.control-group>
                    <x-admin::form.control-group.label class="required">
                        @lang('masterdata::app.fields.form.code')
                    </x-admin::form.control-group.label>

                    <x-admin::form.control-group.control
                        type="text"
                        name="code"
                        rules="required"
                        ::value="current.code"
                        ::readonly="!! current.id"
                        :label="trans('masterdata::app.fields.form.code')"
                        :placeholder="trans('masterdata::app.fields.form.code')"
                    />

                    <x-admin::form.control-group.help>
                        @lang('masterdata::app.help.fields.code')
                    </x-admin::form.control-group.help>

                    <x-admin::form.control-group.error control-name="code" />
                </x-admin::form.control-group>

                <x-admin::form.control-group>
                    <x-admin::form.control-group.label class="required">
                        @lang('masterdata::app.fields.form.type')
                    </x-admin::form.control-group.label>

                    <x-admin::form.control-group.control
                        type="select"
                        name="type"
                        rules="required"
                        ::value="current.type"
                        ::disabled="!! current.id"
                        :label="trans('masterdata::app.fields.form.type')"
                        :placeholder="trans('masterdata::app.fields.form.select-type')"
                        :options="json_encode($fieldTypeOptions)"
                        track-by="id"
                        label-by="label"
                        @input="onTypeChange($event)"
                    />

                    <x-admin::form.control-group.help>
                        @lang('masterdata::app.help.fields.type')
                    </x-admin::form.control-group.help>

                    <x-admin::form.control-group.error control-name="type" />
                </x-admin::form.control-group>

                <x-admin::form.control-group v-if="usesCodeList">
                    <x-admin::form.control-group.label class="required">
                        @lang('masterdata::app.fields.form.code-list')
                    </x-admin::form.control-group.label>

                    <x-admin::form.control-group.control
                        type="select"
                        name="code_list_id"
                        rules="required"
                        ::value="current.code_list_id"
                        :label="trans('masterdata::app.fields.form.code-list')"
                        :placeholder="trans('masterdata::app.fields.form.select-code-list')"
                        async="true"
                        :list-route="route('admin.masterdata.code_lists.options')"
                        track-by="id"
                        label-by="label"
                    />

                    <x-admin::form.control-group.help>
                        @lang('masterdata::app.help.fields.code-list')
                    </x-admin::form.control-group.help>

                    <x-admin::form.control-group.error control-name="code_list_id" />
                </x-admin::form.control-group>

                <x-admin::form.control-group v-if="fieldType === 'reference'">
                    <x-admin::form.control-group.label class="required">
                        @lang('masterdata::app.fields.form.reference-type')
                    </x-admin::form.control-group.label>

                    <x-admin::form.control-group.control
                        type="select"
                        name="reference_entity_type_id"
                        rules="required"
                        ::value="current.reference_entity_type_id"
                        ::disabled="!! current.reference_locked"
                        :label="trans('masterdata::app.fields.form.reference-type')"
                        :placeholder="trans('masterdata::app.fields.form.select-reference-type')"
                        :options="json_encode($referenceTypeOptions)"
                        track-by="id"
                        label-by="label"
                    />

                    <x-admin::form.control-group.help>
                        @lang('masterdata::app.help.fields.reference-type')
                    </x-admin::form.control-group.help>

                    <x-admin::form.control-group.error control-name="reference_entity_type_id" />

                    <x-admin::form.control-group.help
                        v-if="current.reference_locked"
                    >
                        @lang('masterdata::app.fields.form.reference-locked')
                    </x-admin::form.control-group.help>
                </x-admin::form.control-group>

                <x-admin::form.control-group>
                    <x-admin::form.control-group.label>
                        @lang('masterdata::app.fields.form.group')
                    </x-admin::form.control-group.label>

                    <x-admin::form.control-group.control
                        type="text"
                        name="group"
                        ::value="current.group"
                        :label="trans('masterdata::app.fields.form.group')"
                        :placeholder="trans('masterdata::app.fields.form.group')"
                    />

                    <x-admin::form.control-group.help>
                        @lang('masterdata::app.help.fields.group')
                    </x-admin::form.control-group.help>

                    <x-admin::form.control-group.error control-name="group" />
                </x-admin::form.control-group>

                <x-admin::form.control-group>
                    <x-admin::form.control-group.label>
                        @lang('masterdata::app.fields.form.position')
                    </x-admin::form.control-group.label>

                    <x-admin::form.control-group.control
                        type="number"
                        name="position"
                        rules="min_value:0"
                        ::value="current.position ?? 0"
                        :label="trans('masterdata::app.fields.form.position')"
                    />

                    <x-admin::form.control-group.help>
                        @lang('masterdata::app.help.fields.position')
                    </x-admin::form.control-group.help>

                    <x-admin::form.control-group.error control-name="position" />
                </x-admin::form.control-group>
            </div>

            <p class="mb-4 text-base text-gray-800 dark:text-white font-semibold">
                @lang('masterdata::app.fields.form.labels')
            </p>

            <div class="grid grid-cols-2 gap-x-4 max-sm:grid-cols-1">
                @foreach ($locales as $locale)
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label class="{{ $locale->code === $defaultLocaleCode ? 'required' : '' }}">
                            {{ $locale->name }}
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="text"
                            name="label[{{ $locale->code }}]"
                            :rules="$locale->code === $defaultLocaleCode ? 'required' : ''"
                            ::value="current.label?.['{{ $locale->code }}'] ?? ''"
                            :label="trans('masterdata::app.fields.form.label').' ('.$locale->name.')'"
                            :placeholder="trans('masterdata::app.fields.form.label')"
                        />

                        @if ($loop->first)
                            <x-admin::form.control-group.help>
                                @lang('masterdata::app.help.fields.labels')
                            </x-admin::form.control-group.help>
                        @endif

                        <x-admin::form.control-group.error control-name="label[{{ $locale->code }}]" />
                    </x-admin::form.control-group>
                @endforeach
            </div>

            <p class="mb-4 text-base text-gray-800 dark:text-white font-semibold">
                @lang('masterdata::app.fields.form.behavior')
            </p>

            <div class="grid grid-cols-4 gap-x-4 max-md:grid-cols-2">
                @foreach (\Webkul\MasterDataManagement\Services\SchemaPublisher::FLAGS as $flag)
                    <x-admin::form.control-group
                        :v-if="$flag === 'is_archived' ? 'current.id' : null"
                    >
                        <x-admin::form.control-group.label>
                            @lang('masterdata::app.fields.form.flags.'.$flag)
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="hidden"
                            :name="$flag"
                            value="0"
                            ::disabled="{{ $flag === 'multiple' ? '!! current.reference_locked' : 'false' }}"
                        />

                        <x-admin::form.control-group.control
                            type="switch"
                            :name="$flag"
                            value="1"
                            ::checked="!! current.{{ $flag }}"
                            ::disabled="{{ $flag === 'multiple' ? '!! current.reference_locked' : 'false' }}"
                            :label="trans('masterdata::app.fields.form.flags.'.$flag)"
                        />

                        <x-admin::form.control-group.help>
                            @lang('masterdata::app.help.fields.'.$flag)
                        </x-admin::form.control-group.help>
                    </x-admin::form.control-group>
                @endforeach
            </div>

            <p class="mb-4 text-base text-gray-800 dark:text-white font-semibold">
                @lang('masterdata::app.fields.form.validation')
            </p>

            <div class="grid grid-cols-2 gap-x-4 max-sm:grid-cols-1">
                <x-admin::form.control-group>
                    <x-admin::form.control-group.label>
                        @lang('masterdata::app.fields.form.validation-type')
                    </x-admin::form.control-group.label>

                    <x-admin::form.control-group.control
                        type="select"
                        name="validation[type]"
                        ::value="current.validation?.type"
                        :label="trans('masterdata::app.fields.form.validation-type')"
                        :placeholder="trans('masterdata::app.fields.form.select-validation-type')"
                        :options="json_encode($validationTypeOptions)"
                        track-by="id"
                        label-by="label"
                    />

                    <x-admin::form.control-group.help>
                        @lang('masterdata::app.help.fields.validation-type')
                    </x-admin::form.control-group.help>

                    <x-admin::form.control-group.error control-name="validation[type]" />
                </x-admin::form.control-group>

                <x-admin::form.control-group>
                    <x-admin::form.control-group.label>
                        @lang('masterdata::app.fields.form.validation-regex')
                    </x-admin::form.control-group.label>

                    <x-admin::form.control-group.control
                        type="text"
                        name="validation[regex]"
                        ::value="current.validation?.regex ?? ''"
                        :label="trans('masterdata::app.fields.form.validation-regex')"
                        :placeholder="trans('masterdata::app.fields.form.validation-regex')"
                    />

                    <x-admin::form.control-group.help>
                        @lang('masterdata::app.help.fields.validation-regex')
                    </x-admin::form.control-group.help>

                    <x-admin::form.control-group.error control-name="validation[regex]" />
                </x-admin::form.control-group>

                @foreach (['min', 'max', 'max_length'] as $bound)
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label>
                            @lang('masterdata::app.fields.form.validation-'.str_replace('_', '-', $bound))
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="number"
                            name="validation[{{ $bound }}]"
                            ::value="current.validation?.{{ $bound }} ?? ''"
                            :label="trans('masterdata::app.fields.form.validation-'.str_replace('_', '-', $bound))"
                        />

                        <x-admin::form.control-group.help>
                            @lang('masterdata::app.help.fields.validation-'.str_replace('_', '-', $bound))
                        </x-admin::form.control-group.help>

                        <x-admin::form.control-group.error control-name="validation[{{ $bound }}]" />
                    </x-admin::form.control-group>
                @endforeach
            </div>
        </x-slot>

        <x-slot:footer>
            <button
                type="submit"
                class="primary-button"
            >
                @lang('masterdata::app.fields.form.save-btn')
            </button>
        </x-slot>
    </x-admin::modal>
</x-admin::form>
