<?php

namespace Webkul\MasterDataManagement\Quality;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Date;
use Webkul\MasterDataManagement\Models\EntityType;
use Webkul\MasterDataManagement\Models\Record;
use Webkul\MasterDataManagement\Quality\Rules\RequiredXrefRule;
use Webkul\MasterDataManagement\Repositories\EntityTypeRepository;
use Webkul\MasterDataManagement\Repositories\RecordRepository;

/**
 * Re-checks every record of an entity type in batches of `masterdata.batch_size`: refreshes the stored
 * completeness and syncs the quality issues.
 */
class QualityScanner
{
    public function __construct(
        protected EntityTypeRepository $entityTypeRepository,
        protected RecordRepository $recordRepository,
        protected CompletenessCalculator $completenessCalculator,
        protected QualityEvaluator $qualityEvaluator,
    ) {}

    /**
     * Scan the entity type and store when; returns the number of records checked, 0 for an unknown type.
     */
    public function scan(int $entityTypeId): int
    {
        $type = $this->entityTypeRepository->find($entityTypeId);

        if (! $type instanceof EntityType) {
            return 0;
        }

        $checked = 0;

        $this->recordRepository->chunkIdsForEntityType((int) $type->id, $this->batchSize(), function (array $ids) use ($type, &$checked): void {
            $records = $this->recordRepository->forQualityScan($ids);

            $records->each(fn (Record $record) => $record->setRelation('entityType', $type));

            $this->recordRepository->storeCompleteness($records->mapWithKeys(fn (Record $record): array => [
                (int) $record->id => $this->completenessCalculator->score($type, $record->values ?? []),
            ])->all());

            $this->qualityEvaluator->evaluate($type, $records);

            $checked += $records->count();
        });

        $this->entityTypeRepository->markQualityScanned((int) $type->id);

        return $checked;
    }

    /**
     * The settings that change which findings and completeness scores the type's records get: completeness scope,
     * required source systems and the hierarchy rules; a change between two snapshots calls for a rescan.
     *
     * @return array{0: string, 1: list<int>, 2: bool, 3: list<int>}
     */
    public function settingsSnapshot(EntityType $entityType): array
    {
        $systems = RequiredXrefRule::requiredSystemIds($entityType);

        $parents = array_values(array_unique(array_map(intval(...), (array) ($entityType->allowed_parent_type_ids ?? []))));

        sort($systems);

        sort($parents);

        return [$this->completenessCalculator->scope($entityType), $systems, (bool) $entityType->is_hierarchical, $parents];
    }

    /**
     * When each entity type was last scanned, keyed by id; null when never.
     *
     * @param  list<int>  $entityTypeIds
     * @return array<int, ?Carbon>
     */
    public function lastScannedAt(array $entityTypeIds): array
    {
        if ($entityTypeIds === []) {
            return [];
        }

        $values = $this->entityTypeRepository->qualityScannedAt($entityTypeIds);

        $scanned = [];

        foreach ($entityTypeIds as $id) {
            $scanned[$id] = isset($values[$id]) ? Date::parse($values[$id]) : null;
        }

        return $scanned;
    }

    /**
     * The records read and checked per batch.
     */
    protected function batchSize(): int
    {
        return max(1, (int) config('masterdata.batch_size', 500));
    }
}
