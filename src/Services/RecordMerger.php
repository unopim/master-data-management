<?php

namespace Webkul\MasterDataManagement\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Webkul\MasterDataManagement\Contracts\ChangeGate;
use Webkul\MasterDataManagement\Enums\ChangeOperation;
use Webkul\MasterDataManagement\Enums\RecordStatus;
use Webkul\MasterDataManagement\Events\RecordMerged;
use Webkul\MasterDataManagement\Events\RecordUnmerged;
use Webkul\MasterDataManagement\Events\RecordUpdated;
use Webkul\MasterDataManagement\Events\RecordXrefsChanged;
use Webkul\MasterDataManagement\Exceptions\StaleRecordException;
use Webkul\MasterDataManagement\Models\Record;
use Webkul\MasterDataManagement\Models\RecordMerge;
use Webkul\MasterDataManagement\Models\SurvivorshipRule;
use Webkul\MasterDataManagement\Quality\CompletenessCalculator;
use Webkul\MasterDataManagement\Repositories\RecordMergeRepository;
use Webkul\MasterDataManagement\Repositories\RecordRepository;
use Webkul\MasterDataManagement\Repositories\RelationRepository;
use Webkul\MasterDataManagement\Repositories\XrefRepository;
use Webkul\MasterDataManagement\ValueObjects\ChangeSet;
use Webkul\MasterDataManagement\ValueObjects\RecordWriteResult;
use Webkul\MasterDataManagement\ValueObjects\SourceContext;
use Webkul\User\Models\Admin;

/**
 * Folds a duplicate record into a surviving golden record, and undoes that.
 *
 * A merge keeps the survivor's identity, takes each field's value from the side survivorship picks, moves the merged
 * record's active source ids, children, relations and earlier merges onto the survivor, and marks it merged. The
 * merge log keeps what moved, so an unmerge puts back exactly that, leaving anything changed since alone.
 * Both pass the change gate, so an entity type that requires approval holds them until an approver applies them.
 */
class RecordMerger
{
    /**
     * The change request input keys a held merge or unmerge is replayed from.
     */
    public const string INPUT_MERGED = 'merged_id';

    public const string INPUT_MERGED_REVISION = 'merged_revision';

    public const string INPUT_CHOICES = 'choices';

    public const string INPUT_MERGE = 'merge_id';

    public function __construct(
        protected ChangeGate $changeGate,
        protected RecordRepository $recordRepository,
        protected RecordMergeRepository $recordMergeRepository,
        protected XrefRepository $xrefRepository,
        protected RelationRepository $relationRepository,
        protected RecordWriter $recordWriter,
        protected HierarchyService $hierarchyService,
        protected RecordIndexer $recordIndexer,
        protected RelationWriter $relationWriter,
        protected SurvivorshipResolver $survivorshipResolver,
        protected CompletenessCalculator $completenessCalculator,
    ) {}

    /**
     * Each field either record holds, and the label when a survivorship rule targets it, with both values and the
     * side the type's survivorship rules pick; a null side leaves the pick to the steward.
     *
     * @return array<string, array{survivor: array<string, mixed>, merged: array<string, mixed>, suggested: string|null}>
     */
    public function preview(Record $survivor, Record $merged): array
    {
        $references = $this->relationRepository->referenceUuids([(int) $survivor->id, (int) $merged->id]);

        $survivorReferences = $references[(int) $survivor->id] ?? [];

        $mergedReferences = $references[(int) $merged->id] ?? [];

        $suggestions = $this->survivorshipResolver->decide($survivor, $merged, $survivorReferences, $mergedReferences);

        $fieldCodes = $this->survivorshipResolver->fieldCodes($survivor->values ?? [], $merged->values ?? []);

        $fields = [];

        foreach ($suggestions as $code => $side) {
            $isLabel = $code === SurvivorshipRule::TARGET_LABEL && ! in_array($code, $fieldCodes, true)
                && ! array_key_exists($code, $survivorReferences + $mergedReferences);

            $fields[$code] = [
                'survivor'  => $isLabel ? ['common' => $survivor->label] : $this->sideValues($survivor->values ?? [], $survivorReferences, $code),
                'merged'    => $isLabel ? ['common' => $merged->label] : $this->sideValues($merged->values ?? [], $mergedReferences, $code),
                'suggested' => $side,
            ];
        }

        return $fields;
    }

