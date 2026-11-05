<?php

namespace Webkul\MasterDataManagement\Validators\JobInstances\Import;

use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Webkul\MasterDataManagement\Enums\EntityTypeStatus;
use Webkul\MasterDataManagement\Importers\Xref\Importer;
use Webkul\MasterDataManagement\Models\EntityType;
use Webkul\MasterDataManagement\Models\SourceSystem;
use Webkul\MasterDataManagement\Repositories\EntityTypeRepository;
use Webkul\MasterDataManagement\Repositories\SourceSystemRepository;
use Webkul\MasterDataManagement\Security\RecordPolicy;
use Webkul\User\Models\Admin;

class XrefJobValidator extends ImportJobValidator
{
    public function __construct(
        protected EntityTypeRepository $entityTypeRepository,
        protected SourceSystemRepository $sourceSystemRepository,
    ) {}

    /**
     * Validate the settings, then that the entity type is active and its records editable by the admin, and the
     * source system is active.
     *
     * @throws ValidationException
     */
    public function validate(array $data, array $options = []): void
    {
        parent::validate($data, $options);

        $type = $this->entityTypeRepository->findOneByField('code', $this->filterValue($data, Importer::FILTER_ENTITY_TYPE));

        $admin = $this->admin();

        if (
            ! $type instanceof EntityType
            || $type->status !== EntityTypeStatus::Active
            || ($admin instanceof Admin && Gate::forUser($admin)->denies('update', RecordPolicy::subjectForEntityType((int) $type->id)))
        ) {
            $this->fail(Importer::FILTER_ENTITY_TYPE, trans('masterdata::app.imports.errors.entity-type-invalid'));
        }

        $system = $this->sourceSystemRepository->findOneByField('code', $this->filterValue($data, Importer::FILTER_SOURCE_SYSTEM));

        if (! $system instanceof SourceSystem || ! $system->is_active) {
            $this->fail(Importer::FILTER_SOURCE_SYSTEM, trans('masterdata::app.imports.errors.source-system-invalid'));
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function filterRules(): array
    {
        return [
            Importer::FILTER_ENTITY_TYPE   => ['required', 'string', 'max:64'],
            Importer::FILTER_SOURCE_SYSTEM => ['required', 'string', 'max:64'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function filterNames(): array
    {
        return [
            Importer::FILTER_ENTITY_TYPE   => trans('masterdata::app.imports.filters.entity-type'),
            Importer::FILTER_SOURCE_SYSTEM => trans('masterdata::app.imports.filters.source-system'),
        ];
    }
}
