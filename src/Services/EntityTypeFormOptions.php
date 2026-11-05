<?php

namespace Webkul\MasterDataManagement\Services;

use BackedEnum;
use Illuminate\Database\Eloquent\Collection;
use Webkul\Core\Repositories\LocaleRepository;
use Webkul\MasterDataManagement\Enums\CodeStrategy;
use Webkul\MasterDataManagement\Enums\EntityTypeStatus;
use Webkul\MasterDataManagement\Enums\MasteringStyle;
use Webkul\MasterDataManagement\Enums\RelationCardinality;
use Webkul\MasterDataManagement\FieldTypes\FieldTypeRegistry;
use Webkul\MasterDataManagement\Http\Requests\EntityTypeRequest;
use Webkul\MasterDataManagement\Models\EntityType;
use Webkul\MasterDataManagement\Models\Field;
use Webkul\MasterDataManagement\Models\Icon;
use Webkul\MasterDataManagement\Models\RelationType;
use Webkul\MasterDataManagement\Quality\CompletenessCalculator;
use Webkul\MasterDataManagement\Quality\Rules\RequiredXrefRule;
use Webkul\MasterDataManagement\Repositories\EntityTypeRepository;
use Webkul\MasterDataManagement\Repositories\FieldRepository;
use Webkul\MasterDataManagement\Repositories\IconRepository;
use Webkul\MasterDataManagement\Repositories\RelationRepository;
use Webkul\MasterDataManagement\Repositories\RelationTypeRepository;
use Webkul\MasterDataManagement\Repositories\SourceSystemRepository;
use Webkul\User\Models\Role;
use Webkul\User\Repositories\AdminRepository;
use Webkul\User\Repositories\RoleRepository;

/**
 * Builds the select options and view data of the entity type create, edit, fields and permissions screens.
 *
 * Entity types, owners and custom roles are small, admin-maintained sets, so they load eagerly up to `OPTIONS_CAP`;
 * code lists and source systems grow with the data and load through their async option endpoints.
 */
class EntityTypeFormOptions
{
    /**
     * The most rows an eagerly loaded option list holds.
     */
    public const int OPTIONS_CAP = 500;

    public function __construct(
        protected EntityTypeRepository $entityTypeRepository,
        protected LocaleRepository $localeRepository,
        protected SourceSystemRepository $sourceSystemRepository,
        protected FieldRepository $fieldRepository,
        protected IconRepository $iconRepository,
        protected FieldTypeRegistry $fieldTypeRegistry,
        protected AdminRepository $adminRepository,
        protected RoleRepository $roleRepository,
        protected RelationTypeRepository $relationTypeRepository,
        protected RelationRepository $relationRepository,
    ) {}

    /**
     * The shared create/edit form view data.
     *
     * @return array<string, mixed>
     */
    public function formData(EntityType $entityType): array
    {
        return [
            'entityType'               => $entityType,
            'locales'                  => $this->localeRepository->getActiveLocales(),
            'parentOptions'            => $this->entityTypeRepository->codeOptions(self::OPTIONS_CAP, $entityType->id),
            'styleOptions'             => $this->enumOptions(MasteringStyle::cases(), 'masterdata::app.entity-types.mastering-styles'),
            'strategyOptions'          => $this->enumOptions(CodeStrategy::cases(), 'masterdata::app.entity-types.code-strategies'),
            'statusOptions'            => $this->enumOptions(EntityTypeStatus::cases(), 'masterdata::app.entity-types.statuses'),
            'requiredSystemOptions'    => $this->sourceSystemRepository->optionsFor(RequiredXrefRule::requiredSystemIds($entityType)),
            'ownerOptions'             => $this->ownerOptions(),
            'canChangeGovernance'      => bouncer()->hasPermission(EntityTypeRequest::GOVERNANCE_ACL),
            'iconOptions'              => $this->iconOptions(),
            'labelFieldOptions'        => $this->labelFieldOptions($entityType),
            'nextSequence'             => $this->entityTypeRepository->nextSequenceValue($entityType),
            'completenessScopeOptions' => array_map(
                fn (string $scope): array => ['id' => $scope, 'label' => trans('masterdata::app.entity-types.completeness-scopes.'.$scope)],
                [CompletenessCalculator::SCOPE_ALL, CompletenessCalculator::SCOPE_REQUIRED],
            ),
        ];
    }

    /**
     * The listing's quick-create modal view data: only what a new type needs before its edit page takes over.
     *
     * @return array<string, mixed>
     */
    public function quickCreateData(EntityType $entityType): array
    {
        return [
            'defaultLocaleCode' => core()->getDefaultLocaleCodeFromDefaultChannel(),
            'styleOptions'      => $this->enumOptions(MasteringStyle::cases(), 'masterdata::app.entity-types.mastering-styles'),
            'strategyOptions'   => $this->enumOptions(CodeStrategy::cases(), 'masterdata::app.entity-types.code-strategies'),
            'newEntityType'     => $entityType,
        ];
    }

