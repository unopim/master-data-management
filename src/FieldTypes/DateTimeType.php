<?php

namespace Webkul\MasterDataManagement\FieldTypes;

use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Date;
use Throwable;
use Webkul\MasterDataManagement\Models\Field;
use Webkul\MasterDataManagement\ValueObjects\IndexValue;

class DateTimeType extends AbstractFieldType
{
    /**
     * {@inheritdoc}
     */
    public function code(): string
    {
        return 'datetime';
    }

    /**
     * {@inheritdoc}
     */
    public function rules(Field $field): array
    {
        return ['date'];
    }

    /**
     * Stored as ISO 8601 with its offset, so the instant survives a change of app timezone.
     *
     * {@inheritdoc}
     */
    public function cast(mixed $raw, Field $field): mixed
    {
        return $this->parse($raw)?->toIso8601String();
    }

    /**
     * {@inheritdoc}
     */
    public function indexValue(mixed $value, Field $field): IndexValue
    {
        return new IndexValue(date: $this->parse($value)?->toDateString());
    }

    /**
     * Parse the value, or null when it is not a date.
     */
    protected function parse(mixed $raw): ?CarbonInterface
    {
        if (! is_string($raw) || trim($raw) === '' || strtotime($raw) === false) {
            return null;
        }

        try {
            return Date::parse($raw);
        } catch (Throwable) {
            return null;
        }
    }
}
