<?php

namespace Webkul\MasterDataManagement\Services;

use Illuminate\Support\Facades\DB;
use Webkul\MasterDataManagement\FieldTypes\FieldType;
use Webkul\MasterDataManagement\FieldTypes\FieldTypeRegistry;
use Webkul\MasterDataManagement\Models\Field;
use Webkul\MasterDataManagement\Models\Record;
use Webkul\MasterDataManagement\Repositories\FieldRepository;
use Webkul\MasterDataManagement\ValueObjects\IndexValue;

/**
 * The only writer of `mdm_record_index_values`, the derived table every field-based filter reads.
 */
class RecordIndexer
{
    public const string TABLE = 'mdm_record_index_values';

    /**
     * Index values outside decimal(20,6) are left out of the numeric column instead of failing the write.
     */
    protected const float NUMERIC_LIMIT = 1e14;

    public function __construct(
        protected FieldRepository $fieldRepository,
        protected FieldTypeRegistry $fieldTypeRegistry,
        protected Normalizer $normalizer,
    ) {}

    /**
     * Replace the index rows of the record with rows built from its current values.
     */
    public function sync(Record $record): void
    {
        $this->syncMany([$record]);
    }

    /**
     * Write the index rows of a record inserted in the current transaction, which has none yet.
     *
     * Skips the delete: on InnoDB a delete matching no rows takes a gap lock, and two concurrent creates
     * holding it would deadlock on their inserts.
     */
    public function index(Record $record): void
    {
        $this->insertRows($this->rowsFor($record));
    }

    /**
     * Replace the index rows of the records with one delete and chunked inserts.
     *
     * @param  iterable<int, Record>  $records
     */
    public function syncMany(iterable $records): void
    {
        $ids = [];

        $rows = [];

        foreach ($records as $record) {
            $ids[] = $record->getKey();

            array_push($rows, ...$this->rowsFor($record));
        }

        if ($ids === []) {
            return;
        }

        DB::table(self::TABLE)->whereIn('record_id', $ids)->delete();

        $this->insertRows($rows);
    }

    /**
     * Remove the index rows of one field of the entity type, in batches.
     */
    public function forgetField(int $entityTypeId, string $fieldCode): void
    {
        do {
            $ids = DB::table(self::TABLE)
                ->where('entity_type_id', $entityTypeId)
                ->where('field_code', $fieldCode)
                ->orderBy('id')
                ->limit($this->batchSize())
                ->pluck('id')
                ->all();

            if ($ids !== []) {
                DB::table(self::TABLE)->whereIn('id', $ids)->delete();
            }
        } while ($ids !== []);
    }

    /**
     * The configured batch size for chunked index work (`masterdata.batch_size`).
     */
    public function batchSize(): int
    {
        return max(1, (int) config('masterdata.batch_size', 500));
    }

    /**
     * The index columns of each item of a stored value, exactly as the indexer writes them.
     *
     * A list value (multiselect) gives one entry per item; a value of a type kept outside the values JSON gives none.
     *
     * @return list<array{value_normalized: ?string, value_numeric: ?string, value_date: ?string}>
     */
    public function columnsFor(Field $field, mixed $value): array
    {
        $type = $this->indexableType($field);

        if (! $type instanceof FieldType || $value === null) {
            return [];
        }

        $columns = [];

        foreach (is_array($value) && array_is_list($value) ? $value : [$value] as $item) {
            $row = $item === null ? null : $this->columns($type->indexValue($item, $field));

            if ($row !== null) {
                $columns[] = $row;
            }
        }

        return $columns;
    }

    /**
     * Insert the rows in batches.
     *
     * @param  list<array<string, mixed>>  $rows
     */
    protected function insertRows(array $rows): void
    {
        foreach (array_chunk($rows, $this->batchSize()) as $chunk) {
            DB::table(self::TABLE)->insert($chunk);
        }
    }

    /**
     * Build the index rows of the record's searchable and unique values.
     *
     * @return list<array<string, mixed>>
     */
    protected function rowsFor(Record $record): array
    {
        $values = $record->values ?? [];

        $rows = [];

        foreach ($this->fieldRepository->indexableFor($record->entity_type_id) as $field) {
            if (! $field->value_per_locale) {
                array_push($rows, ...$this->rowsForValue($record, $field, null, $values['common'][$field->code] ?? null));

                continue;
            }

            foreach ($values['locale_specific'] ?? [] as $locale => $localeValues) {
                if (! is_string($locale) || strlen($locale) > 16 || ! is_array($localeValues)) {
                    continue;
                }

                array_push($rows, ...$this->rowsForValue($record, $field, $locale, $localeValues[$field->code] ?? null));
            }
        }

        return $rows;
    }

    /**
     * Build one row per element of a list value (multiselect), or one row for any other value.
     *
     * @return list<array<string, mixed>>
     */
    protected function rowsForValue(Record $record, Field $field, ?string $locale, mixed $value): array
    {
        return array_map(fn (array $columns): array => [
            'record_id'      => $record->getKey(),
            'entity_type_id' => $record->entity_type_id,
            'field_code'     => $field->code,
            'locale'         => $locale,
        ] + $columns, $this->columnsFor($field, $value));
    }

    /**
     * Map an index value onto the value columns, or null when it holds nothing searchable.
     *
     * @return array{value_normalized: ?string, value_numeric: ?string, value_date: ?string}|null
     */
    protected function columns(IndexValue $value): ?array
    {
        $text = $value->text === null ? '' : mb_substr($this->normalizer->normalize($value->text), 0, 255);

        $numeric = $value->numeric !== null && is_numeric($value->numeric) && abs((float) $value->numeric) < self::NUMERIC_LIMIT
            ? $value->numeric
            : null;

        $columns = [
            'value_normalized' => $text === '' ? null : $text,
            'value_numeric'    => $numeric,
            'value_date'       => $value->date,
        ];

        return array_filter($columns, fn (?string $column): bool => $column !== null) === [] ? null : $columns;
    }

    /**
     * The field's type when its values are kept in the record's values JSON, else null.
     */
    protected function indexableType(Field $field): ?FieldType
    {
        if (! $this->fieldTypeRegistry->has($field->type)) {
            return null;
        }

        $type = $this->fieldTypeRegistry->get($field->type);

        return $type->storesInValues() ? $type : null;
    }
}
