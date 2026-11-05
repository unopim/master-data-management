<?php

namespace Webkul\MasterDataManagement\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Webkul\Core\Eloquent\Repository;
use Webkul\Core\RequestMemo;
use Webkul\MasterDataManagement\Contracts\SurvivorshipRule;
use Webkul\MasterDataManagement\Models\SurvivorshipRule as SurvivorshipRuleModel;

class SurvivorshipRuleRepository extends Repository
{
    /**
     * The most rules one entity type holds.
     */
    public const int MAX_RULES = 200;

    /**
     * Get model class name.
     */
    public function model(): string
    {
        return SurvivorshipRule::class;
    }

    /**
     * The survivorship rules of the entity type in their sort order.
     *
     * Memoised in the scoped request memo under `masterdata.survivorship_rules.{id}`; resolved per call so a
     * long-lived service never reads another request's memo.
     *
     * @return Collection<int, SurvivorshipRuleModel>
     */
    public function forEntityType(int $entityTypeId): Collection
    {
        $key = 'masterdata.survivorship_rules.'.$entityTypeId;

        $memo = resolve(RequestMemo::class);

        if ($memo->has($key)) {
            return $memo->get($key);
        }

        $rules = $this->model->newQuery()
            ->where('entity_type_id', $entityTypeId)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->limit(self::MAX_RULES)
            ->get();

        $memo->set($key, $rules);

        return $rules;
    }

    /**
     * Find a survivorship rule of the entity type.
     */
    public function findFor(int $entityTypeId, int $id): ?SurvivorshipRuleModel
    {
        return $this->model->newQuery()
            ->where('entity_type_id', $entityTypeId)
            ->find($id);
    }

    /**
     * Whether another rule of the entity type already covers the target; a null target is the default rule.
     */
    public function targetTaken(int $entityTypeId, ?string $target, ?int $ignoreId = null): bool
    {
        return $this->model->newQuery()
            ->where('entity_type_id', $entityTypeId)
            ->when($target === null, fn ($query) => $query->whereNull('target'), fn ($query) => $query->where('target', $target))
            ->when($ignoreId !== null, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists();
    }

    /**
     * Count the survivorship rules of the entity type.
     */
    public function countFor(int $entityTypeId): int
    {
        return $this->model->newQuery()->where('entity_type_id', $entityTypeId)->count();
    }

    /**
     * Create a rule and drop the memoised rule list of its type.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): SurvivorshipRuleModel
    {
        $rule = parent::create($attributes);

        $this->forget((int) $rule->entity_type_id);

        return $rule;
    }

    /**
     * Update a rule and drop the memoised rule list of its type.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function update(array $attributes, $id): SurvivorshipRuleModel
    {
        $rule = parent::update($attributes, $id);

        $this->forget((int) $rule->entity_type_id);

        return $rule;
    }

    /**
     * Delete a rule and drop the memoised rule list of its type.
     */
    public function delete($id): bool
    {
        $rule = $this->model->newQuery()->find($id);

        $deleted = (bool) parent::delete($id);

        if ($rule instanceof SurvivorshipRuleModel) {
            $this->forget((int) $rule->entity_type_id);
        }

        return $deleted;
    }

    /**
     * Drop the memoised rule list of the entity type.
     */
    public function forget(int $entityTypeId): void
    {
        resolve(RequestMemo::class)->forget('masterdata.survivorship_rules.'.$entityTypeId);
    }
}
