<?php

namespace Webkul\MasterDataManagement\Repositories;

use DateTimeInterface;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Collection;
use Webkul\Core\Eloquent\Repository;
use Webkul\MasterDataManagement\Contracts\Code;
use Webkul\MasterDataManagement\Models\Code as CodeModel;
use Webkul\MasterDataManagement\Repositories\Concerns\PagesSelectOptions;

class CodeRepository extends Repository
{
    use PagesSelectOptions;

    /**
     * Get model class name.
     */
    public function model(): string
    {
        return Code::class;
    }

    /**
     * Get the code of the version, or null when it belongs to another version.
     */
    public function findForVersion(int $versionId, int $id): ?CodeModel
    {
        return $this->model->newQuery()
            ->where('version_id', $versionId)
            ->whereKey($id)
            ->first();
    }

    /**
     * Get the codes of the version with the given code values, with their translations.
     *
     * @param  list<string>  $codes
     * @return Collection<string, CodeModel> Keyed by code.
     */
    public function findManyForVersion(int $versionId, array $codes): Collection
    {
        if ($codes === []) {
            return new Collection;
        }

        return $this->model->newQuery()
            ->with('translations')
            ->where('version_id', $versionId)
            ->whereIn('code', array_values(array_unique($codes)))
            ->get()
            ->keyBy('code');
    }

    /**
     * The active codes of the version in effect on the date, as select options labelled in the requested locale.
     *
     * @return list<array{id: string, label: string}>
     */
    public function optionsForVersion(int $versionId, DateTimeInterface $on): array
    {
        return $this->inEffectForVersion($versionId, $on)
            ->map(fn (CodeModel $code): array => [
                'id'    => (string) $code->code,
                'label' => $code->getTranslatedValueWithFallback('label') ?: (string) $code->code,
            ])
            ->values()
            ->all();
    }

    /**
     * One page of the active codes of the version in effect on the date as select options, matching the term on the
     * code or any label, or the options of the given codes when a picker restores them.
     *
     * @param  list<string>  $codes
     * @return array{options: list<array{id: string, label: string}>, page: int, lastPage: int}
     */
    public function searchForVersion(int $versionId, DateTimeInterface $on, ?string $term, array $codes, int $page, int $perPage): array
    {
        $query = $this->inEffectQuery($versionId, $on);

        if ($codes === [] && $term !== null && $term !== '') {
            $like = '%'.addcslashes($term, '%_\\').'%';

            $query->where(fn (EloquentBuilder $match) => $match
                ->whereLike('code', $like)
                ->orWhereHas('translations', fn (EloquentBuilder $translation) => $translation->whereLike('label', $like)));
        }

        return $this->optionsPage(
            $query,
            [],
            null,
            array_values(array_unique($codes)),
            $page,
            $perPage,
            fn (CodeModel $code): array => [
                'id'    => (string) $code->code,
                'label' => $code->getTranslatedValueWithFallback('label') ?: (string) $code->code,
            ],
            'code',
        );
    }

    /**
     * The active codes of the version in effect on the date, with their translations, ordered by sort then code.
     *
     * @return Collection<int, CodeModel>
     */
    public function inEffectForVersion(int $versionId, DateTimeInterface $on): Collection
    {
        return $this->inEffectQuery($versionId, $on)->get();
    }

    /**
     * Start a query over the active codes of the version in effect on the date, with their translations.
     *
     * @return EloquentBuilder<CodeModel>
     */
    protected function inEffectQuery(int $versionId, DateTimeInterface $on): EloquentBuilder
    {
        $date = $on->format('Y-m-d');

        return $this->model->newQuery()
            ->with('translations')
            ->where('version_id', $versionId)
            ->where('is_active', true)
            ->where(fn (Builder $query) => $query->whereNull('effective_from')->orWhere('effective_from', '<=', $date))
            ->where(fn (Builder $query) => $query->whereNull('effective_to')->orWhere('effective_to', '>', $date))
            ->orderBy('sort')
            ->orderBy('code');
    }
}
