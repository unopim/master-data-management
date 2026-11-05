<?php

namespace Webkul\MasterDataManagement\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as BaseCollection;
use Illuminate\Support\Facades\DB;
use Webkul\Core\Eloquent\Repository;
use Webkul\Core\RequestMemo;
use Webkul\MasterDataManagement\Contracts\Field;
use Webkul\MasterDataManagement\Models\Field as FieldModel;

class FieldRepository extends Repository
{
    /**
     * Get model class name.
     */
    public function model(): string
    {
        return Field::class;
    }

    /**
     * Select options for every field, labelled `entity_type.field`, as an alias scope.
     *
     * @return BaseCollection<int, array{id: int, label: string}>
     */
    public function scopeOptions(): BaseCollection
    {
        return DB::table('mdm_fields')
            ->join('mdm_entity_types', 'mdm_entity_types.id', '=', 'mdm_fields.entity_type_id')
            ->orderBy('mdm_entity_types.sort_order')
            ->orderBy('mdm_entity_types.code')
            ->orderBy('mdm_fields.code')
            ->get(['mdm_fields.id', 'mdm_fields.code', 'mdm_entity_types.code as entity_type_code'])
            ->map(fn ($row): array => ['id' => (int) $row->id, 'label' => $row->entity_type_code.'.'.$row->code])
            ->values();
    }

    /**
     * The non-archived fields of the entity type with their translations, in display order.
     *
     * Memoised in the scoped request memo under `masterdata.fields.{id}`; resolved per call
     * so a repository held by a long-lived service never reads another request's memo.
     *
     * @return Collection<int, FieldModel>
     */
    public function activeFor(int $entityTypeId): Collection
    {
        $key = 'masterdata.fields.'.$entityTypeId;

        $memo = resolve(RequestMemo::class);

        if ($memo->has($key)) {
            return $memo->get($key);
        }

        $fields = $this->model->newQuery()
            ->with('translations')
            ->where('entity_type_id', $entityTypeId)
            ->where('is_archived', false)
            ->orderBy('position')
            ->orderBy('id')
            ->get();

        $memo->set($key, $fields);

        return $fields;
    }

    /**
     * The non-archived searchable fields of the entity type, in display order.
     *
     * @return Collection<int, FieldModel>
     */
    public function searchableFor(int $entityTypeId): Collection
    {
        return $this->activeFor($entityTypeId)
            ->filter(fn (FieldModel $field): bool => $field->is_searchable)
            ->values();
    }

    /**
     * The non-archived fields of the entity type the search index holds: searchable or unique, in display order.
     *
     * Unique fields are indexed because the uniqueness check reads the index rows.
     *
     * @return Collection<int, FieldModel>
     */
    public function indexableFor(int $entityTypeId): Collection
    {
        return $this->activeFor($entityTypeId)
            ->filter(fn (FieldModel $field): bool => $field->is_searchable || $field->is_unique)
            ->values();
    }

    /**
     * Find a field of the entity type by its code, archived fields included.
     */
    public function findByCode(int $entityTypeId, string $code): ?FieldModel
    {
        return $this->model->newQuery()
            ->with('translations')
            ->where('entity_type_id', $entityTypeId)
            ->where('code', $code)
            ->first();
    }

    /**
     * Find a field by id within the entity type, archived fields included.
     */
    public function findForEntityType(int $entityTypeId, int $id): ?FieldModel
    {
        return $this->model->newQuery()
            ->with('translations')
            ->where('entity_type_id', $entityTypeId)
            ->whereKey($id)
            ->first();
    }

    /**
     * Every field of the entity type, archived ones included, with its labels, code list and reference type, in display order.
     *
     * @return Collection<int, FieldModel>
     */
    public function allFor(int $entityTypeId): Collection
    {
        return $this->model->newQuery()
            ->with(['translations', 'codeList:id,code', 'referenceEntityType:id,code'])
            ->where('entity_type_id', $entityTypeId)
            ->orderBy('position')
            ->orderBy('id')
            ->get();
    }

    /**
     * Drop the memoised field list of the entity type, after its fields changed within the current request.
     */
    public function forgetActive(int $entityTypeId): void
    {
        resolve(RequestMemo::class)->forget('masterdata.fields.'.$entityTypeId);
    }
}
