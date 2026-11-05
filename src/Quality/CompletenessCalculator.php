<?php

namespace Webkul\MasterDataManagement\Quality;

use Illuminate\Database\Eloquent\Collection;
use Webkul\MasterDataManagement\FieldTypes\FieldTypeRegistry;
use Webkul\MasterDataManagement\Models\EntityType;
use Webkul\MasterDataManagement\Models\Field;
use Webkul\MasterDataManagement\Repositories\FieldRepository;
use Webkul\MasterDataManagement\Services\FieldRuleBuilder;

/**
 * Scores how much of a record's counted fields hold a value, from 0 to 100.
 *
 * The type's `settings.completeness_scope` picks the counted fields: every active field (`all`, the default) or
 * only the required ones (`required`, falling back to all when none is required). Per-locale fields count in the
 * default locale; fields stored outside the values JSON are not counted.
 */
class CompletenessCalculator
{
    public const string SCOPE_ALL = 'all';

    public const string SCOPE_REQUIRED = 'required';

    public function __construct(
        protected FieldRepository $fieldRepository,
        protected FieldRuleBuilder $fieldRuleBuilder,
        protected FieldTypeRegistry $fieldTypeRegistry,
    ) {}

    /**
     * The completeness score of the values, or null when the type counts no fields.
     *
     * @param  array<string, mixed>  $values
     */
    public function score(EntityType $type, array $values): ?int
    {
        return $this->evaluate($type, $values)->score;
    }

    /**
     * Count the filled and missing fields of the values; the score rounds down so 100 means nothing is missing.
     *
     * @param  array<string, mixed>  $values
     */
    public function evaluate(EntityType $type, array $values): Completeness
    {
        $fields = $this->countedFields($type);

        if ($fields->isEmpty()) {
            return new Completeness(null, 0, 0);
        }

        $locale = $this->fieldRuleBuilder->defaultLocale();

        $filled = 0;

        $missing = [];

        foreach ($fields as $field) {
            $value = $field->value_per_locale
                ? ($values['locale_specific'][$locale][$field->code] ?? null)
                : ($values['common'][$field->code] ?? null);

            if (filled($value)) {
                $filled++;

                continue;
            }

            $label = $field->label;

            $missing[] = is_string($label) && $label !== '' ? $label : $field->code;
        }

        return new Completeness((int) floor($filled * 100 / $fields->count()), $filled, $fields->count(), $missing);
    }

    /**
     * The scope the type's completeness counts.
     */
    public function scope(EntityType $type): string
    {
        return ($type->settings['completeness_scope'] ?? null) === self::SCOPE_REQUIRED ? self::SCOPE_REQUIRED : self::SCOPE_ALL;
    }

    /**
     * The active fields stored in the values JSON that the type's scope counts, in display order.
     *
     * @return Collection<int, Field>
     */
    protected function countedFields(EntityType $type): Collection
    {
        $fields = $this->fieldRepository->activeFor((int) $type->id)
            ->filter(fn (Field $field): bool => $this->fieldTypeRegistry->has($field->type) && $this->fieldTypeRegistry->get($field->type)->storesInValues())
            ->values();

        if ($this->scope($type) === self::SCOPE_REQUIRED) {
            $required = $fields->filter(fn (Field $field): bool => $this->fieldRuleBuilder->isRequired($type, $field))->values();

            if ($required->isNotEmpty()) {
                return $required;
            }
        }

        return $fields;
    }
}
