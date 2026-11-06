<?php

namespace Webkul\MasterDataManagement\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Webkul\Core\Eloquent\Repository;
use Webkul\MasterDataManagement\Contracts\EntityType;
use Webkul\MasterDataManagement\Enums\EntityTypeStatus;
use Webkul\MasterDataManagement\Models\EntityType as EntityTypeModel;

class EntityTypeRepository extends Repository
{
    /**
     * Get model class name.
     */
    public function model(): string
    {
        return EntityType::class;
    }

    /**
     * List the active entity types with their translations, in display order.
     *
     * @param  list<int>|null  $ids  Limit the list to these types; null lists every active type.
     * @return Collection<int, EntityTypeModel>
     */
    public function listActive(?array $ids = null): Collection
    {
        return $this->model->newQuery()
            ->with(['translations', 'libraryIcon'])
            ->where('status', EntityTypeStatus::Active->value)
            ->when($ids !== null, fn ($query) => $query->whereIn('id', $ids))
            ->ordered()
            ->get();
    }

    /**
     * The ids of the entity types whose records are restricted to the subtrees assigned to each admin.
     *
     * @return list<int>
     */
    public function scopedIds(): array
    {
        return $this->model->newQuery()
            ->where('is_scoped', true)
            ->pluck('id')
            ->map(fn (mixed $id): int => (int) $id)
            ->values()
            ->all();
    }

    /**
     * Store when the entity type's records were last quality scanned, without touching its history or updated_at.
     */
    public function markQualityScanned(int $id): void
    {
        $this->model->newQuery()->whereKey($id)->toBase()->update(['quality_scanned_at' => now()]);
    }

    /**
     * When each entity type was last quality scanned, keyed by id; types never scanned are left out.
     *
     * @param  list<int>  $ids
     * @return array<int, string>
     */
    public function qualityScannedAt(array $ids): array
    {
        return $this->model->newQuery()
            ->whereIn('id', $ids)
            ->whereNotNull('quality_scanned_at')
            ->toBase()
            ->pluck('quality_scanned_at', 'id')
            ->map(fn (mixed $value): string => (string) $value)
            ->all();
    }

    /**
     * The active entity types as select options of id and name, limited to the given ids when not null.
     *
     * @param  list<int>|null  $ids
     * @return list<array{id: int, label: string}>
     */
    public function options(?array $ids = null): array
    {
        return $this->listActive($ids)
            ->map(fn (EntityTypeModel $type): array => ['id' => (int) $type->id, 'label' => $type->name ?: $type->code])
            ->values()
            ->all();
    }

    /**
     * Names of the given entity types of any status keyed by id, falling back to the code when untranslated.
     *
     * @param  list<int>  $ids
     * @return array<int, string>
     */
    public function namesByIds(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        return $this->model->newQuery()
            ->with('translations')
            ->whereIn('id', $ids)
            ->get()
            ->mapWithKeys(fn (EntityTypeModel $type): array => [(int) $type->id => (string) ($type->name ?: $type->code)])
            ->all();
    }

    /**
     * Entity types of any status as select options of id and code, in display order, optionally without one type.
     *
     * @return list<array{id: int, label: string}>
     */
    public function codeOptions(int $limit, ?int $exceptId = null): array
    {
        return $this->model->newQuery()
            ->when($exceptId !== null, fn ($query) => $query->whereKeyNot($exceptId))
            ->ordered()
            ->limit($limit)
            ->get(['id', 'code'])
            ->map(fn (EntityTypeModel $type): array => ['id' => (int) $type->id, 'label' => (string) $type->code])
            ->values()
            ->all();
    }

    /**
     * Entity types of any status with their names and active fields (labels, code list, reference type, validation
     * rules and transforms loaded), in display order, for the REST schema endpoints.
     *
     * @param  list<int>|null  $ids  Limit the list to these types; null lists every type.
     * @return Collection<int, EntityTypeModel>
     */
    public function listWithSchema(?array $ids = null, ?string $code = null): Collection
    {
        return $this->model->newQuery()
            ->with([
                'translations',
                'fields' => fn ($query) => $query->where('is_archived', false)->orderBy('position')->orderBy('id'),
                'fields.translations',
                'fields.codeList:id,code',
                'fields.referenceEntityType:id,code',
                'fields.validationRules',
                'fields.transforms',
                'matchRules',
                'survivorshipRules',
            ])
            ->when($ids !== null, fn ($query) => $query->whereIn('id', $ids))
            ->when($code !== null, fn ($query) => $query->where('code', $code))
            ->ordered()
            ->get();
    }

    /**
     * Codes of the given entity types keyed by id.
     *
     * @param  list<int>  $ids
     * @return array<int, string>
     */
    public function codesByIds(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        return $this->model->newQuery()
            ->whereIn('id', $ids)
            ->toBase()
            ->pluck('code', 'id')
            ->mapWithKeys(fn (mixed $code, mixed $id): array => [(int) $id => (string) $code])
            ->all();
    }

    /**
     * Find the entity type under a row lock; call it inside a transaction.
     */
    public function findForUpdate(int $id): EntityTypeModel
    {
        return $this->model->newQuery()->lockForUpdate()->findOrFail($id);
    }

    /**
     * Allocate the next record code for the entity type and format it with the type's code pattern.
     *
     * The sequence row is created idempotently and then read under a row lock so concurrent
     * workers on any supported engine never hand out the same number.
     */
    public function nextCode(EntityTypeModel $type): string
    {
        $next = DB::transaction(function () use ($type): int {
            DB::table('mdm_code_sequences')->insertOrIgnore([
                'entity_type_id' => $type->id,
                'last_value'     => 0,
            ]);

            $current = (int) DB::table('mdm_code_sequences')
                ->where('entity_type_id', $type->id)
                ->lockForUpdate()
                ->value('last_value');

            DB::table('mdm_code_sequences')
                ->where('entity_type_id', $type->id)
                ->update(['last_value' => $current + 1]);

            return $current + 1;
        });

        return $this->formatCode($type, $next);
    }

    /**
     * Read the number the next generated record code will use, without allocating it.
     */
    public function nextSequenceValue(EntityTypeModel $type): int
    {
        if (! $type->exists) {
            return 1;
        }

        return (int) DB::table('mdm_code_sequences')
            ->where('entity_type_id', $type->id)
            ->value('last_value') + 1;
    }

    /**
     * Expand the {TYPE} and {seq:N} placeholders of the type's code pattern.
     */
    protected function formatCode(EntityTypeModel $type, int $sequence): string
    {
        $pattern = str_replace('{TYPE}', strtoupper($type->code), $type->code_pattern);

        return (string) preg_replace_callback(
            '/\{seq(?::(\d{1,2}))?\}/',
            fn (array $match): string => str_pad((string) $sequence, (int) ($match[1] ?? 0), '0', STR_PAD_LEFT),
            $pattern
        );
    }
}
