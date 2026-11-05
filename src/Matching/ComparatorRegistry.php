<?php

namespace Webkul\MasterDataManagement\Matching;

use Illuminate\Support\Facades\Lang;
use InvalidArgumentException;
use Webkul\MasterDataManagement\Matching\Contracts\Comparator;

/**
 * The comparators of `masterdata.matching.comparators`, code => class, resolved from the container so a package
 * adds its own by extending the config.
 */
class ComparatorRegistry
{
    /**
     * The registered comparator codes.
     *
     * @return list<string>
     */
    public function codes(): array
    {
        return array_values(array_map(strval(...), array_keys($this->map())));
    }

    /**
     * Whether a comparator is registered under the code.
     */
    public function has(string $code): bool
    {
        return array_key_exists($code, $this->map());
    }

    /**
     * Resolve the comparator registered under the code.
     *
     * @throws InvalidArgumentException When no comparator is registered under the code.
     */
    public function get(string $code): Comparator
    {
        $class = $this->map()[$code] ?? null;

        $comparator = is_string($class) ? resolve($class) : null;

        if (! $comparator instanceof Comparator) {
            throw new InvalidArgumentException(sprintf('Master data comparator [%s] is not registered.', $code));
        }

        return $comparator;
    }

    /**
     * The select options of the registered comparators, labelled from `masterdata::app.match-rules.comparators`
     * and by code when a comparator has no label.
     *
     * @return list<array{id: string, label: string}>
     */
    public function options(): array
    {
        return array_map(fn (string $code): array => ['id' => $code, 'label' => $this->label($code)], $this->codes());
    }

    /**
     * The display name of a comparator.
     */
    public function label(string $code): string
    {
        $key = 'masterdata::app.match-rules.comparators.'.$code;

        return Lang::has($key) ? trans($key) : $code;
    }

    /**
     * @return array<string, mixed>
     */
    protected function map(): array
    {
        return (array) config('masterdata.matching.comparators', []);
    }
}
