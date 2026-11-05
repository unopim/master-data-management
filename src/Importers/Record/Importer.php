<?php

namespace Webkul\MasterDataManagement\Importers\Record;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Webkul\DataTransfer\Repositories\JobTrackBatchRepository;
use Webkul\MasterDataManagement\Enums\CodeStrategy;
use Webkul\MasterDataManagement\Enums\EntityTypeStatus;
use Webkul\MasterDataManagement\Enums\RecordStatus;
use Webkul\MasterDataManagement\Enums\XrefStatus;
use Webkul\MasterDataManagement\Exceptions\ChangeRequestPendingException;
use Webkul\MasterDataManagement\Exceptions\StaleRecordException;
use Webkul\MasterDataManagement\Exceptions\XrefConflictException;
use Webkul\MasterDataManagement\FieldTypes\FieldTypeRegistry;
use Webkul\MasterDataManagement\Importers\MasterDataImporter;
use Webkul\MasterDataManagement\Models\EntityType;
use Webkul\MasterDataManagement\Models\Field;
use Webkul\MasterDataManagement\Models\Record;
use Webkul\MasterDataManagement\Models\SourceSystem;
use Webkul\MasterDataManagement\Models\Xref;
use Webkul\MasterDataManagement\Repositories\EntityTypeRepository;
use Webkul\MasterDataManagement\Repositories\FieldRepository;
use Webkul\MasterDataManagement\Repositories\RecordRepository;
use Webkul\MasterDataManagement\Repositories\SourceSystemRepository;
use Webkul\MasterDataManagement\Security\RecordPolicy;
use Webkul\MasterDataManagement\Services\FieldRuleBuilder;
use Webkul\MasterDataManagement\Services\HierarchyService;
use Webkul\MasterDataManagement\Services\RecordWriter;
use Webkul\MasterDataManagement\Services\XrefWriter;
use Webkul\MasterDataManagement\ValueObjects\RecordWriteResult;
use Webkul\MasterDataManagement\ValueObjects\SourceContext;
use Webkul\User\Repositories\AdminRepository;

/**
 * Upserts the records of one entity type by code through the record writer. Columns are the record columns, the
 * field codes (`field-locale` for per-locale fields) and, with a source system, `external_id`, which links the
 * record in the crosswalk after the write; the mapping filter renames file columns onto them. A common field whose
 * code matches a record column other than `code` (a `status` field, say) takes that column. Without a code a row
 * finds its record by external id, else creates one; `parent_code` names a record of the type or of its allowed
 * parent types. Every write passes the change gate as the job's author, so on a type that requires approval the
 * changes are held instead.
 */
class Importer extends MasterDataImporter
{
    public const string FILTER_ENTITY_TYPE = 'entity_type';

    public const string FILTER_SOURCE_SYSTEM = 'source_system';

    public const string FILTER_MAPPING = 'mapping';

    /**
     * The change request context key carrying the external id a held create links once approved.
     */
    public const string CONTEXT_XREF = 'import_xref';

    protected ?EntityType $entityType = null;

    protected ?SourceSystem $sourceSystem = null;

    protected ColumnMapping $mapping;

    /**
     * @var array<string, Field>
     */
    protected array $fields = [];

    /**
     * @var list<string>
     */
    protected array $locales = [];

    protected bool $canCreate = false;

    protected bool $canEdit = false;

    /**
     * The entity types a row's parent may come from, in priority order: the type itself first when allowed.
     *
     * @var list<int>
     */
    protected array $parentTypeIds = [];

    /**
     * Whether new records take their code from the file; else the type builds it from a pattern or identifier fields.
     */
    protected bool $takesCallerCode = false;

    /**
     * What each file column is read into: a record column name, or a field and locale.
     *
     * @var array<string, array{name: string, field: ?Field, locale: ?string}|null>
     */
    protected array $columnSpecs = [];

