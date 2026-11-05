<?php

namespace Webkul\MasterDataManagement\Quality;

use Webkul\MasterDataManagement\Enums\QualityIssueStatus;
use Webkul\MasterDataManagement\Models\Record;

/**
 * Turns a stored issue's rule and details into translated text for the admin pages.
 */
class IssuePresenter
{
    /**
     * The translated name of a rule, or its code when no translation exists.
     */
    public function ruleLabel(string $rule): string
    {
        $key = 'masterdata::app.quality.rules.'.$rule;

        return trans()->has($key) ? trans($key) : $rule;
    }

    /**
     * The translated message of an issue; a pair issue names the record other than the one being viewed.
     *
     * @param  array<string, mixed>|string|null  $details  The stored details, decoded or as JSON.
     */
    public function message(string $rule, array|string|null $details, ?string $otherRecordCode = null): string
    {
        $details = is_string($details) ? (array) json_decode($details, true) : (array) $details;

        $kind = is_string($details['kind'] ?? null) ? $details['kind'] : null;

        $key = 'masterdata::app.quality.messages.'.$rule.($kind === null ? '' : '.'.$kind);

        if (! trans()->has($key)) {
            return $this->ruleLabel($rule);
        }

        if (isset($details['status']) && is_string($details['status']) && trans()->has('masterdata::app.records.statuses.'.$details['status'])) {
            $details['status'] = trans('masterdata::app.records.statuses.'.$details['status']);
        }

        if ($otherRecordCode !== null) {
            $details['record'] = $otherRecordCode;
        }

        $replace = array_map(fn (mixed $value): string => is_scalar($value) ? (string) $value : '', array_diff_key($details, ['kind' => true]));

        return trans($key, $replace);
    }

    /**
     * The issues involving the record as page rows, with their translated rule and message; a pair issue names and
     * links the other record.
     *
     * @param  list<object>  $issues  Rows of `QualityIssueRepository::involvingRecord()`.
     * @return list<array{id: int, rule: string, severity: string, status: string, message: string, detected_at: ?string, other_uuid: ?string, open: bool}>
     */
    public function forRecord(Record $record, array $issues): array
    {
        return array_map(function (object $issue) use ($record): array {
            $owned = (int) $issue->record_id === (int) $record->id;

            $paired = $issue->related_record_id !== null;

            return [
                'id'          => (int) $issue->id,
                'rule'        => $this->ruleLabel($issue->rule),
                'severity'    => (string) $issue->severity,
                'status'      => (string) $issue->status,
                'message'     => $this->message($issue->rule, $issue->details, $paired ? ($owned ? $issue->related_code : $issue->owner_code) : null),
                'detected_at' => $issue->detected_at,
                'other_uuid'  => $paired ? ($owned ? $issue->related_uuid : $issue->owner_uuid) : null,
                'open'        => $issue->status === QualityIssueStatus::Open->value,
            ];
        }, $issues);
    }
}
