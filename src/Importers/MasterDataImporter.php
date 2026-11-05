<?php

namespace Webkul\MasterDataManagement\Importers;

use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Webkul\DataTransfer\Contracts\JobTrackBatch as JobTrackBatchContract;
use Webkul\DataTransfer\Helpers\Import;
use Webkul\DataTransfer\Helpers\Importers\AbstractImporter;
use Webkul\DataTransfer\Repositories\JobTrackBatchRepository;
use Webkul\MasterDataManagement\ValueObjects\GateResult;
use Webkul\User\Models\Admin;
use Webkul\User\Repositories\AdminRepository;

/**
 * Shared flow of the master data importers: batches of `masterdata.batch_size` rows, written as the admin who
 * started the job so the change gate and the row scope apply to them. Each stored row keeps its file row number, so
 * rows refused or held for approval while importing are reported against the row in the job log; the batch summary
 * counts them as `skipped` and `held` next to the created and updated items.
 */
abstract class MasterDataImporter extends AbstractImporter
{
    /**
     * The key a stored batch row keeps its file row number under.
     */
    public const string ROW_NUMBER = '_row';

    public const string ERROR_INVALID_VALUE = 'masterdata_invalid_value';

    public const string ERROR_NOT_ALLOWED = 'masterdata_not_allowed';

    public const string ERROR_HEADER = 'masterdata_header';

    /**
     * Columns every row of the file must carry.
     *
     * @var list<string>
     */
    protected array $permanentAttributes = [];

    protected int $heldItemsCount = 0;

    protected int $skippedItemsCount = 0;

    protected ?int $currentRowNumber = null;

    protected ?Admin $author = null;

    public function __construct(
        protected JobTrackBatchRepository $importBatchRepository,
        protected AdminRepository $adminRepository,
    ) {
        parent::__construct($importBatchRepository);
    }

    /**
     * Resolve the job's targets from its filters and check the author may write them.
     *
     * @throws RuntimeException
     */
    abstract protected function initialize(): void;

    /**
     * The column names the file may carry.
     *
     * @return list<string>
     */
    abstract protected function columnNames(): array;

    /**
     * Check one row of the file, reporting each problem through skipRow().
     *
     * @param  array<string, mixed>  $rowData
     */
    abstract protected function checkRow(array $rowData, int $rowNumber): void;

    /**
     * Write the valid rows of one batch.
     *
     * @param  list<array<string, mixed>>  $rows
     */
    abstract protected function importRows(array $rows): void;

    /**
     * Validate the job's targets and the file. Deleting through these importers is not supported.
     *
     * @throws RuntimeException
     */
    public function validateData(): void
    {
        if ($this->import->action === Import::ACTION_DELETE) {
            throw new RuntimeException(trans('masterdata::app.imports.errors.delete-not-supported'));
        }

        $this->initialize();

        $this->validColumnNames = $this->columnNames();

        foreach ($this->headerErrors(array_map(strval(...), $this->getSource()->getColumnNames())) as $column => $message) {
            $this->errorHelper->addError(self::ERROR_HEADER, null, null, $message);

            $this->validColumnNames[] = $column;
        }

        parent::validateData();
    }

    /**
     * Header columns refused with a message of their own instead of the generic invalid column error, keyed by column.
     *
     * @param  list<string>  $columnNames
     * @return array<string, string>
     */
    protected function headerErrors(array $columnNames): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function validateRow(array $rowData, int $rowNumber): bool
    {
        $this->currentRowNumber = $rowNumber;

        if (! isset($this->validatedRows[$rowNumber])) {
            $this->validatedRows[$rowNumber] = true;

            $this->checkRow($rowData, $rowNumber);
        }

        return ! $this->errorHelper->isRowInvalid($rowNumber);
    }

