<?php

namespace Webkul\MasterDataManagement\Importers\Xref;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Webkul\DataTransfer\Repositories\JobTrackBatchRepository;
use Webkul\MasterDataManagement\Enums\EntityTypeStatus;
use Webkul\MasterDataManagement\Enums\XrefStatus;
use Webkul\MasterDataManagement\Exceptions\ChangeRequestPendingException;
use Webkul\MasterDataManagement\Exceptions\XrefConflictException;
use Webkul\MasterDataManagement\Importers\MasterDataImporter;
use Webkul\MasterDataManagement\Importers\Record\Storage;
use Webkul\MasterDataManagement\Models\EntityType;
use Webkul\MasterDataManagement\Models\Record;
use Webkul\MasterDataManagement\Models\SourceSystem;
use Webkul\MasterDataManagement\Models\Xref;
use Webkul\MasterDataManagement\Repositories\EntityTypeRepository;
use Webkul\MasterDataManagement\Repositories\SourceSystemRepository;
use Webkul\MasterDataManagement\Security\RecordPolicy;
use Webkul\MasterDataManagement\Services\XrefWriter;
use Webkul\MasterDataManagement\ValueObjects\SourceContext;
use Webkul\User\Repositories\AdminRepository;

/**
 * Links a source system's external ids to existing records of one entity type, by record code. Links pass the
 * change gate through the crosswalk writer as the job's author; an id already active on another record is refused.
 */
class Importer extends MasterDataImporter
{
    public const string FILTER_ENTITY_TYPE = 'entity_type';

    public const string FILTER_SOURCE_SYSTEM = 'source_system';

    protected array $permanentAttributes = ['code', 'external_id'];

    protected ?EntityType $entityType = null;

    protected ?SourceSystem $sourceSystem = null;

    public function __construct(
        protected JobTrackBatchRepository $importBatchRepository,
        protected AdminRepository $adminRepository,
        protected EntityTypeRepository $entityTypeRepository,
        protected SourceSystemRepository $sourceSystemRepository,
        protected XrefWriter $xrefWriter,
        protected Storage $storage,
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

        if (! $system instanceof SourceSystem || ! $system->is_active) {
            throw new RuntimeException(trans('masterdata::app.imports.errors.source-system-missing', ['code' => $systemCode]));
        }

        if (Gate::forUser($this->author())->denies('update', RecordPolicy::subjectForEntityType((int) $type->id))) {
            throw new RuntimeException(trans('masterdata::app.imports.errors.records-not-allowed', ['code' => $type->code]));
        }

        $this->entityType = $type;

        $this->sourceSystem = $system;
    }

    /**
     * {@inheritdoc}
     */
    protected function columnNames(): array
    {
        return ['code', 'external_id'];
    }

    /**
     * {@inheritdoc}
     */
    protected function checkRow(array $rowData, int $rowNumber): void
    {
        $validator = Validator::make($this->values($rowData), [
            'code'        => ['required', 'string', 'max:255'],
            'external_id' => ['required', 'string', 'max:255'],
        ], [], [
            'code'        => trans('masterdata::app.imports.records.columns.code'),
            'external_id' => trans('masterdata::app.imports.records.columns.external-id'),
        ]);

        $this->invalidFromErrors($rowNumber, $validator->errors()->toArray());
    }

    /**
     * Read the batch's records and crosswalk entries in a few queries, then link row by row.
     */
    protected function importRows(array $rows): void
    {
        $values = array_map($this->values(...), $rows);

        $this->storage->load(
            $this->entityType,
            $this->author(),
            array_column($values, 'code'),
            $this->sourceSystem,
            array_column($values, 'external_id'),
        );

        foreach ($rows as $index => $row) {
            $rowNumber = $this->rowNumberOf($row);

            try {
                $this->importRow($rowNumber, $values[$index]['code'], $values[$index]['external_id']);
            } catch (ChangeRequestPendingException) {
                $this->skipped($rowNumber, trans('masterdata::app.imports.errors.already-pending'));
            } catch (ValidationException $exception) {
                $this->skipped($rowNumber, $this->messageOf($exception));
            } catch (XrefConflictException $exception) {
                $this->skipped($rowNumber, $exception->getMessage());
            }
        }
    }

    /**
     * Link one external id to the record with the code; an id already linked to it is left as is.
     *
     * @throws ValidationException
     * @throws XrefConflictException
     */
    protected function importRow(int $rowNumber, string $code, string $externalId): void
    {
        $record = $this->storage->byCode($code);

        if (! $record instanceof Record) {
            $this->skipped($rowNumber, trans('masterdata::app.imports.errors.record-not-found', ['code' => $code]));

            return;
        }

        $xref = $this->storage->xref($externalId);

        if ($xref instanceof Xref && $xref->status === XrefStatus::Active) {
            if ((int) $xref->record_id === (int) $record->id) {
                return;
            }

            throw new XrefConflictException($this->sourceSystem, $externalId, (int) $xref->record_id);
        }

        $gate = $this->xrefWriter->link($record, $this->sourceSystem, $externalId, $this->author(), new SourceContext(SourceContext::IMPORT, (int) $this->sourceSystem->id, $externalId));

        if ($gate->isPending()) {
            $this->held($rowNumber, $gate);

            return;
        }

        if ($xref instanceof Xref) {
            $this->updatedItemsCount++;

            return;
        }

        $this->createdItemsCount++;
    }

    /**
     * The trimmed code and external id of a row.
     *
     * @param  array<string, mixed>  $row
     * @return array{code: string, external_id: string}
     */
    protected function values(array $row): array
    {
        return [
            'code'        => trim((string) ($row['code'] ?? '')),
            'external_id' => trim((string) ($row['external_id'] ?? '')),
        ];
    }
}
