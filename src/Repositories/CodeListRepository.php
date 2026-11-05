<?php

namespace Webkul\MasterDataManagement\Repositories;

use Illuminate\Container\Container;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Support\Facades\DB;
use Webkul\Core\Eloquent\Repository;
use Webkul\MasterDataManagement\Contracts\CodeList;
use Webkul\MasterDataManagement\Enums\AliasScope;
use Webkul\MasterDataManagement\Models\CodeList as CodeListModel;
use Webkul\MasterDataManagement\Models\CodeListVersion;
use Webkul\MasterDataManagement\Repositories\Concerns\PagesSelectOptions;

class CodeListRepository extends Repository
{
    use PagesSelectOptions;

    protected const COPY_CHUNK = 1000;

    public function __construct(
        protected CodeListVersionRepository $versionRepository,
        protected AliasRepository $aliasRepository,
        Container $container,
    ) {
        parent::__construct($container);
    }

    /**
     * Get model class name.
     */
    public function model(): string
    {
        return CodeList::class;
    }

    /**
     * Whether any field draws its values from the list.
     */
    public function isUsedByFields(int $id): bool
    {
        return DB::table('mdm_fields')->where('code_list_id', $id)->exists();
    }

    /**
     * One page of code list options of id and code for the admin async select, ordered and searched by code.
     *
     * @param  list<int>  $ids  The ids a picker restores; when given, their options are returned instead.
     * @return array{options: list<array{id: int, label: string}>, page: int, lastPage: int}
     */
    public function searchOptions(?string $term, array $ids, int $page, int $perPage): array
    {
        return $this->optionsPage(
            $this->model->newQuery()->select(['id', 'code'])->orderBy('code'),
            ['code'],
            $term,
            $ids,
            $page,
            $perPage,
            fn (CodeListModel $codeList): array => ['id' => (int) $codeList->id, 'label' => (string) $codeList->code],
        );
    }

    /**
     * One page of code list options keyed by code, for filters that store the code.
     *
     * @param  list<string>  $codes  The codes a picker restores; when given, their options are returned instead.
     * @return array{options: list<array<string, mixed>>, page: int, lastPage: int}
     */
    public function searchCodeOptions(?string $term, array $codes, int $page, int $perPage): array
    {
        return $this->optionsPage(
            $this->model->newQuery()->select(['id', 'code'])->orderBy('code'),
            ['code'],
            $term,
            $codes,
            $page,
            $perPage,
            fn (CodeListModel $codeList): array => ['code' => (string) $codeList->code, 'label' => (string) $codeList->code],
            'code',
        );
    }

    /**
     * Create a code list together with its empty first draft.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function createWithDraft(array $attributes): CodeListModel
    {
        return DB::transaction(function () use ($attributes): CodeListModel {
            $codeList = $this->create($attributes);

            $this->createDraftFrom($codeList);

            return $codeList;
        });
    }

    /**
     * Delete a code list and its aliases, which have no foreign key to cascade from.
     */
    public function deleteWithAliases(int $id): void
    {
        DB::transaction(function () use ($id): void {
            $this->aliasRepository->deleteForScope(AliasScope::CodeList, $id);

            $this->delete($id);
        });
    }

    /**
     * Create the next draft version of the list, copying the codes and labels of the base version.
     *
     * The base defaults to the highest existing version; an empty draft is created when there is none.
     */
    public function createDraftFrom(CodeListModel $list, ?CodeListVersion $base = null): CodeListVersion
    {
        return DB::transaction(function () use ($list, $base): CodeListVersion {
            $latest = $this->versionRepository->getModel()->newQuery()
                ->where('code_list_id', $list->id)
                ->lockForUpdate()
                ->orderByDesc('version')
                ->first();

            $base ??= $latest;

            $draft = $this->versionRepository->create([
                'code_list_id' => $list->id,
                'version'      => ($latest?->version ?? 0) + 1,
                'status'       => 'draft',
            ]);

            if ($base !== null) {
                $this->copyCodes($base->id, $draft->id);
            }

            return $draft;
        });
    }

    /**
     * Get the list's draft version, creating the next one from the latest version when the list has none.
     *
     * The list row is locked first, so concurrent callers share one draft instead of each opening their own.
     */
    public function draftFor(CodeListModel $list): CodeListVersion
    {
        return DB::transaction(function () use ($list): CodeListVersion {
            $this->model->newQuery()->whereKey($list->id)->lockForUpdate()->first(['id']);

            return $this->versionRepository->findDraft((int) $list->id) ?? $this->createDraftFrom($list);
        });
    }

    /**
     * Copy the codes and their translations between versions in chunks.
     */
    protected function copyCodes(int $fromVersionId, int $toVersionId): void
    {
        DB::table('mdm_codes')
            ->where('version_id', $fromVersionId)
            ->orderBy('id')
            ->chunkById(self::COPY_CHUNK, function ($codes) use ($toVersionId): void {
                $sourceIdByCode = $codes->pluck('id', 'code');

                DB::table('mdm_codes')->insert($codes->map(fn ($row): array => [
                    'version_id'     => $toVersionId,
                    'code'           => $row->code,
                    'parent_code'    => $row->parent_code,
                    'sort'           => $row->sort,
                    'attributes'     => $row->attributes,
                    'effective_from' => $row->effective_from,
                    'effective_to'   => $row->effective_to,
                    'is_active'      => $row->is_active,
                ])->all());

                $newIdByCode = DB::table('mdm_codes')
                    ->where('version_id', $toVersionId)
                    ->whereIn('code', $sourceIdByCode->keys()->all())
                    ->pluck('id', 'code');

                $translations = DB::table('mdm_code_translations')
                    ->whereIn('code_id', $sourceIdByCode->values()->all())
                    ->get();

                $codeBySourceId = $sourceIdByCode->flip();

                $rows = $translations->map(fn ($row): array => [
                    'code_id' => $newIdByCode[$codeBySourceId[$row->code_id]],
                    'locale'  => $row->locale,
                    'label'   => $row->label,
                ])->all();

                if ($rows !== []) {
                    DB::table('mdm_code_translations')->insert($rows);
                }
            });
    }

    /**
     * Cursor-paginate the code lists with their names, ordered by id.
     *
     * @return CursorPaginator<int, CodeListModel>
     */
    public function cursorWithNames(int $perPage, ?string $cursor = null): CursorPaginator
    {
        return $this->model->newQuery()
            ->with('translations')
            ->orderBy('id')
            ->cursorPaginate($perPage, ['*'], 'cursor', $cursor);
    }

    /**
     * Find a code list by its code with its names.
     */
    public function findByCodeWithNames(string $code): ?CodeListModel
    {
        return $this->model->newQuery()->with('translations')->where('code', $code)->first();
    }
}
