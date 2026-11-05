<?php

namespace Webkul\MasterDataManagement\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Webkul\Core\Eloquent\Repository;
use Webkul\MasterDataManagement\Contracts\RelationType;
use Webkul\MasterDataManagement\Enums\RelationCardinality;
use Webkul\MasterDataManagement\Models\Field;
use Webkul\MasterDataManagement\Models\RelationType as RelationTypeModel;

class RelationTypeRepository extends Repository
{
    /**
     * The code prefix of the system relation types that store reference fields.
     */
    public const string SYSTEM_PREFIX = 'field:';

    /**
     * Get model class name.
     */
    public function model(): string
    {
        return RelationType::class;
    }

    /**
     * The system relation type that stores the reference field's values, created on first use and kept in step with
     * the field's target type, cardinality and labels.
     *
     * Idempotent: the code `field:{entity_type_code}.{field_code}` is unique, so a concurrent first call reads the
     * row the other call created.
     */
    public function systemTypeForField(Field $field): RelationTypeModel
    {
        $field->loadMissing(['entityType:id,code', 'translations']);

        $attributes = [
            'from_entity_type_id' => $field->entity_type_id,
            'to_entity_type_id'   => $field->reference_entity_type_id,
            'cardinality'         => $field->multiple ? RelationCardinality::Many : RelationCardinality::One,
            'is_system'           => true,
            'field_id'            => $field->id,
        ];

        $type = $this->model->newQuery()->createOrFirst(['code' => $this->systemCode($field)], $attributes);

        $type->fill($attributes);

        foreach ($field->translations as $translation) {
            $type->translateOrNew($translation->locale)->label = $translation->label;
        }

        $type->save();

        return $type;
    }

    /**
     * The system relation types of the given reference fields, keyed by field id.
     *
     * @param  list<int>  $fieldIds
     * @return Collection<int, RelationTypeModel>
     */
    public function systemTypesForFields(array $fieldIds): Collection
    {
        if ($fieldIds === []) {
            return $this->model->newCollection();
        }

        return $this->model->newQuery()
            ->where('is_system', true)
            ->whereIn('field_id', $fieldIds)
            ->get()
            ->keyBy('field_id');
    }

    /**
     * Find a relation type that starts from the given entity type.
     */
    public function findFrom(int $entityTypeId, int $id): ?RelationTypeModel
    {
        return $this->model->newQuery()
            ->with('translations')
            ->where('from_entity_type_id', $entityTypeId)
            ->find($id);
    }

    /**
     * The relation types that start from the entity type, with their labels, keyed by id, for the relations tab.
     *
     * @return Collection<int, RelationTypeModel>
     */
    public function fromEntityType(int $entityTypeId, int $limit): Collection
    {
        return $this->model->newQuery()
            ->with('translations')
            ->where('from_entity_type_id', $entityTypeId)
            ->orderBy('code')
            ->limit($limit)
            ->get()
            ->keyBy('id');
    }

    /**
     * The relation types that start from or point to the entity type, with their labels, in code order.
     *
     * @return Collection<int, RelationTypeModel>
     */
    public function touchingEntityType(int $entityTypeId, int $limit): Collection
    {
        return $this->model->newQuery()
            ->with('translations')
            ->where(fn ($query) => $query->where('from_entity_type_id', $entityTypeId)->orWhere('to_entity_type_id', $entityTypeId))
            ->orderBy('code')
            ->limit($limit)
            ->get();
    }

    /**
     * The code of the system relation type of a reference field.
     */
    protected function systemCode(Field $field): string
    {
        return self::SYSTEM_PREFIX.$field->entityType->code.'.'.$field->code;
    }
}
