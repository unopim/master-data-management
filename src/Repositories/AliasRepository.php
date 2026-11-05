<?php

namespace Webkul\MasterDataManagement\Repositories;

use Illuminate\Container\Container;
use Webkul\Core\Eloquent\Repository;
use Webkul\MasterDataManagement\Contracts\Alias;
use Webkul\MasterDataManagement\Enums\AliasScope;
use Webkul\MasterDataManagement\Services\Normalizer;

class AliasRepository extends Repository
{
    public function __construct(
        protected Normalizer $normalizer,
        Container $container
    ) {
        parent::__construct($container);
    }

    /**
     * Get model class name.
     */
    public function model(): string
    {
        return Alias::class;
    }

    /**
     * Create an alias, deriving its normalised term.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes)
    {
        return parent::create($this->withNormalizedTerm($attributes));
    }

    /**
     * Update an alias, re-deriving its normalised term when the term changes.
     *
     * @param  array<string, mixed>  $attributes
     * @param  int  $id
     */
    public function update(array $attributes, $id)
    {
        return parent::update($this->withNormalizedTerm($attributes), $id);
    }

    /**
     * Whether another alias already maps the normalised term in the scope for the source system.
     *
     * The unique index treats NULL scope ids and source systems as distinct, so NULL is compared as IS NULL here.
     */
    public function duplicateExists(AliasScope $scope, ?int $scopeId, string $normalizedTerm, ?int $sourceSystemId, ?int $ignoreId = null): bool
    {
        return $this->model->newQuery()
            ->where('scope_type', $scope->value)
            ->where('normalized_term', $normalizedTerm)
            ->when($scopeId === null, fn ($query) => $query->whereNull('scope_id'), fn ($query) => $query->where('scope_id', $scopeId))
            ->when($sourceSystemId === null, fn ($query) => $query->whereNull('source_system_id'), fn ($query) => $query->where('source_system_id', $sourceSystemId))
            ->when($ignoreId !== null, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists();
    }

    /**
     * Delete every alias of the scope, which has no foreign key to cascade from.
     */
    public function deleteForScope(AliasScope $scope, int $scopeId): void
    {
        $this->model->newQuery()
            ->where('scope_type', $scope->value)
            ->where('scope_id', $scopeId)
            ->delete();
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    protected function withNormalizedTerm(array $attributes): array
    {
        if (isset($attributes['term']) && is_string($attributes['term'])) {
            $attributes['normalized_term'] = $this->normalizer->normalize($attributes['term']);
        }

        return $attributes;
    }
}
