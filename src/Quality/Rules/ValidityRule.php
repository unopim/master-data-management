<?php

namespace Webkul\MasterDataManagement\Quality\Rules;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Webkul\MasterDataManagement\Enums\QualitySeverity;
use Webkul\MasterDataManagement\Models\EntityType;
use Webkul\MasterDataManagement\Quality\DetectedIssue;
use Webkul\MasterDataManagement\Services\FieldRuleBuilder;
use Webkul\MasterDataManagement\Services\RecordWriter;

/**
 * Flags stored values that fail the type's current field rules, such as a field made required after records existed.
 */
class ValidityRule implements QualityRule
{
    public const string CODE = 'validity';

    public function __construct(protected FieldRuleBuilder $fieldRuleBuilder) {}

    /**
     * {@inheritdoc}
     */
    public function code(): string
    {
        return self::CODE;
    }

    /**
     * Validate each record's stored values the way a write would, one finding per failing value.
     *
     * {@inheritdoc}
     */
    public function evaluate(EntityType $type, Collection $records): array
    {
        $labels = $this->fieldRuleBuilder->fieldLabels($type);

        $issues = [];

        foreach ($records as $record) {
            $rules = Arr::except($this->fieldRuleBuilder->rulesFor($type, $record), RecordWriter::KEY_RULES);

            if ($rules === []) {
                continue;
            }

            $errors = Validator::make(['values' => $record->values ?? []], $rules, [], $labels)->errors();

            foreach ($errors->messages() as $attribute => $messages) {
                $issues[] = new DetectedIssue(
                    (int) $record->id,
                    self::CODE,
                    mb_substr((string) $attribute, 0, 191),
                    QualitySeverity::Error,
                    [
                        'field'   => (string) Arr::last(explode('.', (string) $attribute)),
                        'message' => (string) ($messages[0] ?? ''),
                    ],
                );
            }
        }

        return $issues;
    }
}
