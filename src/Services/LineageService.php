<?php

namespace Webkul\MasterDataManagement\Services;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Date;
use OwenIt\Auditing\Models\Audit;
use Webkul\HistoryControl\Repositories\AuditRepository;
use Webkul\MasterDataManagement\Export\ExportConsumers;
use Webkul\MasterDataManagement\Models\Record;
use Webkul\MasterDataManagement\Repositories\EntityTypeVersionRepository;
use Webkul\MasterDataManagement\Repositories\RecordRepository;
use Webkul\MasterDataManagement\Repositories\XrefRepository;
use Webkul\User\Repositories\AdminRepository;

/**
 * Reads where a record came from, how it got here and which export jobs publish it, as plain arrays an admin view or
 * an API resource can render.
 *
 * @phpstan-import-type ExportConsumer from ExportConsumers
 *
 * @phpstan-type LineageSource array{code: string, name: string, type: string, ids: list<array{external_id: string, status: string, attached_at: ?CarbonInterface, last_seen_at: ?CarbonInterface}>}
 * @phpstan-type LineageChange array{version: ?int, event: string, user: ?string, at: ?CarbonInterface, changed: list<string>}
 * @phpstan-type LineageAuthorship array{created_at: ?CarbonInterface, created_by: ?string, updated_at: ?CarbonInterface, updated_by: ?string}
 * @phpstan-type LineageSchema array{version: ?int, current_version: ?int, is_current: bool, published_at: ?CarbonInterface, published_by: ?string}
 * @phpstan-type LineagePathItem array{uuid: string, code: string, label: ?string, type: ?string, is_current: bool}
 * @phpstan-type Lineage array{sources: list<LineageSource>, authorship: LineageAuthorship, changes: list<LineageChange>, change_limit: int, schema: LineageSchema, path: list<LineagePathItem>, consumers: list<ExportConsumer>}
 */
class LineageService
{
    /**
     * The most history entries the lineage lists.
     */
    public const int CHANGE_LIMIT = 10;

    public function __construct(
        protected RecordRepository $recordRepository,
        protected XrefRepository $xrefRepository,
        protected EntityTypeVersionRepository $entityTypeVersionRepository,
        protected AuditRepository $auditRepository,
        protected AdminRepository $adminRepository,
        protected ExportConsumers $exportConsumers,
    ) {}

    /**
     * Build a record's lineage: source system ids, authorship, recent changes, schema version, hierarchy path and the
     * export jobs consuming it.
     *
     * Each section is one query; sections the caller already read can be passed in so they are not read twice.
     *
     * @param  array<int, object>|null  $xrefs  rows of XrefRepository::entriesForRecord()
     * @param  Collection<int, Record>|null  $ancestors  rows of RecordRepository::ancestorsOf()
     * @return Lineage
     */
    public function forRecord(Record $record, ?array $xrefs = null, ?Collection $ancestors = null): array
    {
        $changes = $this->changes($record);

        return [
            'sources'      => $this->sources($xrefs ?? $this->xrefRepository->entriesForRecord($record->id)),
            'authorship'   => $this->authorship($record, $changes),
            'changes'      => $changes,
            'change_limit' => self::CHANGE_LIMIT,
            'schema'       => $this->schema($record),
            'path'         => $this->path($record, $ancestors),
            'consumers'    => $this->exportConsumers->forRecord($record),
        ];
    }

    /**
     * Group crosswalk rows by source system.
     *
     * @param  array<int, object>  $xrefs
     * @return list<LineageSource>
     */
    protected function sources(array $xrefs): array
    {
        $systems = [];

        foreach ($xrefs as $xref) {
            $systems[$xref->code] ??= [
                'code' => (string) $xref->code,
                'name' => (string) ($xref->name ?? $xref->code),
                'type' => (string) ($xref->type ?? ''),
                'ids'  => [],
            ];

            $systems[$xref->code]['ids'][] = [
                'external_id'  => (string) $xref->external_id,
                'status'       => (string) $xref->status,
                'attached_at'  => $this->date($xref->first_seen_at ?? null),
                'last_seen_at' => $this->date($xref->last_seen_at ?? null),
            ];
        }

        return array_values($systems);
    }

