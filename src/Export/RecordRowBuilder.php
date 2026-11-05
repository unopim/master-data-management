<?php

namespace Webkul\MasterDataManagement\Export;

use Illuminate\Support\Collection;
use Webkul\MasterDataManagement\FieldTypes\FieldTypeRegistry;
use Webkul\MasterDataManagement\Models\EntityType;
use Webkul\MasterDataManagement\Models\Field;
use Webkul\MasterDataManagement\Models\Record;
use Webkul\MasterDataManagement\Repositories\FieldRepository;
use Webkul\MasterDataManagement\Repositories\RecordRepository;
use Webkul\MasterDataManagement\Repositories\XrefRepository;
use Webkul\User\Models\Admin;

/**
 * Builds the published shape of records: identity, hierarchy codes, typed values keyed by field code, external ids
 * per source system code, completeness and the last change time.
 *
 * Each call reads one bounded batch of ids with a fixed number of queries, whatever the batch size.
 */
class RecordRowBuilder
{
    public function __construct(
        protected RecordRepository $recordRepository,
        protected XrefRepository $xrefRepository,
        protected FieldRepository $fieldRepository,
        protected FieldTypeRegistry $fieldTypeRegistry,
    ) {}

    /**
     * The rows of the records with the given ids that the admin may see, ordered by id.
     *
     * @param  list<int>  $ids
     * @return list<array<string, mixed>>
     */
    public function build(EntityType $type, array $ids, ?Admin $admin = null): array
    {
        if ($ids === []) {
            return [];
        }

        $records = $this->recordRepository->scopedQuery((int) $type->id, $admin)
            ->whereKey($ids)
            ->orderBy('id')
            ->get();

        $paths = $records->mapWithKeys(fn (Record $record): array => [(int) $record->id => $this->pathIds($record)]);

        $codes = $this->recordRepository->codesByIds($paths->flatten()->unique()->values()->all(), $admin);

        $xrefs = $this->xrefRepository->activeIdsByRecords(array_map(intval(...), $records->modelKeys()));

        $fields = $this->exportedFields($type);

        return $records
            ->map(fn (Record $record): array => [
                'uuid'         => $record->uuid,
                'code'         => $record->code,
                'label'        => $record->label,
                'status'       => $record->status->value,
                'entity_type'  => $type->code,
                'parent_code'  => $record->parent_id ? ($codes[$record->parent_id] ?? null) : null,
                'path_codes'   => array_values(array_filter(array_map(fn (int $id): ?string => $codes[$id] ?? null, $paths[(int) $record->id]))),
                'values'       => $this->values($record, $fields),
                'xrefs'        => $xrefs[(int) $record->id] ?? [],
                'completeness' => $record->completeness,
                'updated_at'   => $record->updated_at?->toIso8601String(),
            ])
            ->values()
            ->all();
    }

    /**
     * The active fields of the type whose values live in the values JSON, in display order.
     *
     * @return Collection<int, Field>
     */
    public function exportedFields(EntityType $type): Collection
    {
        return $this->fieldRepository->activeFor((int) $type->id)
            ->filter(fn (Field $field): bool => $this->fieldTypeRegistry->has($field->type) && $this->fieldTypeRegistry->get($field->type)->storesInValues())
            ->values();
    }

    /**
     * The record's values keyed by field code: one value for a common field, a locale map for a per-locale field.
     *
     * @param  Collection<int, Field>  $fields
     * @return array<string, mixed>
     */
    protected function values(Record $record, Collection $fields): array
    {
        $stored = is_array($record->values) ? $record->values : [];

        $values = [];

        foreach ($fields as $field) {
            if (! $field->value_per_locale) {
                $values[$field->code] = $stored['common'][$field->code] ?? null;

                continue;
            }

            $localized = [];

            foreach ((array) ($stored['locale_specific'] ?? []) as $locale => $localeValues) {
                if (is_array($localeValues) && array_key_exists($field->code, $localeValues)) {
                    $localized[(string) $locale] = $localeValues[$field->code];
                }
            }

            $values[$field->code] = $localized;
        }

        return $values;
    }

    /**
     * The ids on the record's materialised path, from the root down to the record itself.
     *
     * @return list<int>
     */
    protected function pathIds(Record $record): array
    {
        $ids = array_map(intval(...), array_values(array_filter(explode('/', (string) $record->path), is_numeric(...))));

        return $ids === [] ? [(int) $record->id] : $ids;
    }
}
