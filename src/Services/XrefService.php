<?php

namespace Webkul\MasterDataManagement\Services;

use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Webkul\MasterDataManagement\Enums\XrefStatus;
use Webkul\MasterDataManagement\Events\RecordXrefsChanged;
use Webkul\MasterDataManagement\Exceptions\XrefConflictException;
use Webkul\MasterDataManagement\Models\Record;
use Webkul\MasterDataManagement\Models\SourceSystem;
use Webkul\MasterDataManagement\Models\Xref;
use Webkul\MasterDataManagement\Repositories\RecordRepository;
use Webkul\MasterDataManagement\Repositories\SourceSystemRepository;
use Webkul\MasterDataManagement\Repositories\XrefRepository;

class XrefService
{
    protected const MAX_MERGE_HOPS = 10;

    public function __construct(
        protected XrefRepository $xrefRepository,
        protected RecordRepository $recordRepository,
        protected SourceSystemRepository $sourceSystemRepository,
    ) {}

    /**
     * Link an external id of a source system to a record.
     *
     * Re-attaching the same id to the same record only refreshes last_seen_at; any other link moves the record's
     * updated_at, so "changed since last run" exports pick the record up. A retired entry may be claimed
     * by any record of the type. A lost insert race is resolved by re-reading the winning row.
     *
     * The create runs in a savepoint so a lost race leaves any outer PostgreSQL transaction usable.
     *
     * @throws XrefConflictException
     * @throws ValidationException
     */
    public function attach(Record $record, SourceSystem $system, string $externalId): Xref
    {
        $externalId = $this->normalize($externalId);

        $existing = $this->xrefRepository->findByExternalId($system->id, $record->entity_type_id, $externalId);

        if (! $existing instanceof Xref) {
            try {
                $created = DB::transaction(fn (): Xref => $this->xrefRepository->create([
                    'record_id'        => $record->id,
                    'entity_type_id'   => $record->entity_type_id,
                    'source_system_id' => $system->id,
                    'external_id'      => $externalId,
                    'status'           => XrefStatus::Active->value,
                    'first_seen_at'    => now(),
                    'last_seen_at'     => now(),
                ]));

                $this->recordRepository->touchUpdatedAt($record);

                event(new RecordXrefsChanged($record));

                return $created;
            } catch (UniqueConstraintViolationException $e) {
                $existing = $this->xrefRepository->findByExternalId($system->id, $record->entity_type_id, $externalId)
                    ?? throw $e;
            }
        }

        if ($existing->record_id !== $record->id && $existing->status === XrefStatus::Active) {
            throw new XrefConflictException($system, $externalId, $existing->record_id);
        }

        $relinks = $existing->record_id !== $record->id || $existing->status !== XrefStatus::Active;

        $existing->forceFill([
            'record_id'        => $record->id,
            'status'           => XrefStatus::Active,
            'last_pushed_hash' => $existing->record_id === $record->id ? $existing->last_pushed_hash : null,
            'last_seen_at'     => now(),
        ])->save();

        if ($relinks) {
            $this->recordRepository->touchUpdatedAt($record);
        }

        event(new RecordXrefsChanged($record));

        return $existing;
    }

    /**
     * Retire an active external id of the record, so it no longer resolves and any record may claim it again.
     *
     * Moves the record's updated_at like a link does. Returns false when the record has no such active entry.
     */
    public function detach(Record $record, int $xrefId): bool
    {
        $xref = $this->xrefRepository->findActiveForRecord($record->id, $xrefId);

        if (! $xref instanceof Xref) {
            return false;
        }

        $xref->forceFill([
            'status'       => XrefStatus::Retired,
            'last_seen_at' => now(),
        ])->save();

        $this->recordRepository->touchUpdatedAt($record);

        event(new RecordXrefsChanged($record));

        return true;
    }

    /**
     * Resolve a source system's external id to the golden record, following merges to the survivor.
     *
     * Only active entries resolve. The record is read through the scoped repository, so a record the
     * authenticated admin may not see resolves to null.
     */
    public function resolve(SourceSystem $system, int $entityTypeId, string $externalId): ?Record
    {
        $externalId = trim($externalId);

        $xref = $this->xrefRepository->findByExternalId($system->id, $entityTypeId, $externalId);

        if (! $xref instanceof Xref || $xref->status !== XrefStatus::Active) {
            return null;
        }

        $record = $this->recordRepository->find($xref->record_id);

        for ($hops = 0; $record?->merged_into_id !== null && $hops < self::MAX_MERGE_HOPS; $hops++) {
            $record = $this->recordRepository->find($record->merged_into_id);
        }

        return $record?->merged_into_id === null ? $record : null;
    }

    /**
     * Map each source system code to the record's active external ids, in one query.
     *
     * @return array<string, list<string>>
     */
    public function idsFor(Record $record): array
    {
        $ids = [];

        foreach ($this->xrefRepository->activeIdsForRecord($record->id) as $row) {
            $ids[$row->code][] = $row->external_id;
        }

        return $ids;
    }

    /**
     * Trim an external id and reject empty or over-long values.
     *
     * @throws ValidationException
     */
    protected function normalize(string $externalId): string
    {
        $externalId = trim($externalId);

        if ($externalId === '' || mb_strlen($externalId) > 255) {
            throw ValidationException::withMessages([
                'external_id' => trans('masterdata::app.xrefs.invalid-external-id'),
            ]);
        }

        return $externalId;
    }
}