    /**
     * Merge the record into the survivor at the revisions the caller read, or hold the merge for approval.
     *
     * @param  array<string, string>  $choices  Field code => `survivor` or `merged`; fields left out follow the type's
     *                                          survivorship rules, then the most complete value.
     *
     * @throws ValidationException
     * @throws StaleRecordException
     */
    public function merge(Record $survivor, Record $merged, array $choices, int $survivorRevision, int $mergedRevision, ?Admin $by, ?SourceContext $source = null): RecordWriteResult
    {
        $this->ensureMergeable($survivor, $merged);

        $choices = $this->validChoices($choices) + $this->ruleChoices($survivor, $merged);

        $changeSet = new ChangeSet(
            ChangeOperation::Merge,
            $survivor->entityType,
            $survivor,
            ['merged_record' => null, 'values' => $survivor->values ?? []],
            [
                'merged_record' => ['uuid' => (string) $merged->uuid, 'code' => (string) $merged->code, 'label' => $merged->label],
                'values'        => $this->survivorshipResolver->combineValues($survivor->values ?? [], $merged->values ?? [], $choices),
            ],
            $by,
            $source,
            $survivorRevision,
            [self::INPUT_MERGED => (int) $merged->id, self::INPUT_MERGED_REVISION => $mergedRevision, self::INPUT_CHOICES => $choices],
        );

        $gate = $this->changeGate->submit($changeSet);

        if ($gate->isPending()) {
            return new RecordWriteResult($survivor, $gate);
        }

        $merge = DB::transaction(function () use ($survivor, $merged, $choices, $survivorRevision, $mergedRevision, $by): RecordMerge {
            $this->claim($survivor, $survivorRevision, $merged, $mergedRevision);

            $this->ensureMergeable($survivor, $merged);

            $type = $survivor->entityType;

            $references = $this->relationRepository->referenceUuids([(int) $survivor->id, (int) $merged->id]);

            $survivorReferences = $references[(int) $survivor->id] ?? [];

            $survivorBefore = ['label' => $survivor->label, 'values' => $survivor->values ?? [], 'references' => $survivorReferences];

            $mergedBefore = ['status' => $merged->status?->value, 'parent_id' => $merged->parent_id];

            $values = $this->survivorshipResolver->combineValues($survivor->values ?? [], $merged->values ?? [], $choices);

            $takenReferences = $this->survivorshipResolver->combineReferences($survivorReferences, $references[(int) $merged->id] ?? [], $choices);

            $explicitLabel = ($choices['label'] ?? null) === SurvivorshipResolver::MERGED ? $merged->label : $survivor->label;

            $label = $this->recordWriter->resolveLabel($type, $values, $explicitLabel);

            $survivor->fill([
                'label'        => $label,
                'values'       => $values,
                'completeness' => $this->completenessCalculator->score($type, $values),
                'updated_by'   => $by?->id,
            ]);

            $survivor->save();

            $this->relationWriter->syncReferences($survivor, $takenReferences);

            $moved = [
                'xrefs'    => $this->xrefRepository->moveActive((int) $merged->id, (int) $survivor->id),
                'children' => $this->moveChildren($merged, $survivor),
                'inbound'  => $this->relationRepository->repointInbound((int) $merged->id, (int) $survivor->id),
                'outbound' => $this->relationRepository->repointOutbound((int) $merged->id, (int) $survivor->id),
                'chained'  => $this->recordRepository->repointMergedInto((int) $merged->id, (int) $survivor->id),
            ];

            $merged->forceFill([
                'status'         => RecordStatus::Merged,
                'merged_into_id' => $survivor->id,
                'updated_by'     => $by?->id,
            ])->save();

            $this->recordIndexer->sync($survivor);

            $this->recordIndexer->sync($merged);

            return $this->recordMergeRepository->create([
                'entity_type_id'  => $type->id,
                'survivor_id'     => $survivor->id,
                'merged_id'       => $merged->id,
                'survivor_before' => $survivorBefore,
                'merged_before'   => $mergedBefore,
                'result'          => ['label' => $survivor->label, 'values' => $values, 'references' => $takenReferences],
                'moved'           => $moved,
                'merged_by'       => $by?->id,
                'merged_at'       => now(),
            ]);
        });

        $this->dispatchMoved($survivor, $merge);

        event(new RecordMerged($survivor, $merged, $merge, $changeSet));

        event(new RecordUpdated($survivor, $changeSet));

        return new RecordWriteResult($survivor, $gate);
    }

