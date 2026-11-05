<?php

namespace Webkul\MasterDataManagement\Importers\Record;

use Webkul\MasterDataManagement\Enums\AliasScope;
use Webkul\MasterDataManagement\Export\RecordCsvFlattener;
use Webkul\MasterDataManagement\Models\Field;
use Webkul\MasterDataManagement\Services\AliasResolver;

/**
 * Turns import cells into record writer values, in the cell format the record CSV export writes: lists joined with
 * `|`, booleans as `true`/`false`, measurements as JSON (or `12.5 kg`). Select and multiselect terms pass through the
 * code list's aliases first; a term no alias matches is kept as given, so validation reports it.
 */
class CellReader
{
    /**
     * Alias targets by code list id and term; null when no alias matches.
     *
     * @var array<int, array<string, ?string>>
     */
    protected array $aliases = [];

    public function __construct(protected AliasResolver $aliasResolver) {}

    /**
     * Resolve the aliases of the given cells in one query per code list, skipping terms already resolved.
     *
     * @param  list<array{0: Field, 1: string}>  $cells
     */
    public function prime(array $cells, ?int $sourceSystemId): void
    {
        $termsByList = [];

        foreach ($cells as [$field, $raw]) {
            if ($field->code_list_id === null || ! in_array($field->type, ['select', 'multiselect'], true)) {
                continue;
            }

            foreach ($field->type === 'multiselect' ? $this->split($raw) : [trim($raw)] as $term) {
                if ($term !== '' && ! array_key_exists($term, $this->aliases[$field->code_list_id] ?? [])) {
                    $termsByList[$field->code_list_id][$term] = $term;
                }
            }
        }

        foreach ($termsByList as $codeListId => $terms) {
            $terms = array_values(array_map(strval(...), $terms));

            $targets = $this->aliasResolver->resolveMany($terms, AliasScope::CodeList, (int) $codeListId, $sourceSystemId);

            foreach ($terms as $term) {
                $this->aliases[$codeListId][$term] = $targets[$term] ?? null;
            }
        }
    }

    /**
     * The writer value of a cell of the field.
     */
    public function read(Field $field, string $raw): mixed
    {
        $raw = trim($raw);

        return match ($field->type) {
            'select'      => $this->alias($field, $raw),
            'multiselect' => array_map(fn (string $term): string => $this->alias($field, $term), $this->split($raw)),
            'reference'   => $field->multiple ? $this->split($raw) : $raw,
            'boolean'     => $this->boolean($raw),
            'measurement' => $this->measurement($raw),
            default       => $raw,
        };
    }

    /**
     * The alias target of a term of the field's code list, or the term itself.
     */
    protected function alias(Field $field, string $term): string
    {
        return $field->code_list_id === null ? $term : ($this->aliases[$field->code_list_id][$term] ?? $term);
    }

    /**
     * The non-empty, trimmed items of a list cell.
     *
     * @return list<string>
     */
    protected function split(string $raw): array
    {
        return array_values(array_filter(
            array_map(trim(...), explode(RecordCsvFlattener::LIST_SEPARATOR, $raw)),
            fn (string $item): bool => $item !== '',
        ));
    }

    /**
     * The `boolean` rule's form of the export's `true`/`false` and of yes/no; anything else is kept for validation.
     */
    protected function boolean(string $raw): string
    {
        return match (mb_strtolower($raw)) {
            'true', 'yes' => '1',
            'false', 'no' => '0',
            default       => $raw,
        };
    }

    /**
     * A measurement from its JSON form or from `value unit`; anything else is kept for validation.
     */
    protected function measurement(string $raw): mixed
    {
        if (str_starts_with($raw, '{')) {
            $decoded = json_decode($raw, true);

            return is_array($decoded) ? $decoded : $raw;
        }

        if (preg_match('/^(-?\d+(?:\.\d+)?)\s+(\S.*)$/u', $raw, $matches) === 1) {
            return ['value' => $matches[1], 'unit' => $matches[2]];
        }

        return $raw;
    }
}
