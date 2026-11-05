@php
    $principalType = old('principal_type') ?? ($grant->principal_type?->value ?? \Webkul\MasterDataManagement\Enums\GrantPrincipal::Role->value);
    $isRole = $principalType === \Webkul\MasterDataManagement\Enums\GrantPrincipal::Role->value;
@endphp

<div class="flex gap-2.5 mt-3.5 max-xl:flex-wrap">
    <div class="flex flex-col gap-2 flex-1 max-xl:flex-auto">
        <div class="p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
            <x-admin::heading
                class="mb-4"
                :title="trans('masterdata::app.access.form.principal')"
                :subtitle="trans('masterdata::app.access.form.principal-info')"
            />

            <v-access-grant-principal></v-access-grant-principal>
        </div>

        <div class="p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
            <x-admin::heading
                class="mb-4"
                :title="trans('masterdata::app.access.form.scope')"
                :subtitle="trans('masterdata::app.access.form.scope-info')"
            />

            <x-admin::form.control-group>
                <x-admin::form.control-group.label class="required">
                    @lang('masterdata::app.access.form.root')
                </x-admin::form.control-group.label>

                <x-admin::form.control-group.control
                    type="select"
                    name="record"
                    async="true"
                    :list-route="route('admin.masterdata.access.options')"
                    rules="required"
                    :value="old('record') ?? $grant->record?->uuid"
                    :label="trans('masterdata::app.access.form.root')"
                    :placeholder="trans('masterdata::app.access.form.root')"
                    track-by="id"
                    label-by="label"
                />

                <x-admin::form.control-group.help>
                    @lang('masterdata::app.access.form.root-info')
                </x-admin::form.control-group.help>

                <x-admin::form.control-group.error control-name="record" />
            </x-admin::form.control-group>

            <x-admin::form.control-group>
                <x-admin::form.control-group.label>
                    @lang('masterdata::app.access.form.entity-type')
                </x-admin::form.control-group.label>

                <x-admin::form.control-group.control
                    type="select"
                    name="entity_type_id"
                    :value="old('entity_type_id') ?? ($grant->entity_type_id ?? '')"
                    :label="trans('masterdata::app.access.form.entity-type')"
                    :placeholder="trans('masterdata::app.access.form.any-entity-type')"
                    :options="json_encode($entityTypeOptions)"
                    track-by="id"
                    label-by="label"
                />

                <x-admin::form.control-group.help>
                    @lang('masterdata::app.access.form.entity-type-info')
                </x-admin::form.control-group.help>

                <x-admin::form.control-group.error control-name="entity_type_id" />
            </x-admin::form.control-group>

            <x-admin::form.control-group class="!mb-0">
                <x-admin::form.control-group.label class="required">
                    @lang('masterdata::app.access.form.actions')
                </x-admin::form.control-group.label>

                <x-admin::form.control-group.control
                    type="multiselect"
                    name="actions"
                    rules="required"
                    :value="json_encode(array_values((array) (old('actions') ?? $grant->actions ?? [])))"
                    :label="trans('masterdata::app.access.form.actions')"
                    :placeholder="trans('masterdata::app.access.form.actions')"
                    :options="json_encode($actionOptions)"
                    track-by="id"
                    label-by="label"
                />

                <x-admin::form.control-group.help>
                    @lang('masterdata::app.access.form.actions-info')
                </x-admin::form.control-group.help>

                <x-admin::form.control-group.error control-name="actions" />
            </x-admin::form.control-group>
        </div>
    </div>
</div>

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-access-grant-principal-template"
    >
        <div>
            <x-admin::form.control-group>
                <x-admin::form.control-group.label class="required">
                    @lang('masterdata::app.access.form.principal-type')
                </x-admin::form.control-group.label>

                <x-admin::form.control-group.control
                    type="select"
                    name="principal_type"
                    rules="required"
                    :value="$principalType"
                    :label="trans('masterdata::app.access.form.principal-type')"
                    :options="json_encode($principalOptions)"
                    track-by="id"
                    label-by="label"
                    @input="updateType($event)"
                />

                <x-admin::form.control-group.error control-name="principal_type" />
            </x-admin::form.control-group>

            <x-admin::form.control-group class="!mb-0" v-show="type === '{{ \Webkul\MasterDataManagement\Enums\GrantPrincipal::Role->value }}'">
                <x-admin::form.control-group.label class="required">
                    @lang('masterdata::app.access.form.role')
                </x-admin::form.control-group.label>

                <x-admin::form.control-group.control
                    type="select"
                    name="role_id"
                    :value="old('role_id') ?? ($isRole ? $grant->principal_id : '')"
                    :label="trans('masterdata::app.access.form.role')"
                    :placeholder="trans('masterdata::app.access.form.role')"
                    :options="json_encode($roleOptions)"
                    track-by="id"
                    label-by="label"
                />

                <x-admin::form.control-group.error control-name="role_id" />
            </x-admin::form.control-group>

            <x-admin::form.control-group class="!mb-0" v-show="type === '{{ \Webkul\MasterDataManagement\Enums\GrantPrincipal::User->value }}'">
                <x-admin::form.control-group.label class="required">
                    @lang('masterdata::app.access.form.user')
                </x-admin::form.control-group.label>

                <x-admin::form.control-group.control
                    type="select"
                    name="admin_id"
                    :value="old('admin_id') ?? ($isRole ? '' : $grant->principal_id)"
                    :label="trans('masterdata::app.access.form.user')"
                    :placeholder="trans('masterdata::app.access.form.user')"
                    :options="json_encode($userOptions)"
                    track-by="id"
                    label-by="label"
                />

                <x-admin::form.control-group.error control-name="admin_id" />
            </x-admin::form.control-group>
        </div>
    </script>

    <script type="module">
        app.component('v-access-grant-principal', {
            template: '#v-access-grant-principal-template',

            data() {
                return {
                    type: @json($principalType),
                };
            },

            methods: {
                updateType(value) {
                    try {
                        this.type = JSON.parse(value)?.id ?? value;
                    } catch (error) {
                        this.type = value;
                    }
                },
            },
        });
    </script>
@endPushOnce