    /**
     * Undo the standing merge of the record into its survivor, or hold the unmerge for approval.
     *
     * Values, references, source ids, children and relations go back only where they still hold what the merge
     * left, so changes made on the survivor since are kept.
     *
     * @throws ValidationException
     * @throws StaleRecordException
     */
    public function unmerge(Record $merged, ?Admin $by, ?SourceContext $source = null): RecordWriteResult
    {
        $merge = $this->standingMerge($merged);

        $survivor = $this->recordRepository->find((int) $merge->survivor_id)
            ?? throw ValidationException::withMessages(['merge' => trans('masterdata::app.records.merge.survivor-missing')]);

        $changeSet = new ChangeSet(
            ChangeOperation::Unmerge,
            $merged->entityType,
            $merged,
            ['status' => RecordStatus::Merged->value, 'merged_into' => (string) $survivor->code],
            ['status' => (string) ($merge->merged_before['status'] ?? RecordStatus::Active->value), 'merged_into' => null],
            $by,
            $source,
            (int) $merged->revision,
            [self::INPUT_MERGE => (int) $merge->id],
        );

        $gate = $this->changeGate->submit($changeSet);

        if ($gate->isPending()) {
            return new RecordWriteResult($merged, $gate);
        }

        $merge = DB::transaction(function () use ($merged, $survivor, $by): RecordMerge {
            $this->claim($survivor, (int) $survivor->revision, $merged, (int) $merged->revision);

            $merge = $this->recordMergeRepository->activeForMerged((int) $merged->id, true);

            if (! $merge instanceof RecordMerge || (int) $merge->survivor_id !== (int) $survivor->id) {
                throw ValidationException::withMessages(['merge' => trans('masterdata::app.records.merge.not-merged')]);
            }

            $before = $merge->survivor_before;

            $result = $merge->result;

            $moved = $merge->moved;

            $values = $this->restoreValues($survivor->values ?? [], $result['values'] ?? [], $before['values'] ?? []);

            $label = $survivor->label === ($result['label'] ?? null) ? ($before['label'] ?? null) : $survivor->label;

            $type = $survivor->entityType;

            $survivor->fill([
                'label'        => $this->recordWriter->resolveLabel($type, $values, $label),
                'values'       => $values,
                'completeness' => $this->completenessCalculator->score($type, $values),
                'updated_by'   => $by?->id,
            ]);

            $survivor->save();

            $this->relationWriter->syncReferences($survivor, $this->restorableReferences($survivor, $result['references'] ?? [], $before['references'] ?? []));

            $this->xrefRepository->moveBack($moved['xrefs'] ?? [], (int) $survivor->id, (int) $merged->id);

            $this->relationRepository->restoreEnd($moved['inbound'] ?? [], 'to_record_id', (int) $survivor->id, (int) $merged->id);

            $this->relationRepository->restoreEnd($moved['outbound'] ?? [], 'from_record_id', (int) $survivor->id, (int) $merged->id);

            $this->recordRepository->restoreMergedInto($moved['chained'] ?? [], (int) $survivor->id, (int) $merged->id);

            $merged->forceFill([
                'status'         => RecordStatus::tryFrom((string) ($merge->merged_before['status'] ?? '')) ?? RecordStatus::Active,
                'merged_into_id' => null,
                'updated_by'     => $by?->id,
            ])->save();

            $this->moveChildrenBack($moved['children'] ?? [], $survivor, $merged);

            $this->recordIndexer->sync($survivor);

            $this->recordIndexer->sync($merged);

            $merge->forceFill(['unmerged_by' => $by?->id, 'unmerged_at' => now()])->save();

            return $merge;
        });

        $this->dispatchMoved($survivor, $merge, $merged);

        event(new RecordUnmerged($survivor, $merged, $merge, $changeSet));

        event(new RecordUpdated($survivor, $changeSet));

        return new RecordWriteResult($merged, $gate);
    }

