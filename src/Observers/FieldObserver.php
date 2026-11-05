<?php

namespace Webkul\MasterDataManagement\Observers;

use Webkul\MasterDataManagement\Models\Field;
use Webkul\MasterDataManagement\Repositories\RelationTypeRepository;

class FieldObserver
{
    public function __construct(protected RelationTypeRepository $relationTypeRepository) {}

    /**
     * Keep the system relation type of a reference field in step with the field.
     */
    public function saved(Field $field): void
    {
        if ($field->type !== 'reference' || $field->reference_entity_type_id === null) {
            return;
        }

        $this->relationTypeRepository->systemTypeForField($field);
    }
}
