<?php

namespace Webkul\MasterDataManagement\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Webkul\Core\Eloquent\Repository;
use Webkul\Core\RequestMemo;
use Webkul\MasterDataManagement\Contracts\MatchRule;
use Webkul\MasterDataManagement\Models\MatchRule as MatchRuleModel;

class MatchRuleRepository extends Repository
{
    /**
     * The most rules one entity type holds.
     */
    public const int MAX_RULES = 50;

    /**
     * Get model class name.
     */
    public function model(): string
    {
        return MatchRule::class;
    }

    /**
     * The match rules of the entity type in their run order.
     *
     * Memoised in the scoped request memo under `masterdata.match_rules.{id}`, since a quality scan asks for every
     * record it checks; resolved per call so a long-lived service never reads another request's memo.
     *
     * @return Collection<int, MatchRuleModel>
     */
    public function forEntityType(int $entityTypeId): Collection
    {
        $key = 'masterdata.match_rules.'.$entityTypeId;

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
     * Find a match rule of the entity type.
     */
    public function findFor(int $entityTypeId, int $id): ?MatchRuleModel
    {
        return $this->model->newQuery()
            ->where('entity_type_id', $entityTypeId)
            ->find($id);
    }

    /**
     * Count the match rules of the entity type.
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
    public function create(array $attributes): MatchRuleModel
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
    public function update(array $attributes, $id): MatchRuleModel
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

        if ($rule instanceof MatchRuleModel) {
            $this->forget((int) $rule->entity_type_id);
        }

        return $deleted;
    }

    /**
     * Drop the memoised rule list of the entity type.
     */
    public function forget(int $entityTypeId): void
    {
        resolve(RequestMemo::class)->forget('masterdata.match_rules.'.$entityTypeId);
    }
}
