<?php

namespace Webkul\MasterDataManagement\Importers\Record;

/**
 * The record importer's column mapping filter: one `column=target` per line, where the target is a record column
 * (`code`, `label`, `status`, `parent_code`, `external_id`), a field code, or `field_code-locale` for one locale of a
 * per-locale field. Columns without a line are read by their own name.
 */
final readonly class ColumnMapping
{
    public const array RECORD_COLUMNS = ['code', 'label', 'status', 'parent_code', 'external_id'];

    /**
     * @param  array<string, string>  $targets  File column => target.
     * @param  list<int>  $invalidLines  Line numbers that are not `column=target`.
     */
    private function __construct(
        public array $targets,
        public array $invalidLines,
    ) {}

    /**
     * Parse the filter value; blank lines are ignored and a later line for the same column wins.
     */
    public static function parse(?string $mapping): self
    {
        $targets = [];

        $invalidLines = [];

        foreach (preg_split('/\R/', (string) $mapping) ?: [] as $index => $line) {
            if (trim($line) === '') {
                continue;
            }

            [$column, $target] = array_map(trim(...), explode('=', $line, 2) + [1 => '']);

            if ($column === '' || $target === '') {
                $invalidLines[] = $index + 1;

                continue;
            }

            $targets[$column] = $target;
        }

        return new self($targets, $invalidLines);
    }

    /**
     * The target a file column is read into.
     */
    public function targetOf(string $column): string
    {
        return $this->targets[$column] ?? $column;
    }

    /**
     * Split a field target into its field code and locale; the locale is null for a common value.
     *
     * @return array{0: string, 1: ?string}
     */
    public static function splitFieldTarget(string $target): array
    {
        $parts = explode('-', $target, 2);

        return [$parts[0], isset($parts[1]) && $parts[1] !== '' ? $parts[1] : null];
    }
}