    /**
     * {@inheritdoc}
     */
    public function importBatch(JobTrackBatchContract $importBatchContract): bool
    {
        Event::dispatch('data_transfer.imports.batch.import.before', $importBatchContract);

        $this->createdItemsCount = $this->updatedItemsCount = $this->heldItemsCount = $this->skippedItemsCount = 0;

        $this->initialize();

        $this->importRows(array_values((array) $importBatchContract->data));

        $batch = $this->importBatchRepository->update([
            'state'   => Import::STATE_PROCESSED,
            'summary' => [
                'created' => $this->createdItemsCount,
                'updated' => $this->updatedItemsCount,
                'deleted' => 0,
                'held'    => $this->heldItemsCount,
                'skipped' => $this->skippedItemsCount,
            ],
        ], $importBatchContract->id);

        Event::dispatch('data_transfer.imports.batch.import.after', $batch);

        return true;
    }

    /**
     * Number of changes held for approval.
     */
    public function getHeldItemsCount(): int
    {
        return $this->heldItemsCount;
    }

    /**
     * Number of valid rows refused while importing.
     */
    public function getSkippedItemsCount(): int
    {
        return $this->skippedItemsCount;
    }

    /**
     * {@inheritdoc}
     */
    protected function getEffectiveBatchSize(): int
    {
        return max(1, (int) config('masterdata.batch_size', 500));
    }

    /**
     * Keep the file row number on the stored row, for the messages of the import step.
     *
     * {@inheritdoc}
     */
    protected function prepareRowForDb(array $rowData): array
    {
        return parent::prepareRowForDb($rowData) + [self::ROW_NUMBER => $this->currentRowNumber];
    }

    /**
     * The job's filter values.
     *
     * @return array<string, mixed>
     */
    protected function filters(): array
    {
        return (array) ($this->import->jobInstance?->filters ?? []);
    }

    /**
     * One filter as a trimmed string; empty when absent.
     */
    protected function filter(string $name): string
    {
        $value = $this->filters()[$name] ?? null;

        return is_scalar($value) ? trim((string) $value) : '';
    }

    /**
     * The admin who started the job; the import writes as this admin.
     *
     * @throws RuntimeException
     */
    protected function author(): Admin
    {
        if ($this->author instanceof Admin) {
            return $this->author;
        }

        $author = $this->import->user_id === null ? null : $this->adminRepository->find($this->import->user_id);

        if (! $author instanceof Admin) {
            throw new RuntimeException(trans('masterdata::app.imports.errors.no-author'));
        }

        return $this->author = $author;
    }

    /**
     * The file row number a stored row came from.
     *
     * @param  array<string, mixed>  $row
     */
    protected function rowNumberOf(array $row): int
    {
        return (int) ($row[self::ROW_NUMBER] ?? 0);
    }

    /**
     * Report a row problem found while validating the file.
     */
    protected function invalid(int $rowNumber, string $column, string $message, string $code = self::ERROR_INVALID_VALUE): void
    {
        $this->skipRow($rowNumber, $code, $column, str_replace('%', '%%', $message));
    }

    /**
     * Report each validation message of a row found while validating the file, against its column.
     *
     * @param  array<string, list<string>>  $errors
     * @param  array<string, string>  $columns  The file column of each validation key.
     */
    protected function invalidFromErrors(int $rowNumber, array $errors, array $columns = []): void
    {
        foreach ($errors as $key => $messages) {
            $this->invalid($rowNumber, $columns[$key] ?? $key, (string) ($messages[0] ?? ''));
        }
    }

    /**
     * Count a change held for approval and log it against its row.
     */
    protected function held(int $rowNumber, GateResult $gate): void
    {
        $this->heldItemsCount++;

        $this->jobLogger?->info(trans('masterdata::app.imports.messages.held', [
            'row'       => $rowNumber,
            'reference' => (string) $gate->reference,
        ]));
    }

    /**
     * Count a row refused while importing and log why.
     */
    protected function skipped(int $rowNumber, string $message): void
    {
        $this->skippedItemsCount++;

        $this->jobLogger?->warning(trans('masterdata::app.imports.messages.skipped', [
            'row'     => $rowNumber,
            'message' => $message,
        ]));
    }

    /**
     * The messages of a refused write as one line.
     */
    protected function messageOf(ValidationException $exception): string
    {
        return implode(' ', array_merge([], ...array_values($exception->errors())));
    }
}