    /**
     * The fields tab view data: the select options and every field's editable state, keyed by id.
     *
     * A reference field whose relations exist is marked `reference_locked`: its target type and cardinality are fixed.
     *
     * @return array<string, mixed>
     */
    public function fieldFormData(EntityType $entityType): array
    {
        $fields = $this->fieldRepository->allFor($entityType->id);

        $locked = array_flip($this->relationRepository->fieldIdsWithRelations(
            $fields->where('type', 'reference')->pluck('id')->map(intval(...))->values()->all()
        ));

        return [
            'fieldTypeOptions' => array_map(
                fn (string $code): array => [
                    'id'    => $code,
                    'label' => trans()->has('masterdata::app.fields.types.'.$code) ? trans('masterdata::app.fields.types.'.$code) : $code,
                ],
                $this->fieldTypeRegistry->codes()
            ),
            'referenceTypeOptions' => $this->entityTypeRepository->codeOptions(self::OPTIONS_CAP),
            'fieldsById'           => $fields
                ->mapWithKeys(fn (Field $field): array => [$field->id => [
                    'reference_locked'         => isset($locked[$field->id]),
                    'id'                       => $field->id,
                    'code'                     => $field->code,
                    'type'                     => $field->type,
                    'group'                    => $field->group,
                    'position'                 => $field->position,
                    'code_list_id'             => $field->code_list_id,
                    'reference_entity_type_id' => $field->reference_entity_type_id,
                    'validation'               => $field->validation ?? [],
                    'label'                    => $field->translations->pluck('label', 'locale')->all(),
                ] + array_combine(SchemaPublisher::FLAGS, array_map($field->getAttribute(...), SchemaPublisher::FLAGS))]),
        ];
    }

    /**
     * The relations tab view data: the target type and cardinality options and every relation type's editable state,
     * keyed by id.
     *
     * @return array<string, mixed>
     */
    public function relationTypeFormData(EntityType $entityType): array
    {
        return [
            'targetTypeOptions'  => $this->entityTypeRepository->codeOptions(self::OPTIONS_CAP),
            'cardinalityOptions' => $this->enumOptions(RelationCardinality::cases(), 'masterdata::app.relation-types.cardinalities'),
            'relationTypesById'  => $this->relationTypeRepository->fromEntityType($entityType->id, self::OPTIONS_CAP)
                ->map(fn (RelationType $type): array => [
                    'id'                => $type->id,
                    'code'              => $type->code,
                    'to_entity_type_id' => $type->to_entity_type_id,
                    'cardinality'       => $type->cardinality->value,
                    'is_system'         => $type->is_system,
                    'label'             => $type->translations->pluck('label', 'locale')->all(),
                ]),
        ];
    }

    /**
     * The custom roles, which are the only roles that need per-entity-type grants.
     *
     * @return Collection<int, Role>
     */
    public function customRoles(): Collection
    {
        return $this->roleRepository
            ->scopeQuery(fn ($query) => $query->where('permission_type', 'custom')->orderBy('name')->limit(self::OPTIONS_CAP))
            ->get(['id', 'name']);
    }

    /**
     * The icons an entity type can show on its dashboard card: "no icon", then the icon library, then the admin font icons.
     *
     * @return list<array{id: string, label: string, icon?: string}>
     */
    protected function iconOptions(): array
    {
        $icons = array_map(
            fn (string $icon): array => [
                'id'    => $icon,
                'label' => trans('masterdata::app.entity-types.icons.'.substr($icon, 5)),
                'icon'  => $icon,
            ],
            config('masterdata.entity_type_icons', []),
        );

        $library = $this->iconRepository->allByName()->map(fn (Icon $icon): array => [
            'id'       => EntityTypeRequest::LIBRARY_ICON_PREFIX.$icon->id,
            'label'    => $icon->name,
            'iconUrl'  => $icon->url(),
            'iconMask' => $icon->isVector(),
        ])->all();

        return [['id' => '', 'label' => trans('masterdata::app.entity-types.form.no-icon')], ...$library, ...$icons];
    }

    /**
     * The type's active fields as label field select options; a type not yet saved has none.
     *
     * @return list<array{id: string, label: string}>
     */
    protected function labelFieldOptions(EntityType $entityType): array
    {
        if (! $entityType->exists) {
            return [];
        }

        return $this->fieldRepository->activeFor((int) $entityType->id)
            ->map(fn (Field $field): array => ['id' => $field->code, 'label' => $field->label ?: $field->code])
            ->values()
            ->all();
    }

    /**
     * The active admins who can own a type's data, as select options led by "no owner".
     *
     * @return list<array{id: int|string, label: string}>
     */
    protected function ownerOptions(): array
    {
        return [['id' => '', 'label' => trans('masterdata::app.entity-types.form.no-owner')], ...$this->adminOptions()];
    }

    /**
     * Select options for the active admins, by name, up to `OPTIONS_CAP`.
     *
     * @return list<array{id: int, label: string}>
     */
    public function adminOptions(): array
    {
        return $this->adminRepository
            ->scopeQuery(fn ($query) => $query->where('status', 1)->orderBy('name')->limit(self::OPTIONS_CAP))
            ->get(['id', 'name', 'email'])
            ->map(fn ($admin): array => [
                'id'    => (int) $admin->id,
                'label' => trans('masterdata::app.entity-types.form.owner-option', ['name' => $admin->name, 'email' => $admin->email]),
            ])
            ->values()
            ->all();
    }

    /**
     * Select options for a backed enum.
     *
     * @param  array<int, BackedEnum>  $cases
     * @return list<array{id: string|int, label: string}>
     */
    protected function enumOptions(array $cases, string $translationKey): array
    {
        return array_values(array_map(
            fn (BackedEnum $case): array => ['id' => $case->value, 'label' => trans($translationKey.'.'.$case->value)],
            $cases
        ));
    }
}