    public function __construct(
        protected JobTrackBatchRepository $importBatchRepository,
        protected AdminRepository $adminRepository,
        protected EntityTypeRepository $entityTypeRepository,
        protected SourceSystemRepository $sourceSystemRepository,
        protected FieldRepository $fieldRepository,
        protected FieldTypeRegistry $fieldTypeRegistry,
        protected FieldRuleBuilder $fieldRuleBuilder,
        protected HierarchyService $hierarchyService,
        protected RecordRepository $recordRepository,
        protected RecordWriter $recordWriter,
        protected XrefWriter $xrefWriter,
        protected Storage $storage,
        protected CellReader $cellReader,
    ) {
        parent::__construct($importBatchRepository, $adminRepository);
    }

    /**
     * {@inheritdoc}
     */
    protected function initialize(): void
    {
        if ($this->entityType instanceof EntityType) {
            return;
        }

        $code = $this->filter(self::FILTER_ENTITY_TYPE);

        $type = $code === '' ? null : $this->entityTypeRepository->findOneByField('code', $code);

        if (! $type instanceof EntityType || $type->status !== EntityTypeStatus::Active) {
            throw new RuntimeException(trans('masterdata::app.imports.errors.entity-type-missing', ['code' => $code]));
        }

        $systemCode = $this->filter(self::FILTER_SOURCE_SYSTEM);

        $system = $systemCode === '' ? null : $this->sourceSystemRepository->findOneByField('code', $systemCode);

        if ($systemCode !== '' && (! $system instanceof SourceSystem || ! $system->is_active)) {
            throw new RuntimeException(trans('masterdata::app.imports.errors.source-system-missing', ['code' => $systemCode]));
        }

        $gate = Gate::forUser($this->author());

        $this->canCreate = $gate->allows('masterdata.records.create', $type);

        $this->canEdit = $gate->allows('update', RecordPolicy::subjectForEntityType((int) $type->id));

        if (! $this->canCreate && ! $this->canEdit) {
            throw new RuntimeException(trans('masterdata::app.imports.errors.records-not-allowed', ['code' => $type->code]));
        }

        $this->entityType = $type;

        $this->sourceSystem = $system instanceof SourceSystem ? $system : null;

        $this->mapping = ColumnMapping::parse($this->filter(self::FILTER_MAPPING));

        $this->fields = $this->fieldRepository->activeFor((int) $type->id)
            ->filter(fn (Field $field): bool => $this->fieldTypeRegistry->has($field->type))
            ->keyBy('code')
            ->all();

        $this->locales = core()->getAllActiveLocales()->pluck('code')->map(strval(...))->all();

        $allowed = $this->hierarchyService->allowedParentTypeIds($type);

        $this->parentTypeIds = in_array((int) $type->id, $allowed, true)
            ? [(int) $type->id, ...array_values(array_diff($allowed, [(int) $type->id]))]
            : $allowed;

        $this->takesCallerCode = $type->code_strategy !== CodeStrategy::Pattern && $this->fieldRuleBuilder->identifierFields($type)->isEmpty();

        if ($this->takesCallerCode) {
            $this->permanentAttributes = [$this->columnFor('code')];
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function columnNames(): array
    {
        $columns = array_filter(
            ColumnMapping::RECORD_COLUMNS,
            fn (string $column): bool => $column !== 'external_id' || $this->sourceSystem instanceof SourceSystem,
        );

        foreach ($this->fields as $field) {
            if (! $field->value_per_locale) {
                $columns[] = $field->code;

                continue;
            }

            foreach ($this->locales as $locale) {
                $columns[] = $field->code.'-'.$locale;
            }
        }

        foreach (array_keys($this->mapping->targets) as $column) {
            if ($this->spec((string) $column) !== null) {
                $columns[] = (string) $column;
            }
        }

        return array_values(array_unique($columns));
    }

    /**
     * An external_id column, by name or mapping, needs the source system it comes from.
     *
     * {@inheritdoc}
     */
    protected function headerErrors(array $columnNames): array
    {
        if ($this->sourceSystem instanceof SourceSystem || $this->isCommonFieldOtherThanCode('external_id')) {
            return [];
        }

        $errors = [];

        foreach ($columnNames as $column) {
            if ($this->mapping->targetOf($column) === 'external_id') {
                $errors[$column] = trans('masterdata::app.imports.errors.external-id-without-source-system', ['column' => $column]);
            }
        }

        return $errors;
    }

    /**
     * Check the record columns and each field value against its field type, after alias resolution.
     *
     * Uniqueness, required values and references are checked by the record writer when the row is imported.
     */
    protected function checkRow(array $rowData, int $rowNumber): void
    {
        [$record, $cells, $columns] = $this->read($rowData);

        $validator = Validator::make($record, [
            'code'        => [$this->takesCallerCode ? 'required' : 'nullable', 'string', 'max:255'],
            'label'       => ['nullable', 'string', 'max:255'],
            'status'      => ['nullable', Rule::enum(RecordStatus::class)->only([RecordStatus::Draft, RecordStatus::Active])],
            'parent_code' => ['nullable', 'string', 'max:255', 'different:code'],
            'external_id' => ['nullable', 'string', 'max:255'],
        ], [], [
            'code'        => trans('masterdata::app.imports.records.columns.code'),
            'label'       => trans('masterdata::app.imports.records.columns.label'),
            'status'      => trans('masterdata::app.imports.records.columns.status'),
            'parent_code' => trans('masterdata::app.imports.records.columns.parent-code'),
            'external_id' => trans('masterdata::app.imports.records.columns.external-id'),
        ]);

        $this->invalidFromErrors($rowNumber, $validator->errors()->toArray(), $columns);

        $this->cellReader->prime($cells, $this->sourceSystem?->id);

        foreach ($cells as [$field, $raw, $locale, $column]) {
            if (! $this->fieldTypeRegistry->get($field->type)->storesInValues()) {
                continue;
            }

            $label = is_string($field->label) && $field->label !== '' ? $field->label : $field->code;

            $validator = Validator::make(
                ['value' => $this->cellReader->read($field, $raw)],
                ['value' => ['nullable', ...$this->fieldTypeRegistry->get($field->type)->rules($field)]],
                [],
                ['value' => $label],
            );

            if ($validator->fails()) {
                $this->invalid($rowNumber, $column, (string) $validator->errors()->first('value'));
            }
        }
    }

    /**
     * Read the batch's records, crosswalk entries and aliases in a few queries, then write row by row.
     */
    protected function importRows(array $rows): void
    {
        $parsed = [];

        $codes = [];

        $parentCodes = [];

        $externalIds = [];

        $cells = [];

        foreach ($rows as $row) {
            [$record, $rowCells] = $this->read($row);

            $parsed[] = [$this->rowNumberOf($row), $record, $rowCells];

            if (isset($record['code'])) {
                $codes[] = $record['code'];
            }

            if (isset($record['parent_code'])) {
                $parentCodes[] = $record['parent_code'];
            }

            if (isset($record['external_id'])) {
                $externalIds[] = $record['external_id'];
            }

            array_push($cells, ...$rowCells);
        }

        $this->storage->load($this->entityType, $this->author(), $codes, $this->sourceSystem, $externalIds, $parentCodes, $this->parentTypeIds);

        $this->cellReader->prime($cells, $this->sourceSystem?->id);

        foreach ($parsed as [$rowNumber, $record, $rowCells]) {
            try {
                $this->importRow($rowNumber, $record, $rowCells);
            } catch (ChangeRequestPendingException) {
                $this->skipped($rowNumber, trans('masterdata::app.imports.errors.already-pending'));
            } catch (ValidationException $exception) {
                $this->skipped($rowNumber, $this->messageOf($exception));
            } catch (XrefConflictException|StaleRecordException $exception) {
                $this->skipped($rowNumber, $exception->getMessage());
            }
        }
    }

    /**
     * Create or update the row's record, then link its external id.
     *
     * @param  array<string, string>  $data
     * @param  list<array{0: Field, 1: string, 2: ?string, 3: string}>  $cells
     *
     * @throws ValidationException
     * @throws XrefConflictException
     * @throws StaleRecordException
     */
    protected function importRow(int $rowNumber, array $data, array $cells): void
    {
        $code = $data['code'] ?? null;

        $externalId = $this->sourceSystem instanceof SourceSystem ? ($data['external_id'] ?? null) : null;

        $record = $code !== null ? $this->storage->byCode($code) : ($externalId !== null ? $this->storage->byExternalId($externalId) : null);

        if (! $record instanceof Record && $code !== null && $this->entityType->code_strategy === CodeStrategy::Pattern) {
            $this->skipped($rowNumber, trans('masterdata::app.imports.errors.code-not-found', ['code' => $code]));

            return;
        }

        if ($externalId !== null) {
            $this->ensureLinkable($externalId, $record);
        }

        if (! ($record instanceof Record ? $this->canEdit : $this->canCreate)) {
            $this->skipped($rowNumber, trans($record instanceof Record ? 'masterdata::app.imports.errors.edit-not-allowed' : 'masterdata::app.imports.errors.create-not-allowed'));

            return;
        }

        $input = $this->writerInput($data, $cells);

        $source = new SourceContext(SourceContext::IMPORT, $this->sourceSystem?->id, $externalId, $externalId === null ? [] : [
            self::CONTEXT_XREF => [
                XrefWriter::INPUT_SYSTEM      => (int) $this->sourceSystem?->id,
                XrefWriter::INPUT_EXTERNAL_ID => $externalId,
            ],
        ]);

        if (! $record instanceof Record) {
            $result = $this->recordWriter->submitCreate($this->entityType, $input + ($this->takesCallerCode && $code !== null ? ['code' => $code] : []), $this->author(), $source);

            if ($result->isPending()) {
                $this->held($rowNumber, $result->gate);

                return;
            }

            $this->createdItemsCount++;
        } else {
            $baseRevision = (int) $record->revision;

            $result = $this->update($record, $input, $source, $baseRevision);

            if ($result->isPending()) {
                $this->held($rowNumber, $result->gate);
            } elseif ((int) $result->record->revision !== $baseRevision) {
                $this->updatedItemsCount++;
            }
        }

        $this->storage->remember($result->record);

        if ($externalId !== null) {
            $this->link($rowNumber, $result->record, $externalId, $source);
        }
    }

    /**
     * Update at the revision read with the batch; a record changed since is re-read once and the update retried.
     *
     * @param  array<string, mixed>  $input
     *
     * @throws ValidationException
     * @throws StaleRecordException
     */
    protected function update(Record $record, array $input, SourceContext $source, int &$baseRevision): RecordWriteResult
    {
        try {
            return $this->recordWriter->submitUpdate($record, $input, $baseRevision, $this->author(), $source);
        } catch (StaleRecordException $exception) {
            $fresh = $this->recordRepository->findManyForEntityType((int) $record->entity_type_id, [(int) $record->id], $this->author())->first()
                ?? throw $exception;

            $baseRevision = (int) $fresh->revision;

            return $this->recordWriter->submitUpdate($fresh, $input, $baseRevision, $this->author(), $source);
        }
    }

    /**
     * Refuse a row whose external id is active on another record before anything is written for it.
     *
     * @throws XrefConflictException
     */
    protected function ensureLinkable(string $externalId, ?Record $record): void
    {
        $xref = $this->storage->xref($externalId);

        if ($xref instanceof Xref && $xref->status === XrefStatus::Active && (int) $xref->record_id !== (int) $record?->id) {
            throw new XrefConflictException($this->sourceSystem, $externalId, (int) $xref->record_id);
        }
    }

    /**
     * Link the external id to the record through the crosswalk writer, unless it is already linked to it.
     *
     * @throws ValidationException
     * @throws XrefConflictException
     */
    protected function link(int $rowNumber, Record $record, string $externalId, SourceContext $source): void
    {
        $xref = $this->storage->xref($externalId);

        if ($xref instanceof Xref && $xref->status === XrefStatus::Active && (int) $xref->record_id === (int) $record->id) {
            return;
        }

        $gate = $this->xrefWriter->link($record, $this->sourceSystem, $externalId, $this->author(), $source);

        if ($gate->isPending()) {
            $this->held($rowNumber, $gate);
        }
    }

    /**
     * The record writer input of a row: label, status, parent and the field values it carries.
     *
     * @param  array<string, string>  $data
     * @param  list<array{0: Field, 1: string, 2: ?string, 3: string}>  $cells
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    protected function writerInput(array $data, array $cells): array
    {
        $input = array_intersect_key($data, array_flip(['label', 'status']));

        if (isset($data['parent_code'])) {
            $parent = $this->storage->parent($this->entityType, $data['parent_code'], $this->parentTypeIds);

            if (! $parent instanceof Record) {
                throw ValidationException::withMessages([
                    'parent' => trans('masterdata::app.imports.errors.parent-not-found', ['code' => $data['parent_code']]),
                ]);
            }

            $input['parent'] = (string) $parent->uuid;
        }

        foreach ($cells as [$field, $raw, $locale]) {
            $value = $this->cellReader->read($field, $raw);

            if ($locale === null) {
                $input['values']['common'][$field->code] = $value;
            } else {
                $input['values']['locale_specific'][$locale][$field->code] = $value;
            }
        }

        return $input;
    }

    /**
     * Split a file row into its record columns, its non-empty field cells (field, raw value, locale, file column) and
     * the file column of each record column; empty cells leave stored values unchanged.
     *
     * @param  array<string, mixed>  $row
     * @return array{0: array<string, string>, 1: list<array{0: Field, 1: string, 2: ?string, 3: string}>, 2: array<string, string>}
     */
    protected function read(array $row): array
    {
        $record = [];

        $cells = [];

        $columns = [];

        foreach ($row as $column => $raw) {
            $column = (string) $column;

            if ($column === self::ROW_NUMBER || ! is_scalar($raw) || trim((string) $raw) === '') {
                continue;
            }

            $spec = $this->spec($column);

            if ($spec === null) {
                continue;
            }

            if ($spec['field'] instanceof Field) {
                $cells[] = [$spec['field'], (string) $raw, $spec['locale'], $column];

                continue;
            }

            $value = trim((string) $raw);

            $record[$spec['name']] = $spec['name'] === 'status' ? mb_strtolower($value) : $value;

            $columns[$spec['name']] = $column;
        }

        return [$record, $cells, $columns];
    }

    /**
     * What a file column is read into, or null when it maps onto nothing the type has.
     *
     * @return array{name: string, field: ?Field, locale: ?string}|null
     */
    protected function spec(string $column): ?array
    {
        if (array_key_exists($column, $this->columnSpecs)) {
            return $this->columnSpecs[$column];
        }

        $target = $this->mapping->targetOf($column);

        if (in_array($target, ColumnMapping::RECORD_COLUMNS, true) && ! $this->isCommonFieldOtherThanCode($target)) {
            $spec = $target !== 'external_id' || $this->sourceSystem instanceof SourceSystem
                ? ['name' => $target, 'field' => null, 'locale' => null]
                : null;

            return $this->columnSpecs[$column] = $spec;
        }

        [$code, $locale] = ColumnMapping::splitFieldTarget($target);

        $field = $this->fields[$code] ?? null;

        $valid = $field instanceof Field && ($field->value_per_locale
            ? $locale !== null && in_array($locale, $this->locales, true)
            : $locale === null);

        return $this->columnSpecs[$column] = $valid ? ['name' => $target, 'field' => $field, 'locale' => $locale] : null;
    }

    /**
     * Whether a record column name is also the code of a common field of the type, which then takes the column; the
     * record code always stays the record column.
     */
    protected function isCommonFieldOtherThanCode(string $target): bool
    {
        $field = $this->fields[$target] ?? null;

        return $target !== 'code' && $field instanceof Field && ! $field->value_per_locale;
    }

    /**
     * The file column that is read into the given target.
     */
    protected function columnFor(string $target): string
    {
        $column = array_search($target, $this->mapping->targets, true);

        return is_string($column) ? $column : $target;
    }
}
