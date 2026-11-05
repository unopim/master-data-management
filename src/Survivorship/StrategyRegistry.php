<?php

namespace Webkul\MasterDataManagement\Survivorship;

use Illuminate\Support\Facades\Lang;
use InvalidArgumentException;
use Webkul\MasterDataManagement\Survivorship\Contracts\SurvivorshipStrategy;

/**
 * The survivorship strategies of `masterdata.survivorship.strategies`, code => class, resolved from the container so
 * a package adds its own by extending the config.
 */
class StrategyRegistry
{
    /**
     * The registered strategy codes.
     *
     * @return list<string>
     */
    public function codes(): array
    {
        return array_values(array_map(strval(...), array_keys($this->map())));
    }

    /**
     * Whether a strategy is registered under the code.
     */
    public function has(string $code): bool
    {
        return array_key_exists($code, $this->map());
    }

    /**
     * Resolve the strategy registered under the code.
     *
     * @throws InvalidArgumentException When no strategy is registered under the code.
     */
    public function get(string $code): SurvivorshipStrategy
    {
        $class = $this->map()[$code] ?? null;

        $strategy = is_string($class) ? resolve($class) : null;

        if (! $strategy instanceof SurvivorshipStrategy) {
            throw new InvalidArgumentException(sprintf('Master data survivorship strategy [%s] is not registered.', $code));
        }

        return $strategy;
    }

    /**
     * The select options of the registered strategies, labelled from `masterdata::app.survivorship-rules.strategies`
     * and by code when a strategy has no label.
     *
     * @return list<array{id: string, label: string}>
     */
    public function options(): array
    {
        return array_map(fn (string $code): array => ['id' => $code, 'label' => $this->label($code)], $this->codes());
    }

    /**
     * The display name of a strategy.
     */
    public function label(string $code): string
    {
        $key = 'masterdata::app.survivorship-rules.strategies.'.$code;

        return Lang::has($key) ? trans($key) : $code;
    }

    /**
     * @return array<string, mixed>
     */
    protected function map(): array
    {
        return (array) config('masterdata.survivorship.strategies', []);
    }
}
