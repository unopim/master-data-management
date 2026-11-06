<?php

namespace Webkul\MasterDataManagement\Support;

use Illuminate\Support\Facades\Lang;
use InvalidArgumentException;

/**
 * A `code => class` map read from the config and resolved from the container, so a package adds its own entries
 * by extending the config key. Nothing is cached, so the registry is safe to share across requests.
 *
 * @template TContract of object
 */
abstract class ConfigRegistry
{
    /**
     * The config key holding the `code => class` map.
     */
    abstract protected function configKey(): string;

    /**
     * The contract every registered class implements.
     *
     * @return class-string<TContract>
     */
    abstract protected function contract(): string;

    /**
     * The translation key prefix of the entries' display names.
     */
    abstract protected function labelPrefix(): string;

    /**
     * The registered codes, in config order.
     *
     * @return list<string>
     */
    public function codes(): array
    {
        return array_values(array_filter(array_map(strval(...), array_keys($this->map())), $this->has(...)));
    }

    /**
     * Whether a class implementing the contract is registered under the code.
     */
    public function has(string $code): bool
    {
        $class = $this->map()[$code] ?? null;

        return is_string($class) && is_subclass_of($class, $this->contract());
    }

    /**
     * Resolve the entry registered under the code.
     *
     * @return TContract
     *
     * @throws InvalidArgumentException When nothing valid is registered under the code.
     */
    public function get(string $code): object
    {
        if (! $this->has($code)) {
            throw new InvalidArgumentException(sprintf('Nothing is registered under [%s] in [%s].', $code, $this->configKey()));
        }

        return resolve($this->map()[$code]);
    }

    /**
     * The select options of the registered entries.
     *
     * @return list<array{id: string, label: string}>
     */
    public function options(): array
    {
        return array_map(fn (string $code): array => ['id' => $code, 'label' => $this->label($code)], $this->codes());
    }

    /**
     * The translated display name of an entry, or its code when it has none.
     */
    public function label(string $code): string
    {
        $key = $this->labelPrefix().'.'.$code;

        return Lang::has($key) ? trans($key) : $code;
    }

    /**
     * @return array<array-key, mixed>
     */
    protected function map(): array
    {
        return (array) config($this->configKey(), []);
    }
}
