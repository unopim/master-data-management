<?php

namespace Webkul\MasterDataManagement\Importers\Record;

use Webkul\MasterDataManagement\Enums\XrefStatus;
use Webkul\MasterDataManagement\Models\EntityType;
use Webkul\MasterDataManagement\Models\Record;
use Webkul\MasterDataManagement\Models\SourceSystem;
use Webkul\MasterDataManagement\Models\Xref;
use Webkul\MasterDataManagement\Repositories\RecordRepository;
use Webkul\MasterDataManagement\Repositories\XrefRepository;
use Webkul\User\Models\Admin;

/**
 * The records and crosswalk entries one import batch touches, read in a few queries per batch: records by code, the
 * parents rows name (in the type itself or in its allowed parent types), and the source system's entries for the
 * batch's external ids with the records they point to. Records the batch writes are remembered, so later rows can
 * name them as parents.
 */
class Storage
{
    /**
     * @var array<string, Record>
     */
    protected array $byCode = [];

    /**
     * @var array<int, Record>
     */
    protected array $byId = [];

    /**
     * @var array<string, Xref>
     */
    protected array $xrefs = [];

    /**
     * Candidate parents of other entity types, by entity type id in parent priority order, then code.
     *
     * @var array<int, array<string, Record>>
     */
    protected array $parents = [];

    public function __construct(
        protected RecordRepository $recordRepository,
        protected XrefRepository $xrefRepository,
    ) {}

    /**
     * Load the batch's records and parents the author may see, and the source system's entries for its external ids.
     *
     * @param  list<string>  $codes
     * @param  list<string>  $externalIds
     * @param  list<string>  $parentCodes
     * @param  list<int>  $parentTypeIds  The entity types parents may come from, in priority order.
     */
    public function load(EntityType $type, Admin $author, array $codes, ?SourceSystem $system = null, array $externalIds = [], array $parentCodes = [], array $parentTypeIds = []): void
    {
        $ownParents = in_array((int) $type->id, $parentTypeIds, true) ? $parentCodes : [];

        $this->byCode = $this->recordRepository->findManyByCodes((int) $type->id, [...$codes, ...$ownParents], $author)->all();

        $this->parents = [];

        foreach ($parentCodes === [] ? [] : $parentTypeIds as $typeId) {
            if ($typeId !== (int) $type->id) {
                $this->parents[$typeId] = $this->recordRepository->findManyByCodes($typeId, $parentCodes, $author)->all();
            }
        }

        $this->byId = [];

        foreach ($this->byCode as $record) {
            $this->byId[(int) $record->id] = $record;
        }

        $this->xrefs = $system instanceof SourceSystem
            ? $this->xrefRepository->findManyByExternalIds((int) $system->id, (int) $type->id, $externalIds)->all()
            : [];

        $missing = [];

        foreach ($this->xrefs as $xref) {
            if ($xref->status === XrefStatus::Active && ! isset($this->byId[(int) $xref->record_id])) {
                $missing[] = (int) $xref->record_id;
            }
        }

        foreach ($this->recordRepository->findManyForEntityType((int) $type->id, $missing, $author) as $record) {
            $this->byId[(int) $record->id] = $record;
        }
    }

    /**
     * The record with the code, when the author may see it.
     */
    public function byCode(string $code): ?Record
    {
        return $this->byCode[$code] ?? null;
    }

    /**
     * The parent a row names: a record of the type itself when its type may parent it, else the first allowed parent
     * type holding the code.
     *
     * @param  list<int>  $parentTypeIds
     */
    public function parent(EntityType $type, string $code, array $parentTypeIds): ?Record
    {
        foreach ($parentTypeIds as $typeId) {
            $parent = $typeId === (int) $type->id ? $this->byCode($code) : ($this->parents[$typeId][$code] ?? null);

            if ($parent instanceof Record) {
                return $parent;
            }
        }

        return null;
    }

    /**
     * The record an active entry of the external id points to, when the author may see it.
     */
    public function byExternalId(string $externalId): ?Record
    {
        $xref = $this->xref($externalId);

        return $xref instanceof Xref && $xref->status === XrefStatus::Active ? ($this->byId[(int) $xref->record_id] ?? null) : null;
    }

    /**
     * The source system's entry for the external id, whatever its status.
     */
    public function xref(string $externalId): ?Xref
    {
        return $this->xrefs[$externalId] ?? null;
    }

    /**
     * Remember a record the batch wrote, so later rows find it by code.
     */
    public function remember(Record $record): void
    {
        $this->byCode[(string) $record->code] = $record;

        $this->byId[(int) $record->id] = $record;
    }
}
