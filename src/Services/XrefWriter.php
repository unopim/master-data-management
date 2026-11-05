<?php

namespace Webkul\MasterDataManagement\Services;

use Illuminate\Validation\ValidationException;
use Webkul\MasterDataManagement\Contracts\ChangeGate;
use Webkul\MasterDataManagement\Enums\ChangeOperation;
use Webkul\MasterDataManagement\Enums\RecordStatus;
use Webkul\MasterDataManagement\Enums\XrefStatus;
use Webkul\MasterDataManagement\Exceptions\XrefConflictException;
use Webkul\MasterDataManagement\Models\Record;
use Webkul\MasterDataManagement\Models\SourceSystem;
use Webkul\MasterDataManagement\Models\Xref;
use Webkul\MasterDataManagement\Repositories\XrefRepository;
use Webkul\MasterDataManagement\ValueObjects\ChangeSet;
use Webkul\MasterDataManagement\ValueObjects\GateResult;
use Webkul\MasterDataManagement\ValueObjects\SourceContext;
use Webkul\User\Models\Admin;

/**
 * Crosswalk maintenance by people: links and unlinks pass the change gate like record writes, so an entity type that
 * requires approval holds them as change requests until an approver applies them through the crosswalk service.
 */
class XrefWriter
{
    /**
     * The change request input keys a held link or unlink is replayed from.
     */
    public const string INPUT_SYSTEM = 'source_system_id';

    public const string INPUT_EXTERNAL_ID = 'external_id';

    public const string INPUT_XREF = 'xref_id';

    public function __construct(
        protected ChangeGate $changeGate,
        protected XrefService $xrefService,
        protected XrefRepository $xrefRepository,
    ) {}

    /**
     * Link an external id of the source system to the record, or hold the link for approval.
     *
     * An id already active on another record is refused before anything is held, so the author sees the conflict.
     * An automated link is held whenever the entity type requires approval, even when the author may skip it.
     *
     * @throws XrefConflictException
     * @throws ValidationException
     */
    public function link(Record $record, SourceSystem $system, string $externalId, ?Admin $by, ?SourceContext $source = null, bool $automated = false): GateResult
    {
        $this->ensureEditable($record);

        $externalId = trim($externalId);

        $existing = $this->xrefRepository->findByExternalId((int) $system->id, (int) $record->entity_type_id, $externalId);

        if ($existing instanceof Xref && $existing->status === XrefStatus::Active && (int) $existing->record_id !== (int) $record->id) {
            throw new XrefConflictException($system, $externalId, (int) $existing->record_id);
        }

        $entry = [
            'source_system_id' => (int) $system->id,
            'source_system'    => (string) $system->code,
            'external_id'      => $externalId,
        ];

        $gate = $this->changeGate->submit(new ChangeSet(
            ChangeOperation::LinkXref,
            $record->entityType,
            $record,
            ['xref' => null],
            ['xref' => $entry],
            $by,
            $source,
            (int) $record->revision,
            [self::INPUT_SYSTEM => (int) $system->id, self::INPUT_EXTERNAL_ID => $externalId],
            $automated,
        ));

        if (! $gate->isPending()) {
            $this->xrefService->attach($record, $system, $externalId);
        }

        return $gate;
    }

    /**
     * Unlink an active external id of the record, or hold the unlink for approval; null when the record has no such
     * active entry.
     *
     * @throws ValidationException
     */
    public function unlink(Record $record, int $xrefId, ?Admin $by, ?SourceContext $source = null): ?GateResult
    {
        $this->ensureEditable($record);

        $xref = $this->xrefRepository->findActiveForRecord((int) $record->id, $xrefId);

        if (! $xref instanceof Xref) {
            return null;
        }

        $gate = $this->changeGate->submit(new ChangeSet(
            ChangeOperation::UnlinkXref,
            $record->entityType,
            $record,
            ['xref' => [
                'source_system_id' => (int) $xref->source_system_id,
                'source_system'    => (string) $xref->sourceSystem?->code,
                'external_id'      => (string) $xref->external_id,
            ]],
            ['xref' => null],
            $by,
            $source,
            (int) $record->revision,
            [self::INPUT_XREF => (int) $xref->id],
        ));

        if (! $gate->isPending() && ! $this->xrefService->detach($record, (int) $xref->id)) {
            return null;
        }

        return $gate;
    }

    /**
     * Refuse crosswalk changes on records that left the active lifecycle.
     *
     * @throws ValidationException
     */
    protected function ensureEditable(Record $record): void
    {
        if (! in_array($record->status, [RecordStatus::Draft, RecordStatus::Active], true)) {
            throw ValidationException::withMessages(['status' => trans('masterdata::app.records.not-editable')]);
        }
    }
}
