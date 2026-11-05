<?php

namespace Webkul\MasterDataManagement\Export;

use Illuminate\Support\Collection;
use Webkul\MasterDataManagement\Models\Field;

/**
 * Flattens published record rows into fixed CSV columns: one per common field (`value_{code}`), one per field and
 * locale for per-locale fields (`value_{code}_{locale}`), and one per source system (`xref_{system}`).
 */
class RecordCsvFlattener
{
    public const string LIST_SEPARATOR = '|';

    /**
     * @var list<array{column: string, field: string, locale: ?string}>
     */
    protected array $valueColumns = [];

    /**
     * @var list<string>
     */
    protected array $systemCodes = [];

    /**
     * Fix the column layout for the fields, locales and source system codes of one export.
     *
     * @param  Collection<int, Field>  $fields
     * @param  list<string>  $locales
     * @param  list<string>  $systemCodes
     */
    public function layout(Collection $fields, array $locales, array $systemCodes): static
    {
        $this->valueColumns = [];

        foreach ($fields as $field) {
            if (! $field->value_per_locale) {
                $this->valueColumns[] = ['column' => 'value_'.$field->code, 'field' => $field->code, 'locale' => null];

                continue;
            }

            foreach ($locales as $locale) {
                $this->valueColumns[] = ['column' => 'value_'.$field->code.'_'.$locale, 'field' => $field->code, 'locale' => $locale];
            }
        }

        sort($systemCodes);

        $this->systemCodes = array_values($systemCodes);

        return $this;
    }

    /**
     * The CSV header in column order.
     *
     * @return list<string>
     */
    public function columns(): array
    {
        return array_merge(
            ['uuid', 'code', 'label', 'status', 'entity_type', 'parent_code', 'path_codes'],
            array_column($this->valueColumns, 'column'),
            array_map(fn (string $code): string => 'xref_'.$code, $this->systemCodes),
            ['completeness', 'updated_at'],
        );
    }

    /**
     * Flatten one published row into the layout's columns.
     *
     * @param  array<string, mixed>  $row
     * @return array<string, scalar|null>
     */
    public function flatten(array $row): array
    {
        $flat = [
            'uuid'        => $row['uuid'] ?? null,
            'code'        => $row['code'] ?? null,
            'label'       => $row['label'] ?? null,
            'status'      => $row['status'] ?? null,
            'entity_type' => $row['entity_type'] ?? null,
            'parent_code' => $row['parent_code'] ?? null,
            'path_codes'  => implode('/', (array) ($row['path_codes'] ?? [])),
        ];

        $values = (array) ($row['values'] ?? []);

        foreach ($this->valueColumns as $column) {
            $value = $values[$column['field']] ?? null;

            if ($column['locale'] !== null) {
                $value = is_array($value) ? ($value[$column['locale']] ?? null) : null;
            }

            $flat[$column['column']] = $this->cell($value);
        }

        $xrefs = (array) ($row['xrefs'] ?? []);

        foreach ($this->systemCodes as $code) {
            $flat['xref_'.$code] = implode(self::LIST_SEPARATOR, (array) ($xrefs[$code] ?? []));
        }

        $flat['completeness'] = $row['completeness'] ?? null;
        $flat['updated_at'] = $row['updated_at'] ?? null;

        return $flat;
    }

    /**
     * One value as a CSV cell: lists are joined, structured values such as measurements are JSON encoded.
     *
     * @return scalar|null
     */
    protected function cell(mixed $value): mixed
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_array($value)) {
            return array_is_list($value) && array_filter($value, is_array(...)) === []
                ? implode(self::LIST_SEPARATOR, array_map(strval(...), $value))
                : json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        return is_scalar($value) ? $value : null;
    }
}
