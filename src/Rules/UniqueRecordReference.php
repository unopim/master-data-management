<?php

namespace Webkul\MasterDataManagement\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;
use Webkul\MasterDataManagement\Repositories\RelationRepository;

/**
 * Fails when another record already references one of the value's records through the same reference field.
 */
class UniqueRecordReference implements ValidationRule
{
    public function __construct(
        protected int $fieldId,
        protected ?int $ignoreRecordId,
    ) {}

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $uuids = array_values(array_map(
            strtolower(...),
            array_filter((array) $value, fn (mixed $uuid): bool => is_string($uuid) && Str::isUuid($uuid)),
        ));

        if (resolve(RelationRepository::class)->referenceTaken($this->fieldId, $uuids, $this->ignoreRecordId)) {
            $fail('masterdata::app.records.validation.unique')->translate();
        }
    }
}
