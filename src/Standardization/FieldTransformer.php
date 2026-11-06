<?php

namespace Webkul\MasterDataManagement\Standardization;

use Illuminate\Validation\ValidationException;
use Webkul\MasterDataManagement\Models\EntityType;
use Webkul\MasterDataManagement\Models\Field;
use Webkul\MasterDataManagement\Models\FieldTransform;
use Webkul\MasterDataManagement\Repositories\FieldRepository;
use Webkul\MasterDataManagement\Repositories\FieldTransformRepository;

/**
 * Standardizes submitted field values with each field's transforms, in their sort order, before the values are
 * validated. Only text is rewritten (each entry of a list value too); other values and fields without transforms
 * pass through unchanged. A submitted value whose field has a transform no longer registered fails closed.
 */
class FieldTransformer
{
    public function __construct(
        protected FieldTransformRepository $fieldTransformRepository,
        protected FieldRepository $fieldRepository,
        protected TransformRegistry $transformRegistry,
    ) {}

    /**
     * The write input with its `values.common` and `values.locale_specific.*` values standardized.
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     *
     * @throws ValidationException When a submitted value's field has a transform that is no longer registered.
     */
    public function transformInput(EntityType $type, array $input): array
    {
        if (! is_array($input['values'] ?? null)) {
            return $input;
        }

        $codes = $this->transformsByFieldCode((int) $type->id);

        if ($codes === []) {
            return $input;
        }

        $sections = [];

        if (is_array($input['values']['common'] ?? null)) {
            $sections['values.common'] = $input['values']['common'];
        }

        if (is_array($input['values']['locale_specific'] ?? null)) {
            foreach ($input['values']['locale_specific'] as $locale => $section) {
                if (is_array($section)) {
                    $sections['values.locale_specific.'.$locale] = $section;
                }
            }
        }

        $this->ensureAvailable((int) $type->id, $codes, $sections);

        foreach ($sections as $path => $section) {
            data_set($input, $path, $this->transformSection($section, $codes));
        }

        return $input;
    }

    /**
     * One value of the field, standardized.
     */
    public function transformValue(Field $field, mixed $value): mixed
    {
        $codes = $this->transformsByFieldCode((int) $field->entity_type_id)[(string) $field->code] ?? [];

        return $codes === [] ? $value : $this->apply($codes, $value);
    }

    /**
     * The transform codes of the entity type's active fields, in run order, keyed by field code.
     *
     * @return array<string, list<string>>
     */
    protected function transformsByFieldCode(int $entityTypeId): array
    {
        $transforms = $this->fieldTransformRepository->forEntityType($entityTypeId);

        if ($transforms->isEmpty()) {
            return [];
        }

        $fields = $this->fieldRepository->activeFor($entityTypeId)->keyBy('id');

        $codes = [];

        foreach ($transforms as $transform) {
            $field = $transform instanceof FieldTransform ? $fields->get($transform->field_id) : null;

            if ($field instanceof Field) {
                $codes[(string) $field->code][] = (string) $transform->transform;
            }
        }

        return $codes;
    }

    /**
     * Refuse the submitted values whose field has a transform that is no longer registered.
     *
     * @param  array<string, list<string>>  $codes
     * @param  array<string, array<array-key, mixed>>  $sections
     *
     * @throws ValidationException
     */
    protected function ensureAvailable(int $entityTypeId, array $codes, array $sections): void
    {
        $missing = array_filter(array_map(
            fn (array $transforms): array => array_values(array_filter($transforms, fn (string $code): bool => ! $this->transformRegistry->has($code))),
            $codes,
        ));

        if ($missing === []) {
            return;
        }

        $labels = $this->fieldRepository->activeFor($entityTypeId)->keyBy('code');

        $errors = [];

        foreach ($sections as $path => $section) {
            foreach (array_intersect_key($missing, $section) as $field => $transforms) {
                if (in_array($section[$field], [null, '', []], true)) {
                    continue;
                }

                $label = $labels->get($field)?->label;

                $errors[$path.'.'.$field] = trans('masterdata::app.field-transforms.unavailable', [
                    'attribute' => is_string($label) && $label !== '' ? $label : (string) $field,
                    'transform' => implode(', ', $transforms),
                ]);
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * Standardize the values of one `code => value` section that have transforms.
     *
     * @param  array<array-key, mixed>  $section
     * @param  array<string, list<string>>  $codes
     * @return array<array-key, mixed>
     */
    protected function transformSection(array $section, array $codes): array
    {
        foreach (array_intersect_key($codes, $section) as $code => $transforms) {
            $section[$code] = $this->apply($transforms, $section[$code]);
        }

        return $section;
    }

    /**
     * Run the transforms over a text value, or over each text entry of a list value.
     *
     * @param  list<string>  $transforms
     */
    protected function apply(array $transforms, mixed $value): mixed
    {
        if (is_array($value)) {
            return array_map(fn (mixed $entry): mixed => $this->apply($transforms, $entry), $value);
        }

        if (! is_string($value)) {
            return $value;
        }

        foreach ($transforms as $code) {
            if ($this->transformRegistry->has($code)) {
                $value = $this->transformRegistry->get($code)->apply($value);
            }
        }

        return $value;
    }
}
