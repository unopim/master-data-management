<?php

namespace Webkul\MasterDataManagement\Quality\Rules;

use Illuminate\Database\Eloquent\Collection;
use Webkul\MasterDataManagement\Enums\QualitySeverity;
use Webkul\MasterDataManagement\Models\EntityType;
use Webkul\MasterDataManagement\Quality\DetectedIssue;
use Webkul\MasterDataManagement\Repositories\SourceSystemRepository;
use Webkul\MasterDataManagement\Repositories\XrefRepository;

/**
 * Flags records without an active external id in a source system the entity type requires
 * (`settings.required_source_system_ids`).
 */
class RequiredXrefRule implements QualityRule
{
    public const string CODE = 'required_xref';

    public function __construct(
        protected XrefRepository $xrefRepository,
        protected SourceSystemRepository $sourceSystemRepository,
    ) {}

    /**
     * {@inheritdoc}
     */
    public function code(): string
    {
        return self::CODE;
    }

    /**
     * The source system ids the type requires, as stored in its settings.
     *
     * @return list<int>
     */
    public static function requiredSystemIds(EntityType $type): array
    {
        return array_values(array_unique(array_map(intval(...), (array) ($type->settings['required_source_system_ids'] ?? []))));
    }

    /**
     * Compare each record's linked systems, read for the whole batch in one query, with the required ones.
     *
     * {@inheritdoc}
     */
    public function evaluate(EntityType $type, Collection $records): array
    {
        $required = self::requiredSystemIds($type);

        if ($required === [] || $records->isEmpty()) {
            return [];
        }

        $systems = $this->sourceSystemRepository->codesById($required);

        if ($systems === []) {
            return [];
        }

        $linked = $this->xrefRepository->activeSystemIdsByRecord($records->modelKeys());

        $issues = [];

        foreach ($records as $record) {
            $present = $linked[$record->id] ?? [];

            foreach ($systems as $systemId => $code) {
                if (! in_array($systemId, $present, true)) {
                    $issues[] = new DetectedIssue((int) $record->id, self::CODE, (string) $systemId, QualitySeverity::Warning, [
                        'system' => $code,
                    ]);
                }
            }
        }

        return $issues;
    }
}
