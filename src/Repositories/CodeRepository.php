<?php

namespace Webkul\MasterDataManagement\Repositories;

use DateTimeInterface;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Webkul\Core\Eloquent\Repository;
use Webkul\MasterDataManagement\Contracts\Code;
use Webkul\MasterDataManagement\Models\Code as CodeModel;

class CodeRepository extends Repository
{
    protected const INSERT_CHUNK = 200;

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
     * The active codes of the version in effect on the date, with their translations, ordered by sort then code.
     *
     * @return Collection<int, CodeModel>
     */
    public function inEffectForVersion(int $versionId, DateTimeInterface $on): Collection
    {
        $date = $on->format('Y-m-d');

        return $this->model->newQuery()
            ->with('translations')
            ->where('version_id', $versionId)
            ->where('is_active', true)
            ->where(fn (Builder $query) => $query->whereNull('effective_from')->orWhere('effective_from', '<=', $date))
            ->where(fn (Builder $query) => $query->whereNull('effective_to')->orWhere('effective_to', '>', $date))
            ->orderBy('sort')
            ->orderBy('code')
            ->get();
    }

    /**
     * Insert the codes into the version in their given order, with a label per locale, in chunked bulk inserts.
     *
     * @param  list<array{code: string, labels: array<string, string>}>  $codes  Labels keyed by locale.
     */
    public function insertLabelled(int $versionId, array $codes): void
    {
        $sort = 0;

        foreach (array_chunk($codes, self::INSERT_CHUNK) as $chunk) {
            $rows = [];

            foreach ($chunk as $code) {
                $rows[] = [
                    'version_id' => $versionId,
                    'code'       => $code['code'],
                    'sort'       => ++$sort,
                    'is_active'  => true,
                ];
            }

            DB::table('mdm_codes')->insert($rows);

            $ids = DB::table('mdm_codes')
                ->where('version_id', $versionId)
                ->whereIn('code', array_column($chunk, 'code'))
                ->pluck('id', 'code');

            $translations = [];

            foreach ($chunk as $code) {
                foreach ($code['labels'] as $locale => $label) {
                    $translations[] = [
                        'code_id' => $ids[$code['code']],
                        'locale'  => $locale,
                        'label'   => $label,
                    ];
                }
            }

            foreach (array_chunk($translations, self::INSERT_CHUNK * 5) as $translationChunk) {
                DB::table('mdm_code_translations')->insert($translationChunk);
            }
        }
    }
}
