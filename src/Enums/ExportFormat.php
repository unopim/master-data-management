<?php

namespace Webkul\MasterDataManagement\Enums;

enum ExportFormat: string
{
    case Jsonl = 'Jsonl';
    case Csv = 'Csv';
    case Xlsx = 'Xlsx';

    /**
     * The file extension of the format.
     */
    public function extension(): string
    {
        return strtolower($this->value);
    }

    /**
     * Whether the format is a flat file of fixed columns written through the core spreadsheet writer.
     */
    public function isFlat(): bool
    {
        return $this !== self::Jsonl;
    }

    /**
     * Every format value.
     *
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
