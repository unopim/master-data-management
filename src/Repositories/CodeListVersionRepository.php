<?php

namespace Webkul\MasterDataManagement\Repositories;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Collection;
use Webkul\Core\Eloquent\Repository;
use Webkul\MasterDataManagement\Contracts\CodeListVersion;
use Webkul\MasterDataManagement\Enums\CodeListVersionStatus;
use Webkul\MasterDataManagement\Models\CodeListVersion as CodeListVersionModel;

class CodeListVersionRepository extends Repository
{
    /**
     * Get model class name.
     */
    public function model(): string
    {
        return CodeListVersion::class;
    }

    /**
     * Get the version of the list, or null when it belongs to another list.
     */
    public function findForCodeList(int $codeListId, int $id): ?CodeListVersionModel
    {
        return $this->model->newQuery()
            ->where('code_list_id', $codeListId)
            ->whereKey($id)
            ->first();
    }

    /**
     * Get every version of the list, newest first.
     *
     * @return Collection<int, CodeListVersionModel>
     */
    public function allFor(int $codeListId): Collection
    {
        return $this->model->newQuery()
            ->where('code_list_id', $codeListId)
            ->orderByDesc('version')
            ->get();
    }

    /**
     * Whether the list has a draft version.
     */
    public function hasDraft(int $codeListId): bool
    {
        return $this->model->newQuery()
            ->where('code_list_id', $codeListId)
            ->where('status', CodeListVersionStatus::Draft->value)
            ->exists();
    }

    /**
     * Get the draft version of the list, or null when it has none.
     */
    public function findDraft(int $codeListId): ?CodeListVersionModel
    {
        return $this->model->newQuery()
            ->where('code_list_id', $codeListId)
            ->where('status', CodeListVersionStatus::Draft->value)
            ->orderByDesc('version')
            ->first();
    }

    /**
     * Get the latest version of the list that is in effect on the date.
     *
     * Retired versions stay eligible because their closed window keeps them bounded to the past.
     */
    public function effectiveOn(int $codeListId, DateTimeInterface $on): ?CodeListVersionModel
    {
        $date = $on->format('Y-m-d');

        return $this->model->newQuery()
            ->where('code_list_id', $codeListId)
            ->whereIn('status', [CodeListVersionStatus::Published->value, CodeListVersionStatus::Retired->value])
            ->where(fn ($query) => $query->whereNull('effective_from')->orWhere('effective_from', '<=', $date))
            ->where(fn ($query) => $query->whereNull('effective_to')->orWhere('effective_to', '>', $date))
            ->orderByDesc('version')
            ->first();
    }
}
