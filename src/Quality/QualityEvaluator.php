<?php

namespace Webkul\MasterDataManagement\Quality;

use Illuminate\Database\Eloquent\Collection;
use Webkul\MasterDataManagement\Enums\RecordStatus;
use Webkul\MasterDataManagement\Models\EntityType;
use Webkul\MasterDataManagement\Models\Record;
use Webkul\MasterDataManagement\Quality\Rules\ComparesRecords;
use Webkul\MasterDataManagement\Quality\Rules\QualityRule;
use Webkul\MasterDataManagement\Repositories\QualityIssueRepository;

/**
 * Runs the quality rules of `masterdata.quality.rules` over records of one type and stores the result as issues.
 *
 * Records outside the draft and active lifecycle are checked by no rule, so their open issues close.
 */
class QualityEvaluator
{
    public function __construct(protected QualityIssueRepository $qualityIssueRepository) {}

    /**
     * The configured rules, resolved from the container.
     *
     * @return list<QualityRule>
     */
    public function rules(): array
    {
        return array_values(array_map(
            fn (string $class): QualityRule => resolve($class),
            (array) config('masterdata.quality.rules', []),
        ));
    }

    /**
     * The configured rules that compare a record with the other records of its type.
     *
     * @return list<QualityRule>
     */
    public function comparingRules(): array
    {
        return array_values(array_filter($this->rules(), fn (QualityRule $rule): bool => $rule instanceof ComparesRecords));
    }

    /**
     * The configured rules that check a record on its own, run synchronously after each write.
     *
     * @return list<QualityRule>
     */
    public function recordRules(): array
    {
        return array_values(array_filter($this->rules(), fn (QualityRule $rule): bool => ! $rule instanceof ComparesRecords));
    }

    /**
     * Evaluate the records, all of the given type, and sync their issues of the given rules, all configured by default.
     *
     * @param  Collection<int, Record>  $records
     * @param  list<QualityRule>|null  $rules
     */
    public function evaluate(EntityType $type, Collection $records, ?array $rules = null): void
    {
        if ($records->isEmpty()) {
            return;
        }

        $live = $records
            ->filter(fn (Record $record): bool => in_array($record->status, [RecordStatus::Draft, RecordStatus::Active], true))
            ->values();

        $rules ??= $this->rules();

        if ($rules === []) {
            return;
        }

        $detected = [];

        foreach ($rules as $rule) {
            foreach ($live->isEmpty() ? [] : $rule->evaluate($type, $live) as $finding) {
                $detected[$finding->identity()] = $finding;
            }
        }

        $this->qualityIssueRepository->syncFindings(
            (int) $type->id,
            array_map(intval(...), $records->modelKeys()),
            array_map(fn (QualityRule $rule): string => $rule->code(), $rules),
            $detected,
        );
    }

    /**
     * Evaluate one record after a write, with the given rules or all configured ones.
     *
     * @param  list<QualityRule>|null  $rules
     */
    public function evaluateRecord(Record $record, ?array $rules = null): void
    {
        $this->evaluate($record->entityType, $record->newCollection([$record]), $rules);
    }
}
