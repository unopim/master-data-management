@php
    $isDraft = $version->status === \Webkul\MasterDataManagement\Enums\CodeListVersionStatus::Draft;

    $defaultLocaleCode = config('masterdata.default_locale');
@endphp

<v-masterdata-codes>
    <x-admin::shimmer.datagrid />
</v-masterdata-codes>

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-masterdata-codes-template"
    >
        <div class="mt-3.5 p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
            <div class="flex items-start justify-between gap-4 mb-4 max-md:flex-wrap">
                <div>
                    <p class="text-base text-gray-800 dark:text-white font-semibold">
                        @lang('masterdata::app.code-lists.versions.title')
                    </p>

                    <p class="text-xs text-gray-500 dark:text-gray-300">
                        @lang('masterdata::app.code-lists.versions.info')
                    </p>
                </div>

                <div class="flex items-center gap-2.5 max-sm:flex-wrap">
                    <x-admin::dropdown position="bottom-right">
                        <x-slot:toggle>
                            <button
                                type="button"
                                class="secondary-button"
                            >
                                <span>{{ trans('masterdata::app.code-lists.versions.label', ['version' => $version->version]) }}</span>

                                <span class="icon-chevron-down text-2xl" aria-hidden="true"></span>
                            </button>
                        </x-slot>

                        <x-slot:menu class="!py-1">
                            @foreach ($versions as $item)
                                <li>
                                    <a
                                        href="{{ route('admin.masterdata.code_lists.edit', ['id' => $codeList->id, 'version' => $item->id]) }}"
                                        class="flex items-center justify-between gap-4 px-4 py-2 text-sm text-gray-600 dark:text-gray-300 hover:bg-primary-50 dark:hover:bg-cherry-800 {{ $item->id === $version->id ? 'font-semibold' : '' }}"
                                    >
                                        <span>{{ trans('masterdata::app.code-lists.versions.label', ['version' => $item->version]) }}</span>

                                        <x-admin::badge :variant="$item->status->badgeVariant()">
                                            @lang('masterdata::app.code-lists.versions.statuses.'.$item->status->value)
                                        </x-admin::badge>
                                    </a>
                                </li>
                            @endforeach
                        </x-slot>
                    </x-admin::dropdown>

                    <x-admin::badge :variant="$version->status->badgeVariant()">
                        @lang('masterdata::app.code-lists.versions.statuses.'.$version->status->value)
                    </x-admin::badge>

                    @if (! $hasDraft)
                        <button
                            type="button"
                            class="secondary-button"
                            :disabled="isSubmitting"
                            @click="createDraft"
                        >
                            @lang('masterdata::app.code-lists.versions.new-draft-btn')
                        </button>
                    @endif

                    @if ($isDraft)
                        <button
                            type="button"
                            class="secondary-button"
                            :disabled="isSubmitting"
                            @click="confirmPublish"
                        >
                            @lang('masterdata::app.code-lists.versions.publish-btn')
                        </button>

                        <button
                            type="button"
                            class="primary-button"
                            @click="openModal(null)"
                        >
                            @lang('masterdata::app.codes.create-btn')
                        </button>
                    @endif
                </div>
            </div>

            @if ($version->effective_from)
                <p class="mb-2 text-xs text-gray-500 dark:text-gray-300">
                    @if ($version->effective_to)
                        {{ trans('masterdata::app.code-lists.versions.effective', ['from' => $version->effective_from->toDateString(), 'to' => $version->effective_to->toDateString()]) }}
                    @else
                        {{ trans('masterdata::app.code-lists.versions.effective-open', ['from' => $version->effective_from->toDateString()]) }}
                    @endif
                </p>
            @endif

            @unless ($isDraft)
                <p class="mb-4 text-sm text-gray-600 dark:text-gray-300">
                    {{ trans('masterdata::app.code-lists.versions.read-only', ['status' => mb_strtolower(trans('masterdata::app.code-lists.versions.statuses.'.$version->status->value))]) }}
                </p>
            @endunless

            <x-admin::datagrid
                :src="route('admin.masterdata.codes.index', ['id' => $codeList->id, 'versionId' => $version->id])"
                ref="datagrid"
            >
                <template #body="{ records, performAction }">
                    <div
                        v-for="record in records"
                        class="row grid gap-2.5 items-center px-4 py-4 border-b dark:border-cherry-800 text-gray-600 dark:text-gray-300 transition-all hover:bg-primary-50 hover:bg-opacity-30 dark:hover:bg-cherry-800"
                        :class="{ 'cursor-pointer': isDraft }"
                        :style="`grid-template-columns: repeat(${gridsCount}, minmax(0, 1fr))`"
                        @click="isDraft && openModal(record)"
                    >
                        <p class="truncate" v-text="record.code" :title="record.code"></p>

                        <p class="truncate" v-text="record.label" :title="record.label"></p>

                        <p class="truncate" v-text="record.parent_code"></p>

                        <p v-text="record.sort"></p>

                        <p v-text="record.is_active"></p>

                        <p v-text="record.effective_from"></p>

                        <p v-text="record.effective_to"></p>

                        <div
                            class="flex justify-end"
                            v-if="isDraft"
                            @click.stop
                        >
                            <button
                                type="button"
                                class="icon-edit cursor-pointer rounded-md p-1.5 text-2xl transition-all hover:bg-primary-100 dark:hover:bg-gray-800"
                                title="@lang('masterdata::app.codes.datagrid.edit')"
                                aria-label="@lang('masterdata::app.codes.datagrid.edit')"
                                @click="openModal(record)"
                            ></button>

                            <button
                                type="button"
                                class="icon-delete cursor-pointer rounded-md p-1.5 text-2xl transition-all hover:bg-primary-100 dark:hover:bg-gray-800"
                                title="@lang('masterdata::app.codes.datagrid.delete')"
                                aria-label="@lang('masterdata::app.codes.datagrid.delete')"
                                @click="performAction(record.actions.find(action => action.index === 'delete'))"
                            ></button>
                        </div>
                    </div>
                </template>
            </x-admin::datagrid>

            @if ($isDraft)
                <x-admin::form
                    ::action="formAction"
                    method="POST"
                    ajax="true"
                    :track-dirty="false"
                >
                    <x-admin::modal
                        ref="codeModal"
                        type="large"
                    >
                        <x-slot:header>
                            <p class="text-lg text-gray-800 dark:text-white font-bold">
                                <span v-if="current.id">@lang('masterdata::app.codes.edit.title')</span>

                                <span v-else>@lang('masterdata::app.codes.create.title')</span>
                            </p>
                        </x-slot>

                        <x-slot:content>
                            <x-admin::form.control-group.control
                                type="hidden"
                                name="_method"
                                ::value="current.id ? 'PUT' : 'POST'"
                            />

                            <p class="mb-4 text-base text-gray-800 dark:text-white font-semibold">
                                @lang('masterdata::app.codes.form.general')
                            </p>

                            <div class="grid grid-cols-2 gap-x-4 max-sm:grid-cols-1">
                                <x-admin::form.control-group>
                                    <x-admin::form.control-group.label class="required">
                                        @lang('masterdata::app.codes.form.code')
                                    </x-admin::form.control-group.label>

                                    <x-admin::form.control-group.control
                                        type="text"
                                        name="code"
                                        rules="required"
                                        ::value="current.code"
                                        ::readonly="!! current.id"
                                        :label="trans('masterdata::app.codes.form.code')"
                                        :placeholder="trans('masterdata::app.codes.form.code')"
                                    />

                                    <x-admin::form.control-group.help>
                                        @lang('masterdata::app.help.codes.code')
                                    </x-admin::form.control-group.help>

                                    <x-admin::form.control-group.error control-name="code" />
                                </x-admin::form.control-group>

                                <x-admin::form.control-group>
                                    <x-admin::form.control-group.label>
                                        @lang('masterdata::app.codes.form.parent-code')
                                    </x-admin::form.control-group.label>

                                    <x-admin::form.control-group.control
                                        type="text"
                                        name="parent_code"
                                        ::value="current.parent_code ?? ''"
                                        :label="trans('masterdata::app.codes.form.parent-code')"
                                        :placeholder="trans('masterdata::app.codes.form.parent-code')"
                                    />

                                    <x-admin::form.control-group.help>
                                        @lang('masterdata::app.help.codes.parent-code')
                                    </x-admin::form.control-group.help>

                                    <x-admin::form.control-group.error control-name="parent_code" />
                                </x-admin::form.control-group>

                                <x-admin::form.control-group>
                                    <x-admin::form.control-group.label>
                                        @lang('masterdata::app.codes.form.sort')
                                    </x-admin::form.control-group.label>

                                    <x-admin::form.control-group.control
                                        type="number"
                                        name="sort"
                                        rules="min_value:0"
                                        ::value="current.sort ?? 0"
                                        :label="trans('masterdata::app.codes.form.sort')"
                                    />

                                    <x-admin::form.control-group.help>
                                        @lang('masterdata::app.help.codes.sort')
                                    </x-admin::form.control-group.help>

                                    <x-admin::form.control-group.error control-name="sort" />
                                </x-admin::form.control-group>

                                <x-admin::form.control-group>
                                    <x-admin::form.control-group.label>
                                        @lang('masterdata::app.codes.form.is-active')
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
                                        ::checked="!! current.is_active"
                                        :label="trans('masterdata::app.codes.form.is-active')"
                                    />

                                    <x-admin::form.control-group.help>
                                        @lang('masterdata::app.help.codes.is-active')
                                    </x-admin::form.control-group.help>
                                </x-admin::form.control-group>

                                <x-admin::form.control-group>
                                    <x-admin::form.control-group.label>
                                        @lang('masterdata::app.codes.form.effective-from')
                                    </x-admin::form.control-group.label>

                                    <x-admin::form.control-group.control
                                        type="date"
                                        name="effective_from"
                                        ::value="current.effective_from ?? ''"
                                        :label="trans('masterdata::app.codes.form.effective-from')"
                                    />

                                    <x-admin::form.control-group.help>
                                        @lang('masterdata::app.help.codes.effective-from')
                                    </x-admin::form.control-group.help>

                                    <x-admin::form.control-group.error control-name="effective_from" />
                                </x-admin::form.control-group>

                                <x-admin::form.control-group>
                                    <x-admin::form.control-group.label>
                                        @lang('masterdata::app.codes.form.effective-to')
                                    </x-admin::form.control-group.label>

                                    <x-admin::form.control-group.control
                                        type="date"
                                        name="effective_to"
                                        ::value="current.effective_to ?? ''"
                                        :label="trans('masterdata::app.codes.form.effective-to')"
                                    />

                                    <x-admin::form.control-group.help>
                                        @lang('masterdata::app.help.codes.effective-to')
                                    </x-admin::form.control-group.help>

                                    <x-admin::form.control-group.error control-name="effective_to" />
                                </x-admin::form.control-group>
                            </div>

                            <p class="mb-4 text-base text-gray-800 dark:text-white font-semibold">
                                @lang('masterdata::app.codes.form.labels')
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
                                            :label="trans('masterdata::app.codes.form.label').' ('.$locale->name.')'"
                                            :placeholder="trans('masterdata::app.codes.form.label')"
                                        />

                                        @if ($loop->first)
                                            <x-admin::form.control-group.help>
                                                @lang('masterdata::app.help.codes.labels')
                                            </x-admin::form.control-group.help>
                                        @endif

                                        <x-admin::form.control-group.error control-name="label[{{ $locale->code }}]" />
                                    </x-admin::form.control-group>
                                @endforeach
                            </div>
                        </x-slot>

                        <x-slot:footer>
                            <button
                                type="submit"
                                class="primary-button"
                            >
                                @lang('masterdata::app.codes.form.save-btn')
                            </button>
                        </x-slot>
                    </x-admin::modal>
                </x-admin::form>
            @endif
        </div>
    </script>

    <script type="module">
        app.component('v-masterdata-codes', {
            template: '#v-masterdata-codes-template',

            data() {
                return {
                    current: {},

                    isDraft: @json($isDraft),

                    isSubmitting: false,

                    locales: @json($locales->pluck('code')->values()),
                };
            },

            computed: {
                gridsCount() {
                    let count = this.$refs.datagrid.available.columns.length;

                    if (this.$refs.datagrid.available.actions.length) {
                        ++count;
                    }

                    return count;
                },

                formAction() {
                    return this.current.id
                        ? "{{ route('admin.masterdata.codes.update', ['id' => $codeList->id, 'versionId' => $version->id, 'codeId' => '__id__']) }}".replace('__id__', this.current.id)
                        : "{{ route('admin.masterdata.codes.store', ['id' => $codeList->id, 'versionId' => $version->id]) }}";
                },
            },

            methods: {
                openModal(record) {
                    if (! record) {
                        this.current = { label: {}, is_active: true, sort: 0 };
                    } else {
                        this.current = {
                            id: record.id,
                            code: record.code,
                            parent_code: record.parent_code,
                            sort: record.sort,
                            is_active: !! Number(record.is_active_value),
                            effective_from: record.effective_from,
                            effective_to: record.effective_to,
                            label: Object.fromEntries(this.locales.map((locale) => [locale, record['label_' + locale] ?? ''])),
                        };
                    }

                    this.$refs.codeModal.open();
                },

                createDraft() {
                    this.submit("{{ route('admin.masterdata.code_lists.versions.store', $codeList->id) }}");
                },

                confirmPublish() {
                    this.$emitter.emit('open-confirm-modal', {
                        title: @json(trans('masterdata::app.code-lists.versions.confirm-title')),
                        message: @json(trans('masterdata::app.code-lists.versions.confirm-message', ['version' => $version->version])),
                        agree: () => this.submit("{{ route('admin.masterdata.code_lists.versions.publish', ['id' => $codeList->id, 'versionId' => $version->id]) }}"),
                    });
                },

                submit(url) {
                    this.isSubmitting = true;

                    this.$axios.post(url)
                        .then((response) => this.$navigate(response.data.redirect_url))
                        .catch((error) => {
                            this.isSubmitting = false;

                            this.$emitter.emit('add-flash', { type: 'error', message: error.response?.data?.message });
                        });
                },
            },
        });
    </script>
@endPushOnce
