<?php

namespace Webkul\MasterDataManagement\FieldTypes;

use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;

/**
 * Resolves field types from the `masterdata.field_types` map (`code => class`), which other packages extend.
 * Bound scoped, so resolved types live for one request or job only.
 */
class FieldTypeRegistry
{
    /**
     * @var array<string, FieldType>
     */
    protected array $resolved = [];

    public function __construct(protected Container $container) {}

    /**
     * Resolve the field type registered under the code.
     *
     * @throws InvalidArgumentException When no valid field type is registered under the code.
     */
    public function get(string $code): FieldType
    {
        if (isset($this->resolved[$code])) {
            return $this->resolved[$code];
        }

        $class = $this->map()[$code] ?? null;

        if (! is_string($class) || ! is_subclass_of($class, FieldType::class)) {
            throw new InvalidArgumentException(sprintf('Unknown master data field type [%s].', $code));
        }

        return $this->resolved[$code] = $this->container->make($class);
    }

    /**
     * Whether a field type is registered under the code.
     */
    public function has(string $code): bool
    {
        $class = $this->map()[$code] ?? null;

        return is_string($class) && is_subclass_of($class, FieldType::class);
    }

    /**
     * The registered field type codes.
     *
     * @return list<string>
     */
    public function codes(): array
    {
        return array_values(array_filter(
            array_map(strval(...), array_keys($this->map())),
            $this->has(...)
        ));
    }

    /**
     * The configured `code => class` map.
     *
     * @return array<array-key, mixed>
     */
    protected function map(): array
    {
        return (array) config('masterdata.field_types', []);
    }
}
