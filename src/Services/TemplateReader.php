<?php

namespace Webkul\MasterDataManagement\Services;

use InvalidArgumentException;

class TemplateReader
{
    protected const SUPPORTED_FORMAT_VERSION = 1;

    /**
     * Read a starter template by name from the package templates directory.
     *
     * @return array{format_version: int, entity_types?: list<array<string, mixed>>, source_systems?: list<array<string, mixed>>}
     *
     * @throws InvalidArgumentException
     */
    public function read(string $name): array
    {
        $path = dirname(__DIR__).'/Resources/templates/'.basename($name).'.json';

        $data = is_file($path) ? json_decode((string) file_get_contents($path), true) : null;

        if (! is_array($data) || ($data['format_version'] ?? null) !== self::SUPPORTED_FORMAT_VERSION) {
            throw new InvalidArgumentException(trans('masterdata::app.demo.invalid-template', ['name' => $name]));
        }

        return $data;
    }
}
