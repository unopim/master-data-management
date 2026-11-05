<?php

namespace Webkul\MasterDataManagement\Quality\Rules;

use Illuminate\Database\Eloquent\Collection;
use Webkul\MasterDataManagement\Enums\QualitySeverity;
use Webkul\MasterDataManagement\Enums\RecordStatus;
use Webkul\MasterDataManagement\Models\EntityType;
use Webkul\MasterDataManagement\Models\Record;
use Webkul\MasterDataManagement\Quality\DetectedIssue;
use Webkul\MasterDataManagement\Repositories\RecordRepository;
use Webkul\MasterDataManagement\Services\HierarchyService;

/**
 * Flags records whose parent no longer fits the type's hierarchy: a parent type the type stopped allowing, or a
 * parent that left the active lifecycle.
 */
class HierarchyRule implements QualityRule
{
    public const string CODE = 'hierarchy';

    public const string KEY_PARENT_TYPE = 'parent_type';

    public const string KEY_PARENT_STATUS = 'parent_status';

    public function __construct(
        protected RecordRepository $recordRepository,
        protected HierarchyService $hierarchyService,
    ) {}

    /**
     * {@inheritdoc}
     */
    public function code(): string
    {
        return self::CODE;
    }

    /**
     * Check every placed record against its parent, read for the whole batch in one query.
     *
     * {@inheritdoc}
     */
    public function evaluate(EntityType $type, Collection $records): array
    {
        $children = $records->filter(fn (Record $record): bool => $record->parent_id !== null);

        if ($children->isEmpty()) {
            return [];
        }

        $parents = $this->recordRepository->typeSummaries($children->pluck('parent_id')->unique()->values()->all());

        $allowed = $type->is_hierarchical ? $this->hierarchyService->allowedParentTypeIds($type) : [];

        $issues = [];

        foreach ($children as $record) {
            $parent = $parents[$record->parent_id] ?? null;

            if ($parent === null) {
                continue;
            }

            if (! in_array((int) $parent->entity_type_id, $allowed, true)) {
                $issues[] = new DetectedIssue((int) $record->id, self::CODE, self::KEY_PARENT_TYPE, QualitySeverity::Error, [
                    'kind'   => self::KEY_PARENT_TYPE,
                    'parent' => (string) $parent->code,
                    'type'   => (string) $parent->type_code,
                ]);

                continue;
            }

            if (! in_array($parent->status, [RecordStatus::Draft->value, RecordStatus::Active->value], true)) {
                $issues[] = new DetectedIssue((int) $record->id, self::CODE, self::KEY_PARENT_STATUS, QualitySeverity::Warning, [
                    'kind'   => self::KEY_PARENT_STATUS,
                    'parent' => (string) $parent->code,
                    'status' => (string) $parent->status,
                ]);
            }
        }

        return $issues;
    }
}
