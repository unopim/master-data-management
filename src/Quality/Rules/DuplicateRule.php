<?php

namespace Webkul\MasterDataManagement\Quality\Rules;

use Illuminate\Database\Eloquent\Collection;
use Webkul\MasterDataManagement\Enums\QualitySeverity;
use Webkul\MasterDataManagement\Matching\MatchSuggester;
use Webkul\MasterDataManagement\Models\EntityType;
use Webkul\MasterDataManagement\Quality\DetectedIssue;

/**
 * Flags likely duplicates within a type: another record the match suggester scores at or above
 * `masterdata.quality.duplicate_threshold` from the record's label and, through the type's match rules, its values.
 *
 * A pair is stored once, on its newer record, so a scan that meets it from both sides keeps a single issue.
 */
class DuplicateRule implements ComparesRecords
{
    public const string CODE = 'duplicate';

    /**
     * The most suggestions read per record.
     */
    protected const int SUGGESTION_LIMIT = 5;

    public function __construct(protected MatchSuggester $matchSuggester) {}

    /**
     * {@inheritdoc}
     */
    public function code(): string
    {
        return self::CODE;
    }

    /**
     * Score each record with a label or values against the type's other records; codes are left out since they are
     * unique.
     *
     * {@inheritdoc}
     */
    public function evaluate(EntityType $type, Collection $records): array
    {
        $threshold = (float) config('masterdata.quality.duplicate_threshold', 0.85);

        $issues = [];

        foreach ($records as $record) {
            $label = trim((string) $record->label);

            $values = (array) ($record->values ?? []);

            if ($label === '' && array_filter($values) === []) {
                continue;
            }

            foreach ($this->matchSuggester->suggest($type, ['label' => $label, 'values' => $values], self::SUGGESTION_LIMIT) as $suggestion) {
                $other = $suggestion->record;

                if ((int) $other->id === (int) $record->id || $suggestion->score < $threshold) {
                    continue;
                }

                [$newer, $older] = $record->id > $other->id ? [$record, $other] : [$other, $record];

                $issues[] = new DetectedIssue((int) $newer->id, self::CODE, (string) $older->id, QualitySeverity::Warning, [
                    'record' => (string) $older->code,
                    'label'  => (string) $older->label,
                    'score'  => (int) round($suggestion->score * 100),
                ], (int) $older->id);
            }
        }

        return $issues;
    }
}
