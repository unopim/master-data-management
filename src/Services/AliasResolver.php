<?php

namespace Webkul\MasterDataManagement\Services;

use Illuminate\Support\Collection;
use Webkul\MasterDataManagement\Enums\AliasScope;
use Webkul\MasterDataManagement\Repositories\AliasRepository;

class AliasResolver
{
    public function __construct(
        protected AliasRepository $aliasRepository,
        protected Normalizer $normalizer
    ) {}

    /**
     * Resolve a term to its target code or canonical value, or null when no alias matches.
     *
     * Priority: source-specific scope alias, scope alias, source-specific global alias, global alias.
     * The lowest id wins inside a tier, so rows duplicated through NULL keys resolve deterministically.
     */
    public function resolve(string $term, AliasScope $scope, ?int $scopeId, ?int $sourceSystemId = null): ?string
    {
        return $this->resolveMany([$term], $scope, $scopeId, $sourceSystemId)[$term] ?? null;
    }

    /**
     * Resolve several terms of one scope in a single query, with the priority of resolve().
     *
     * @param  list<string>  $terms
     * @return array<string, string> The target of each term that an alias matches, keyed by the term as given.
     */
    public function resolveMany(array $terms, AliasScope $scope, ?int $scopeId, ?int $sourceSystemId = null): array
    {
        $normalizedByTerm = [];

        foreach ($terms as $term) {
            $normalizedByTerm[$term] = $this->normalizer->normalize($term);
        }

        if ($normalizedByTerm === []) {
            return [];
        }

        $candidates = $this->aliasRepository->getModel()->newQuery()
            ->whereIn('normalized_term', array_values(array_unique($normalizedByTerm)))
            ->where(function ($query) use ($scope, $scopeId): void {
                $query->where(function ($query) use ($scope, $scopeId): void {
                    $query->where('scope_type', $scope->value);

                    $scopeId === null ? $query->whereNull('scope_id') : $query->where('scope_id', $scopeId);
                })->orWhere(function ($query): void {
                    $query->where('scope_type', AliasScope::Global->value)->whereNull('scope_id');
                });
            })
            ->where(function ($query) use ($sourceSystemId): void {
                $query->whereNull('source_system_id');

                if ($sourceSystemId !== null) {
                    $query->orWhere('source_system_id', $sourceSystemId);
                }
            })
            ->orderBy('id')
            ->get(['id', 'normalized_term', 'scope_type', 'scope_id', 'source_system_id', 'target'])
            ->groupBy('normalized_term');

        $targets = [];

        foreach ($normalizedByTerm as $term => $normalized) {
            $target = $this->pick($candidates->get($normalized) ?? new Collection, $scope, $scopeId, $sourceSystemId);

            if ($target !== null) {
                $targets[(string) $term] = $target;
            }
        }

        return $targets;
    }

    /**
     * The target of the highest-priority candidate of one normalised term.
     *
     * @param  Collection<int, mixed>  $candidates
     */
    protected function pick(Collection $candidates, AliasScope $scope, ?int $scopeId, ?int $sourceSystemId): ?string
    {
        $tiers = [
            [$scope, $scopeId, $sourceSystemId],
            [$scope, $scopeId, null],
            [AliasScope::Global, null, $sourceSystemId],
            [AliasScope::Global, null, null],
        ];

        foreach ($tiers as [$tierScope, $tierScopeId, $tierSource]) {
            $match = $candidates->first(
                fn ($alias): bool => $alias->scope_type === $tierScope
                    && $alias->scope_id === $tierScopeId
                    && $alias->source_system_id === $tierSource
            );

            if ($match) {
                return $match->target;
            }
        }

        return null;
    }
}
