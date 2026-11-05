<?php

namespace Webkul\MasterDataManagement\FieldTypes;

use Closure;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Webkul\MasterDataManagement\Enums\RecordStatus;
use Webkul\MasterDataManagement\Models\Field;
use Webkul\MasterDataManagement\Models\RecordProxy;

class ReferenceType extends AbstractFieldType
{
    /**
     * {@inheritdoc}
     */
    public function code(): string
    {
        return 'reference';
    }

    /**
     * Uuids of live records of the referenced entity type; the uuid check bails before the lookup query.
     *
     * {@inheritdoc}
     */
    public function rules(Field $field): array
    {
        if (! $field->multiple) {
            return ['bail', 'string', $this->uuidRule(), $this->existsRule($field)];
        }

        return ['bail', 'array', $this->uuidRule(), $this->existsRule($field)];
    }

    /**
     * {@inheritdoc}
     */
    public function cast(mixed $raw, Field $field): mixed
    {
        if (! $field->multiple) {
            return is_string($raw) && Str::isUuid($raw) ? strtolower($raw) : null;
        }

        if (! is_array($raw)) {
            return null;
        }

        $uuids = array_values(array_unique(array_map(
            strtolower(...),
            array_filter($raw, fn (mixed $uuid): bool => is_string($uuid) && Str::isUuid($uuid))
        )));

        return $uuids === [] ? null : $uuids;
    }

    /**
     * References are kept as relations, not in the record's values.
     */
    public function storesInValues(): bool
    {
        return false;
    }

    /**
     * The exists rule on live records of the referenced type; an unset reference type matches nothing.
     */
    protected function existsRule(Field $field): Exists
    {
        return Rule::exists(RecordProxy::modelClass(), 'uuid')
            ->where('entity_type_id', $field->reference_entity_type_id ?? 0)
            ->whereNotIn('status', [RecordStatus::Merged->value, RecordStatus::Erased->value]);
    }

    /**
     * A rule that fails unless the value, or every item of a list value, is a uuid.
     */
    protected function uuidRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            foreach ((array) $value as $uuid) {
                if (! is_string($uuid) || ! Str::isUuid($uuid)) {
                    $fail('masterdata::app.fields.validation.invalid-reference')->translate();

                    return;
                }
            }
        };
    }
}
