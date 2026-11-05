<?php

namespace Webkul\MasterDataManagement\Repositories\Concerns;

use Closure;
use Illuminate\Database\Eloquent\Builder;

/**
 * Answers the admin async select: one page of options matching the search, or the options of the ids the picker
 * restores, in the `{options, page, lastPage}` shape the select handler reads.
 */
trait PagesSelectOptions
{
    /**
     * One page of select options from the query searched on the given columns, or every option of the given ids.
     *
     * The ids a picker restores are primary keys unless another key column, such as the code, is named.
     *
     * @param  list<string>  $searchColumns
     * @param  list<int|string>  $ids
     * @return array{options: list<array<string, mixed>>, page: int, lastPage: int}
     */
    protected function optionsPage(Builder $query, array $searchColumns, ?string $term, array $ids, int $page, int $perPage, Closure $toOption, ?string $keyColumn = null): array
    {
        if ($ids !== []) {
            return [
                'options'  => $query->whereIn($keyColumn ?? $query->getModel()->getQualifiedKeyName(), $ids)->get()->map($toOption)->values()->all(),
                'page'     => 1,
                'lastPage' => 1,
            ];
        }

        if ($term !== null && $term !== '') {
            $like = '%'.addcslashes($term, '%_\\').'%';

            $query->where(function (Builder $inner) use ($searchColumns, $like): void {
                foreach ($searchColumns as $column) {
                    $inner->orWhereLike($column, $like);
                }
            });
        }

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        return [
            'options'  => collect($paginator->items())->map($toOption)->values()->all(),
            'page'     => $paginator->currentPage(),
            'lastPage' => $paginator->lastPage(),
        ];
    }
}
