<?php

namespace Webkul\MasterDataManagement\Services;

use InvalidArgumentException;

class TemplateReader
{
    /**
     * The locale every template label carries; a locale without its own label falls back to it.
     */
    public const SOURCE_LOCALE = 'en_US';

    protected const SUPPORTED_FORMAT_VERSION = 1;

    protected const CODE_LISTS_DIRECTORY = 'code-lists';

    /**
     * Read a starter template by name from the package templates directory.
     *
     * @return array{format_version: int, names?: array<string, string>, entity_types?: list<array<string, mixed>>, source_systems?: list<array<string, mixed>>, samples?: list<array<string, mixed>>}
     *
     * @throws InvalidArgumentException
     */
    public function read(string $name): array
    {
        return $this->decode($this->directory().'/'.basename($name).'.json', $name);
    }

    /**
     * Read a shared code list by its code; templates name the lists their select fields draw from.
     *
     * @return array<string, mixed>
     *
     * @throws InvalidArgumentException
     */
    public function readCodeList(string $code): array
    {
        return $this->decode($this->codeListPath($code), $code);
    }

    /**
     * Whether a shared code list with the code ships with the package.
     */
    public function hasCodeList(string $code): bool
    {
        return is_file($this->codeListPath($code));
    }

    /**
     * The names of the templates that ship with the package, sorted.
     *
     * @return list<string>
     */
    public function available(): array
    {
        $names = array_map(
            fn (string $path): string => basename($path, '.json'),
            glob($this->directory().'/*.json') ?: [],
        );

        sort($names);

        return $names;
    }

    /**
     * The template labels as translated attributes of the given locales, each falling back to the source locale.
     *
     * @param  array<string, string>  $labels
     * @param  list<string>  $locales
     * @return array<string, array<string, string>>
     */
    public function localized(string $attribute, array $labels, array $locales): array
    {
        $translated = [];

        foreach ($locales as $locale) {
            $label = $labels[$locale] ?? $labels[self::SOURCE_LOCALE] ?? null;

            if (is_string($label) && $label !== '') {
                $translated[$locale] = [$attribute => $label];
            }
        }

        return $translated;
    }

    /**
     * @return array<string, mixed>
     *
     * @throws InvalidArgumentException
     */
    protected function decode(string $path, string $name): array
    {
        $data = is_file($path) ? json_decode((string) file_get_contents($path), true) : null;

        if (! is_array($data) || ($data['format_version'] ?? null) !== self::SUPPORTED_FORMAT_VERSION) {
            throw new InvalidArgumentException(trans('masterdata::app.demo.invalid-template', ['name' => $name]));
        }

        return $data;
    }

    protected function codeListPath(string $code): string
    {
        return $this->directory().'/'.self::CODE_LISTS_DIRECTORY.'/'.basename($code).'.json';
    }

    protected function directory(): string
    {
        return __DIR__.'/../Resources/templates';
    }
}
