<?php

namespace Webkul\MasterDataManagement\Validators\JobInstances\Import;

use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Webkul\MasterDataManagement\Enums\EntityTypeStatus;
use Webkul\MasterDataManagement\Importers\Record\ColumnMapping;
use Webkul\MasterDataManagement\Importers\Record\Importer;
use Webkul\MasterDataManagement\Models\EntityType;
use Webkul\MasterDataManagement\Models\Field;
use Webkul\MasterDataManagement\Models\SourceSystem;
use Webkul\MasterDataManagement\Repositories\EntityTypeRepository;
use Webkul\MasterDataManagement\Repositories\FieldRepository;
use Webkul\MasterDataManagement\Repositories\SourceSystemRepository;
use Webkul\MasterDataManagement\Security\RecordPolicy;
use Webkul\User\Models\Admin;

class RecordJobValidator extends ImportJobValidator
{
    public function __construct(
        protected EntityTypeRepository $entityTypeRepository,
        protected SourceSystemRepository $sourceSystemRepository,
        protected FieldRepository $fieldRepository,
    ) {}

    /**
     * Validate the settings, then that the entity type is active and writable by the admin, the source system is
     * active, and every mapping line names a record column or an active field of the type.
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
            || ($admin instanceof Admin && Gate::forUser($admin)->denies('masterdata.records.create', $type)
                && Gate::forUser($admin)->denies('update', RecordPolicy::subjectForEntityType((int) $type->id)))
        ) {
            $this->fail(Importer::FILTER_ENTITY_TYPE, trans('masterdata::app.imports.errors.entity-type-invalid'));
        }

        $systemCode = $this->filterValue($data, Importer::FILTER_SOURCE_SYSTEM);

        if ($systemCode !== '') {
            $system = $this->sourceSystemRepository->findOneByField('code', $systemCode);

            if (! $system instanceof SourceSystem || ! $system->is_active) {
                $this->fail(Importer::FILTER_SOURCE_SYSTEM, trans('masterdata::app.imports.errors.source-system-invalid'));
            }
        }

        $this->validateMapping($type, $this->filterValue($data, Importer::FILTER_MAPPING), $systemCode !== '');
    }

    /**
     * Refuse malformed mapping lines and targets that are neither a record column nor an active field of the type.
     *
     * @throws ValidationException
     */
    protected function validateMapping(EntityType $type, string $value, bool $hasSourceSystem): void
    {
        $mapping = ColumnMapping::parse($value);

        if ($mapping->invalidLines !== []) {
            $this->fail(Importer::FILTER_MAPPING, trans('masterdata::app.imports.errors.mapping-invalid-lines', [
                'lines' => implode(', ', $mapping->invalidLines),
            ]));
        }

        $fields = $this->fieldRepository->activeFor((int) $type->id)->keyBy('code');

        $locales = core()->getAllActiveLocales()->pluck('code')->map(strval(...))->all();

        $unknown = [];

        foreach ($mapping->targets as $target) {
            if (in_array($target, ColumnMapping::RECORD_COLUMNS, true)) {
                if ($target === 'external_id' && ! $hasSourceSystem) {
                    $unknown[] = $target;
                }

                continue;
            }

            [$code, $locale] = ColumnMapping::splitFieldTarget($target);

            $field = $fields->get($code);

            $valid = $field instanceof Field && ($field->value_per_locale
                ? $locale !== null && in_array($locale, $locales, true)
                : $locale === null);

            if (! $valid) {
                $unknown[] = $target;
            }
        }

        if ($unknown !== []) {
            $this->fail(Importer::FILTER_MAPPING, trans('masterdata::app.imports.errors.mapping-unknown-targets', [
                'targets' => implode(', ', array_unique($unknown)),
            ]));
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function filterRules(): array
    {
        return [
            Importer::FILTER_ENTITY_TYPE   => ['required', 'string', 'max:64'],
            Importer::FILTER_SOURCE_SYSTEM => ['nullable', 'string', 'max:64'],
            Importer::FILTER_MAPPING       => ['nullable', 'string', 'max:20000'],
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
            Importer::FILTER_MAPPING       => trans('masterdata::app.imports.filters.mapping'),
        ];
    }
}