    /**
     * The standing merge of a merged record, else a validation error.
     *
     * @throws ValidationException
     */
    public function standingMerge(Record $merged): RecordMerge
    {
        $merge = $merged->status === RecordStatus::Merged ? $this->recordMergeRepository->activeForMerged((int) $merged->id) : null;

        return $merge ?? throw ValidationException::withMessages(['merge' => trans('masterdata::app.records.merge.not-merged')]);
    }

    /**
     * Refuse a merge across types, into itself, of a record out of the active lifecycle, or of an ancestor of the
     * survivor, whose children would then hang below their own descendant.
     *
     * @throws ValidationException
     */
    protected function ensureMergeable(Record $survivor, Record $merged): void
    {
        $message = match (true) {
            (int) $survivor->id === (int) $merged->id                         => 'same-record',
            (int) $survivor->entity_type_id !== (int) $merged->entity_type_id => 'different-type',
            ! in_array($survivor->status, [RecordStatus::Draft, RecordStatus::Active], true),
            ! in_array($merged->status, [RecordStatus::Draft, RecordStatus::Active], true)              => 'not-mergeable',
            $merged->path !== null && str_starts_with((string) $survivor->path, (string) $merged->path) => 'ancestor',
            default                                                                                     => null,
        };

        if ($message !== null) {
            throw ValidationException::withMessages(['merged' => trans('masterdata::app.records.merge.'.$message)]);
        }
    }

    /**
     * Keep only choices naming a known side.
     *
     * @param  array<array-key, mixed>  $choices
     * @return array<string, string>
     */
    protected function validChoices(array $choices): array
    {
        $valid = [];

        foreach ($choices as $code => $side) {
            if (in_array($side, [SurvivorshipResolver::SURVIVOR, SurvivorshipResolver::MERGED], true)) {
                $valid[(string) $code] = $side;
            }
        }

        return $valid;
    }

    /**
     * The sides the entity type's survivorship rules pick, leaving out the targets a rule hands to the steward.
     *
     * @return array<string, string>
     */
    protected function ruleChoices(Record $survivor, Record $merged): array
    {
        $references = $this->relationRepository->referenceUuids([(int) $survivor->id, (int) $merged->id]);

        return array_filter(
            $this->survivorshipResolver->ruledSides($survivor, $merged, $references[(int) $survivor->id] ?? [], $references[(int) $merged->id] ?? []),
            fn (?string $side): bool => $side !== null,
        );
    }

    /**
     * Claim both revisions in id order, so two merges over the same pair cannot deadlock, then re-read both rows.
     *
     * @throws StaleRecordException
     */
    protected function claim(Record $survivor, int $survivorRevision, Record $merged, int $mergedRevision): void
    {
        $claims = [[$survivor, $survivorRevision], [$merged, $mergedRevision]];

        usort($claims, fn (array $left, array $right): int => $left[0]->id <=> $right[0]->id);

        foreach ($claims as [$record, $revision]) {
            throw_unless($this->recordRepository->claimRevision($record, $revision), StaleRecordException::class, $record, $revision);

            $record->refresh();
        }
    }

    /**
     * Place the merged record's children below the survivor, each under its own revision claim.
     *
     * @return list<int>
     *
     * @throws ValidationException
     */
    protected function moveChildren(Record $from, Record $to): array
    {
        $limit = (int) config('masterdata.merge.max_children', 500);

        $ids = $this->recordRepository->childIds((int) $from->id, $limit + 1);

        if (count($ids) > $limit) {
            throw ValidationException::withMessages(['merged' => trans('masterdata::app.records.merge.too-many-children', ['count' => $limit])]);
        }

        foreach ($ids as $id) {
            $this->reparent($id, $to);
        }

        return $ids;
    }

