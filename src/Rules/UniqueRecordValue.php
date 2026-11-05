<?php

namespace Webkul\MasterDataManagement\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;
use Webkul\MasterDataManagement\FieldTypes\FieldTypeRegistry;
use Webkul\MasterDataManagement\Models\Field;
use Webkul\MasterDataManagement\Repositories\FieldRepository;
use Webkul\MasterDataManagement\Services\RecordIndexer;

/**
 * Fails when another record of the entity type holds an equal value of the field, compared in its indexed
 * (normalised) form, so `PL-01` and `pl 01` conflict.
 */
class UniqueRecordValue implements ValidationRule
{
    /**
     * Stands for the locale segment of the validated attribute (`values.locale_specific.{locale}.{code}`).
     */
    public const string ATTRIBUTE_LOCALE = '*';

    public function __construct(
        protected int $entityTypeId,
        protected string $fieldCode,
        protected ?string $locale,
        protected ?int $ignoreRecordId,
    ) {}

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $field = resolve(FieldRepository::class)->activeFor($this->entityTypeId)->firstWhere('code', $this->fieldCode);

        $registry = resolve(FieldTypeRegistry::class);

        if (! $field instanceof Field || ! $registry->has($field->type)) {
            return;
        }

        $stored = $registry->get($field->type)->cast($value, $field);

        $locale = $this->locale === self::ATTRIBUTE_LOCALE ? $this->attributeLocale($attribute) : $this->locale;

        foreach (resolve(RecordIndexer::class)->columnsFor($field, $stored) as $columns) {
            if ($this->taken($columns, $locale)) {
                $fail('masterdata::app.records.validation.unique')->translate();

                return;
            }
        }
    }

    /**
     * Whether another record has an index row with the same columns.
     *
     * @param  array<string, ?string>  $columns
     */
    protected function taken(array $columns, ?string $locale): bool
    {
        $query = DB::table(RecordIndexer::TABLE)
            ->where('entity_type_id', $this->entityTypeId)
            ->where('field_code', $this->fieldCode);

        foreach (['locale' => $locale] + $columns as $column => $value) {
            if ($value === null) {
                $query->whereNull($column);
            } else {
                $query->where($column, $value);
            }
        }

        if ($this->ignoreRecordId !== null) {
            $query->where('record_id', '!=', $this->ignoreRecordId);
        }

        return $query->exists();
    }

    /**
     * The locale segment of a `values.locale_specific.{locale}.{code}` attribute.
     */
    protected function attributeLocale(string $attribute): ?string
    {
        return explode('.', $attribute)[2] ?? null;
    }
}
