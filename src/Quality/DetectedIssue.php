<?php

namespace Webkul\MasterDataManagement\Quality;

use Webkul\MasterDataManagement\Enums\QualitySeverity;

/**
 * One finding a quality rule reports for a record, before it is stored as an issue.
 */
final readonly class DetectedIssue
{
    /**
     * @param  string  $key  What the finding is about within the rule (a field, a source system, the other record).
     * @param  array<string, scalar|null>  $details  The message placeholders, stored with the issue.
     * @param  ?int  $relatedRecordId  The other record of a finding about a pair of records.
     */
    public function __construct(
        public int $recordId,
        public string $rule,
        public string $key,
        public QualitySeverity $severity,
        public array $details = [],
        public ?int $relatedRecordId = null,
    ) {}

    /**
     * The identity an issue row is stored under: record, rule and key.
     */
    public function identity(): string
    {
        return self::identityOf($this->recordId, $this->rule, $this->key);
    }

    /**
     * Build the identity of a stored issue row.
     */
    public static function identityOf(int $recordId, string $rule, string $key): string
    {
        return $recordId.'|'.$rule.'|'.$key;
    }
}