    /**
     * Place the given children back below the original parent while they still hang below the survivor.
     *
     * @param  list<int>  $ids
     */
    protected function moveChildrenBack(array $ids, Record $survivor, Record $original): void
    {
        foreach ($ids as $id) {
            if ($this->recordRepository->hierarchyRow((int) $id)?->parent_id === (int) $survivor->id) {
                $this->reparent((int) $id, $original);
            }
        }
    }

    /**
     * Move one record below a new parent under a claim of its current revision.
     *
     * @throws StaleRecordException
     * @throws ValidationException
     */
    protected function reparent(int $id, Record $parent): void
    {
        $child = $this->recordRepository->lockedRow($id);

        if (! $child instanceof Record) {
            return;
        }

        throw_unless($this->recordRepository->claimRevision($child, (int) $child->revision), StaleRecordException::class, $child, (int) $child->revision);

        $child->refresh();

        $this->hierarchyService->reparent($child, $parent);

        $child->save();
    }

    /**
     * Put back each value the merge changed and nobody has changed since.
     *
     * @param  array<string, mixed>  $current
     * @param  array<string, mixed>  $result
     * @param  array<string, mixed>  $before
     * @return array<string, mixed>
     */
    protected function restoreValues(array $current, array $result, array $before): array
    {
        foreach ($this->survivorshipResolver->fieldCodes($result, $before) as $code) {
            $sections = ['common' => [$current['common'] ?? [], $result['common'] ?? [], $before['common'] ?? []]];

            $locales = array_unique([...array_keys($result['locale_specific'] ?? []), ...array_keys($before['locale_specific'] ?? [])]);

            foreach ($locales as $locale) {
                $sections[(string) $locale] = [
                    $current['locale_specific'][$locale] ?? [],
                    $result['locale_specific'][$locale] ?? [],
                    $before['locale_specific'][$locale] ?? [],
                ];
            }

            foreach ($sections as $section => [$now, $merged, $original]) {
                if (($now[$code] ?? null) !== ($merged[$code] ?? null) || ($merged[$code] ?? null) === ($original[$code] ?? null)) {
                    continue;
                }

                if ($section === 'common') {
                    if (array_key_exists($code, $original)) {
                        $current['common'][$code] = $original[$code];
                    } else {
                        unset($current['common'][$code]);
                    }
                } elseif (array_key_exists($code, $original)) {
                    $current['locale_specific'][$section][$code] = $original[$code];
                } else {
                    unset($current['locale_specific'][$section][$code]);
                }
            }
        }

        return $current;
    }

    /**
     * The reference fields to put back: those the merge took that still point at what it took.
     *
     * @param  array<string, list<string>>  $taken
     * @param  array<string, list<string>>  $before
     * @return array<string, list<string>>
     */
    protected function restorableReferences(Record $survivor, array $taken, array $before): array
    {
        if ($taken === []) {
            return [];
        }

        $current = $this->relationRepository->referenceUuids([(int) $survivor->id])[(int) $survivor->id] ?? [];

        $restore = [];

        foreach ($taken as $code => $uuids) {
            $now = $current[$code] ?? [];

            sort($now);

            sort($uuids);

            if ($now === $uuids) {
                $restore[$code] = $before[$code] ?? [];
            }
        }

        return $restore;
    }

    /**
     * Tell listeners about the source ids that changed records.
     */
    protected function dispatchMoved(Record $survivor, RecordMerge $merge, ?Record $merged = null): void
    {
        if (($merge->moved['xrefs'] ?? []) === []) {
            return;
        }

        event(new RecordXrefsChanged($survivor));

        if ($merged instanceof Record) {
            event(new RecordXrefsChanged($merged));
        }
    }

    /**
     * One side's values of a field: its common and per-locale values, or its reference targets.
     *
     * @param  array<string, mixed>  $values
     * @param  array<string, list<string>>  $references
     * @return array<string, mixed>
     */
    protected function sideValues(array $values, array $references, string $code): array
    {
        return array_key_exists($code, $references)
            ? ['common' => $references[$code]]
            : $this->survivorshipResolver->valuesOf($values, $code);
    }
}
