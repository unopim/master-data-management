<?php

namespace Webkul\MasterDataManagement\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Webkul\Core\Eloquent\Repository;
use Webkul\MasterDataManagement\Contracts\SourceSystem;
use Webkul\MasterDataManagement\Models\SourceSystem as SourceSystemModel;
use Webkul\MasterDataManagement\Repositories\Concerns\PagesSelectOptions;

class SourceSystemRepository extends Repository
{
    use PagesSelectOptions;

    /**
     * Get model class name.
     */
    public function model(): string
    {
        return SourceSystem::class;
    }

    /**
     * Whether any crosswalk entry references the source system.
     */
    public function hasXrefs(int $id): bool
    {
        return DB::table('mdm_xrefs')->where('source_system_id', $id)->exists();
    }

    /**
     * One page of source system options for the admin async select, searched on code and name.
     *
     * @param  list<int>  $ids  The ids a picker restores; when given, their options are returned instead.
     * @return array{options: list<array{id: int, label: string}>, page: int, lastPage: int}
     */
    public function searchOptions(?string $term, array $ids, int $page, int $perPage, bool $activeOnly = true): array
    {
        return $this->optionsPage($this->optionsQuery($activeOnly), ['code', 'name'], $term, $ids, $page, $perPage, $this->toOption(...));
    }

    /**
     * One page of active source system options keyed by code, for filters that store the code.
     *
     * @param  list<string>  $codes  The codes a picker restores; when given, their options are returned instead.
     * @return array{options: list<array<string, mixed>>, page: int, lastPage: int}
     */
    public function searchCodeOptions(?string $term, array $codes, int $page, int $perPage): array
    {
        return $this->optionsPage(
            $this->optionsQuery(true),
            ['code', 'name'],
            $term,
            $codes,
            $page,
            $perPage,
            fn (SourceSystemModel $system): array => ['code' => (string) $system->code, 'label' => $this->optionLabel($system)],
            'code',
        );
    }

    /**
     * The options of the given source system ids, in priority then code order.
     *
     * @param  list<int>  $ids
     * @return list<array{id: int, label: string}>
     */
    public function optionsFor(array $ids): array
    {
        return $ids === [] ? [] : $this->searchOptions(null, $ids, 1, count($ids), false)['options'];
    }

    /**
     * The label a source system shows in selects and messages: its name and code.
     */
    public function optionLabel(SourceSystemModel $system): string
    {
        return trans('masterdata::app.source-systems.option-label', ['name' => $system->name, 'code' => $system->code]);
    }

    /**
     * The source systems ordered for a select, optionally only the active ones.
     *
     * @return Builder<SourceSystemModel>
     */
    protected function optionsQuery(bool $activeOnly): Builder
    {
        return $this->model->newQuery()
            ->select(['id', 'code', 'name'])
            ->when($activeOnly, fn ($query) => $query->where('is_active', true))
            ->orderBy('priority')
            ->orderBy('code');
    }

    /**
     * A source system as a select option of id and label.
     *
     * @return array{id: int, label: string}
     */
    protected function toOption(SourceSystemModel $system): array
    {
        return ['id' => (int) $system->id, 'label' => $this->optionLabel($system)];
    }

    /**
     * Map every source system code to its name.
     *
     * @return array<string, string>
     */
    public function namesByCode(): array
    {
        return $this->model->newQuery()->pluck('name', 'code')->all();
    }

    /**
     * Map the given source system ids that exist to their priority.
     *
     * @param  list<int>  $ids
     * @return array<int, int>
     */
    public function prioritiesById(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        return $this->model->newQuery()
            ->whereIn('id', $ids)
            ->pluck('priority', 'id')
            ->mapWithKeys(fn ($priority, $id): array => [(int) $id => (int) $priority])
            ->all();
    }

    /**
     * Map the given source system ids that exist to their codes, ordered by code.
     *
     * @param  list<int>  $ids
     * @return array<int, string>
     */
    public function codesById(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        return $this->model->newQuery()
            ->whereIn('id', $ids)
            ->orderBy('code')
            ->pluck('code', 'id')
            ->mapWithKeys(fn ($code, $id): array => [(int) $id => (string) $code])
            ->all();
    }
}
