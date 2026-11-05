<x-admin::form.control-group>
    <x-admin::form.control-group.label
        :class="$field['required'] ? 'required' : ''"
        :localizable="$field['locale'] !== null ? 'true' : null"
        :current-locale-code="$field['locale']"
    >
        {{ $field['label'] }}
    </x-admin::form.control-group.label>

    @switch ($field['control'])
        @case('textarea')
        @case('date')
        @case('datetime')
        @case('text')
            <x-admin::form.control-group.control
                :type="$field['control']"
                :name="$field['name']"
                :value="$field['display']"
                :rules="$field['required'] ? 'required' : null"
                :label="$field['label']"
            />

            @break

        @case('number')
            <x-admin::form.control-group.control
                type="number"
                step="any"
                :name="$field['name']"
                :value="$field['display']"
                :rules="$field['required'] ? 'required' : null"
                :label="$field['label']"
            />

            @break

        @case('switch')
            <x-admin::form.control-group.control
                type="hidden"
                :name="$field['name']"
                value="0"
            />

            <x-admin::form.control-group.control
                type="switch"
                :name="$field['name']"
                value="1"
                :label="$field['label']"
                :checked="(bool) $field['value']"
            />

            @break

        @case('select')
            <x-admin::form.control-group.control
                type="select"
                :name="$field['name']"
                :value="$field['display']"
                :rules="$field['required'] ? 'required' : null"
                :label="$field['label']"
                :placeholder="trans('masterdata::app.records.form.select-option')"
                :options="json_encode($field['options'])"
                track-by="id"
                label-by="label"
            />

            @break

        @case('multiselect')
            <x-admin::form.control-group.control
                type="hidden"
                :name="$field['name']"
                value="[]"
            />

            <x-admin::form.control-group.control
                type="multiselect"
                :name="$field['name']"
                :value="json_encode(array_values((array) ($field['value'] ?? [])))"
                :label="$field['label']"
                :placeholder="trans('masterdata::app.records.form.select-option')"
                :options="json_encode($field['options'])"
                track-by="id"
                label-by="label"
            />

            @break

        @case('reference')
            <x-admin::form.control-group.control
                :type="$field['multiple'] ? 'multiselect' : 'select'"
                :name="$field['name']"
                async="true"
                :list-route="$field['route']"
                :query-params="json_encode($field['query'])"
                :value="$field['value']"
                :rules="$field['required'] ? 'required' : null"
                :label="$field['label']"
                :placeholder="trans('masterdata::app.records.form.select-record')"
                track-by="id"
                label-by="label"
            />

            @break

        @case('measurement')
            <div class="grid grid-cols-2 gap-2.5">
                <x-admin::form.control-group.control
                    type="text"
                    :name="$field['name'].'[value]'"
                    :value="$field['value']['value'] ?? null"
                    :label="trans('masterdata::app.records.form.measurement-value')"
                    :placeholder="trans('masterdata::app.records.form.measurement-value')"
                />

                <x-admin::form.control-group.control
                    type="text"
                    :name="$field['name'].'[unit]'"
                    :value="$field['value']['unit'] ?? null"
                    :label="trans('masterdata::app.records.form.measurement-unit')"
                    :placeholder="trans('masterdata::app.records.form.measurement-unit')"
                />
            </div>

            @break

        @default
            <p class="text-sm text-gray-600 dark:text-gray-300">
                {{ $field['display'] }}
            </p>

            <p class="text-xs text-gray-500 dark:text-gray-400">
                @lang('masterdata::app.records.form.no-control')
            </p>
    @endswitch

    <x-admin::form.control-group.error :control-name="$field['name']" />

    @if ($field['help'])
        <x-admin::form.control-group.help>
            {{ $field['help'] }}
        </x-admin::form.control-group.help>
    @endif
</x-admin::form.control-group>