    /**
     * Resolve who created and last changed the record, falling back to the history when a column is empty.
     *
     * @param  list<LineageChange>  $changes
     * @return LineageAuthorship
     */
    protected function authorship(Record $record, array $changes): array
    {
        $ids = array_values(array_unique(array_filter([$record->created_by, $record->updated_by])));

        $names = $ids === []
            ? []
            : $this->adminRepository->findWhereIn('id', $ids, ['id', 'name'])->pluck('name', 'id')->all();

        $created = array_find($changes, fn (array $change): bool => $change['event'] === 'created');

        return [
            'created_at' => $this->date($record->created_at),
            'created_by' => ($record->created_by === null ? null : $names[$record->created_by] ?? null) ?? $created['user'] ?? null,
            'updated_at' => $this->date($record->updated_at),
            'updated_by' => ($record->updated_by === null ? null : $names[$record->updated_by] ?? null) ?? $changes[0]['user'] ?? null,
        ];
    }

    /**
     * Read the latest history entries of the record with the attributes and field codes each one changed.
     *
     * @return list<LineageChange>
     */
    protected function changes(Record $record): array
    {
        return $this->auditRepository
            ->latestForHistory($record->generateTags()[0], (int) $record->id, self::CHANGE_LIMIT)
            ->map(fn (Audit $audit): array => [
                'version' => $audit->version_id === null ? null : (int) $audit->version_id,
                'event'   => (string) $audit->event,
                'user'    => $audit->getAttribute('user'),
                'at'      => $this->date($audit->created_at),
                'changed' => $audit->event === 'created'
                    ? []
                    : $this->changedKeys((array) $audit->old_values, (array) $audit->new_values),
            ])
            ->values()
            ->all();
    }

    /**
     * Name the changed attributes, expanding the values document into the field codes that differ.
     *
     * @param  array<string, mixed>  $old
     * @param  array<string, mixed>  $new
     * @return list<string>
     */
    protected function changedKeys(array $old, array $new): array
    {
        $keys = [];

        foreach (array_unique([...array_keys($old), ...array_keys($new)]) as $key) {
            if ($key !== 'values') {
                $keys[] = (string) $key;

                continue;
            }

            $before = $this->flattenValues($old['values'] ?? null);
            $after = $this->flattenValues($new['values'] ?? null);

            foreach (array_unique([...array_keys($before), ...array_keys($after)]) as $field) {
                if (($before[$field] ?? null) !== ($after[$field] ?? null)) {
                    $keys[] = (string) $field;
                }
            }
        }

        return array_values(array_unique($keys));
    }

    /**
     * Flatten a stored values document into field code (with locale suffix) and JSON value pairs.
     *
     * @return array<string, string>
     */
    protected function flattenValues(mixed $values): array
    {
        if (is_string($values)) {
            $values = json_decode($values, true);
        }

        if (! is_array($values)) {
            return [];
        }

        $flat = [];

        foreach ((array) ($values['common'] ?? []) as $field => $value) {
            $flat[(string) $field] = (string) json_encode($value);
        }

        foreach ((array) ($values['locale_specific'] ?? []) as $locale => $localeValues) {
            foreach ((array) $localeValues as $field => $value) {
                $flat[$field.' ('.$locale.')'] = (string) json_encode($value);
            }
        }

        return $flat;
    }

    /**
     * Describe the schema version the record was last validated against next to the entity type's current one.
     *
     * @return LineageSchema
     */
    protected function schema(Record $record): array
    {
        $version = (int) $record->schema_version ?: null;

        $current = (int) $record->entityType->schema_version ?: null;

        $publication = $version === null
            ? null
            : $this->entityTypeVersionRepository->publications((int) $record->entity_type_id, [$version])->get($version);

        return [
            'version'         => $version,
            'current_version' => $current,
            'is_current'      => $version === $current,
            'published_at'    => $this->date($publication?->published_at),
            'published_by'    => $publication?->getAttribute('publisher'),
        ];
    }

    /**
     * List the record's visible ancestors from the root down, ending with the record itself.
     *
     * @param  Collection<int, Record>|null  $ancestors
     * @return list<LineagePathItem>
     */
    protected function path(Record $record, ?Collection $ancestors): array
    {
        $ancestors ??= $record->parent_id === null
            ? new Collection
            : $this->recordRepository->ancestorsOf($record);

        return $ancestors
            ->concat([$record])
            ->map(fn (Record $item): array => [
                'uuid'       => (string) $item->uuid,
                'code'       => (string) $item->code,
                'label'      => $item->label,
                'type'       => $item->entityType?->name ?: $item->entityType?->code,
                'is_current' => $item->is($record),
            ])
            ->values()
            ->all();
    }

    /**
     * Normalise a stored timestamp into a date instance.
     */
    protected function date(mixed $value): ?CarbonInterface
    {
        return $value === null || $value === '' ? null : Date::parse($value);
    }
}
