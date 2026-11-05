<?php

namespace Webkul\MasterDataManagement\Enums;

enum ExportFormat: string
{
    case Jsonl = 'Jsonl';
    case Csv = 'Csv';

    /**
     * The file extension of the format.
     */
    public function extension(): string
    {
        return strtolower($this->value);
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
