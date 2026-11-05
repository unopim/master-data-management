<?php

namespace Webkul\MasterDataManagement\Matching;

use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Webkul\MasterDataManagement\Enums\MatchCandidateStatus;
use Webkul\MasterDataManagement\Enums\RecordStatus;
use Webkul\MasterDataManagement\Enums\XrefStatus;
use Webkul\MasterDataManagement\Exceptions\XrefConflictException;
use Webkul\MasterDataManagement\Models\EntityType;
use Webkul\MasterDataManagement\Models\MatchCandidate;
use Webkul\MasterDataManagement\Models\Record;
use Webkul\MasterDataManagement\Models\SourceSystem;
use Webkul\MasterDataManagement\Models\Xref;
use Webkul\MasterDataManagement\Repositories\MatchCandidateRepository;
use Webkul\MasterDataManagement\Repositories\RecordRepository;
use Webkul\MasterDataManagement\Repositories\XrefRepository;
use Webkul\MasterDataManagement\Services\RecordWriter;
use Webkul\MasterDataManagement\Services\XrefService;
use Webkul\MasterDataManagement\Services\XrefWriter;
use Webkul\MasterDataManagement\ValueObjects\SourceContext;
use Webkul\User\Models\Admin;

/**
 * The match inbox: incoming source rows no crosswalk entry resolves, and the steward decisions on them.
 *
 * Every decision locks the inbox row, so two stewards cannot resolve the same row twice. An entity type may opt in to
 * automatic accepts with `settings.auto_match_threshold`: a submitted row whose best suggestion reaches it, with no
 * second suggestion reaching it too, is accepted on submit through the steward path, and held for approval whenever
 * the type requires it.
 */
class MatchInbox
{
    /**
     * The change request context key naming the inbox row a held create came from.
     */
    public const string CONTEXT_KEY = 'match_candidate_id';

    /**
     * The change request context key carrying the score of an automatic accept.
     */
    public const string AUTO_CONTEXT_KEY = 'auto_match_score';

    /**
     * The entity type setting holding the automatic accept threshold; absent or null turns automatic accepts off.
     */
    public const string THRESHOLD_SETTING = 'auto_match_threshold';

    /**
     * The lowest and highest threshold an entity type may set.
     */
    public const float MIN_THRESHOLD = 0.5;

    public const float MAX_THRESHOLD = 1.0;

    public function __construct(
        protected MatchCandidateRepository $matchCandidateRepository,
        protected XrefRepository $xrefRepository,
        protected XrefService $xrefService,
        protected MatchSuggester $matchSuggester,
        protected RecordWriter $recordWriter,
        protected XrefWriter $xrefWriter,
        protected RecordRepository $recordRepository,
    ) {}

    /**
     * Put an incoming source row into the inbox, or reopen the row already held for the same external id.
     *
     * The payload may carry `code`, `label` and `values` in the record writer's shape. An external id that already
     * resolves through an active crosswalk entry is refused; a lost insert race re-reads and updates the winning row.
     * The row is then accepted automatically when the entity type's threshold allows it.
     *
     * @param  array{code?: ?string, label?: ?string, values?: array<string, mixed>}  $payload
     *
     * @throws ValidationException
     */
    public function submit(SourceSystem $system, EntityType $type, string $externalId, array $payload, ?Admin $by): MatchCandidate
    {
        $externalId = trim($externalId);

        $xref = $this->xrefRepository->findByExternalId($system->id, $type->id, $externalId);

        if ($xref instanceof Xref && $xref->status === XrefStatus::Active) {
            throw ValidationException::withMessages([
                'external_id' => trans('masterdata::app.matching.already-linked', ['system' => $system->code, 'id' => $externalId]),
            ]);
        }

        $payload = array_filter(Arr::only($payload, ['code', 'label', 'values']), fn (mixed $value): bool => $value !== null && $value !== '');

        $suggestions = $this->matchSuggester->suggest($type, $payload, 2, $by);

        $attributes = [
            'label'             => isset($payload['label']) ? mb_substr((string) $payload['label'], 0, 255) : null,
            'payload'           => $payload,
            'status'            => MatchCandidateStatus::Pending,
            'top_score'         => $suggestions[0]->score ?? null,
            'auto_matched'      => false,
            'matched_record_id' => null,
            'submitted_by'      => $by?->id,
            'resolved_by'       => null,
            'resolved_at'       => null,
        ];

        $candidate = $this->store($system, $type, $externalId, $attributes);

        $best = $this->automaticMatch($type, $suggestions);

        if ($best instanceof MatchSuggestion) {
            $this->acceptAutomatically($candidate, $best, $by);
        }

        return $candidate;
    }

    /**
     * The entity type's automatic accept threshold, or null when automatic accepts are off.
     */
    public function threshold(EntityType $type): ?float
    {
        $threshold = $type->settings[self::THRESHOLD_SETTING] ?? null;

        if (! is_numeric($threshold)) {
            return null;
        }

        $threshold = (float) $threshold;

        return $threshold >= self::MIN_THRESHOLD && $threshold <= self::MAX_THRESHOLD ? $threshold : null;
    }

    /**
     * The live suggestions for an inbox row.
     *
     * @return list<MatchSuggestion>
     */
    public function suggestionsFor(MatchCandidate $candidate, int $limit = 5, ?Admin $admin = null): array
    {
        return $this->matchSuggester->suggest($candidate->entityType, (array) $candidate->payload, $limit, $admin);
    }

    /**
     * Link the row's external id to an existing draft or active record of the same entity type and close the row
     * as matched.
     *
     * The link passes the change gate. Returns null when the gate holds it for approval; the row then stays pending,
     * and the change request carries the row id so its approval closes the row.
     *
     * @throws ValidationException
     */
    public function accept(MatchCandidate $candidate, Record $record, Admin $by): ?MatchCandidate
    {
        return $this->link($candidate, $record, $by);
    }

    /**
     * Create the inbox row, or reopen the one a lost insert race or an earlier submit left for the external id.
     *
     * @param  array<string, mixed>  $attributes
     */
    protected function store(SourceSystem $system, EntityType $type, string $externalId, array $attributes): MatchCandidate
    {
        $existing = $this->matchCandidateRepository->findByExternalId($system->id, $type->id, $externalId);

        if (! $existing instanceof MatchCandidate) {
            try {
                return DB::transaction(fn (): MatchCandidate => $this->matchCandidateRepository->create($attributes + [
                    'source_system_id' => $system->id,
                    'entity_type_id'   => $type->id,
                    'external_id'      => $externalId,
                ]));
            } catch (UniqueConstraintViolationException $exception) {
                $existing = $this->matchCandidateRepository->findByExternalId($system->id, $type->id, $externalId)
                    ?? throw $exception;
            }
        }

        $existing->forceFill($attributes)->save();

        return $existing;
    }

    /**
     * The suggestion to accept automatically: the best one when it reaches the type's threshold and no other does.
     *
     * @param  list<MatchSuggestion>  $suggestions
     */
    protected function automaticMatch(EntityType $type, array $suggestions): ?MatchSuggestion
    {
        $threshold = $this->threshold($type);

        if ($threshold === null || ! isset($suggestions[0]) || $suggestions[0]->score < $threshold) {
            return null;
        }

        return isset($suggestions[1]) && $suggestions[1]->score >= $threshold ? null : $suggestions[0];
    }

    /**
     * Accept the suggestion for the row as an automated decision; the row stays pending and unflagged when the
     * submitter may not edit the record or the link is refused, as a steward would have to decide it anyway.
     */
    protected function acceptAutomatically(MatchCandidate $candidate, MatchSuggestion $suggestion, ?Admin $by): void
    {
        $record = $this->recordRepository->find($suggestion->record->id);

        if (! $record instanceof Record || ($by instanceof Admin && Gate::forUser($by)->denies('update', $record))) {
            return;
        }

        try {
            $this->link($candidate, $record, $by, $suggestion->score);
        } catch (ValidationException) {
            return;
        }

        $candidate->refresh();
    }

    /**
     * Link the row's external id to the record through the change gate, closing the row unless the gate holds it.
     *
     * An automatic accept passes its score; the link is then an automated change, held whenever the type requires
     * approval, and the row is flagged as auto matched.
     *
     * @throws ValidationException
     */
    protected function link(MatchCandidate $candidate, Record $record, ?Admin $by, ?float $autoScore = null): ?MatchCandidate
    {
        if ((int) $record->entity_type_id !== (int) $candidate->entity_type_id) {
            throw ValidationException::withMessages(['record' => trans('masterdata::app.matching.wrong-entity-type')]);
        }

        if (! in_array($record->status, [RecordStatus::Draft, RecordStatus::Active], true)) {
            throw ValidationException::withMessages(['record' => trans('masterdata::app.records.not-editable')]);
        }

        return DB::transaction(function () use ($candidate, $record, $by, $autoScore): ?MatchCandidate {
            $locked = $this->lockPending($candidate);

            $automated = $autoScore !== null;

            try {
                $gate = $this->xrefWriter->link(
                    $record,
                    $locked->sourceSystem,
                    (string) $locked->external_id,
                    $by,
                    new SourceContext('matching', (int) $locked->source_system_id, (string) $locked->external_id, array_filter([
                        self::CONTEXT_KEY      => (int) $locked->id,
                        self::AUTO_CONTEXT_KEY => $autoScore,
                    ], fn (mixed $value): bool => $value !== null)),
                    $automated,
                );
            } catch (XrefConflictException $exception) {
                throw ValidationException::withMessages(['record' => $exception->getMessage()]);
            }

            if ($automated) {
                $locked->forceFill(['auto_matched' => true])->save();
            }

            return $gate->isPending() ? null : $this->resolve($locked, MatchCandidateStatus::Matched, $by, $record);
        });
    }

    /**
     * Create a new record from the row through the record writer, link the external id to it and close the row.
     *
     * Returns null when the change gate holds the create for approval; the row then stays pending, and the change
     * request carries the row id so its approval links and closes the row.
     *
     * @param  array{code?: ?string, label?: ?string}  $input
     *
     * @throws ValidationException
     */
    public function createRecord(MatchCandidate $candidate, array $input, Admin $by): ?Record
    {
        return DB::transaction(function () use ($candidate, $input, $by): ?Record {
            $locked = $this->lockPending($candidate);

            $payload = (array) $locked->payload;

            $writerInput = array_filter([
                'code'   => $input['code'] ?? null,
                'label'  => $input['label'] ?? $payload['label'] ?? null,
                'values' => $payload['values'] ?? null,
            ], fn (mixed $value): bool => ! in_array($value, [null, '', []], true));

            $record = $this->recordWriter->create(
                $locked->entityType,
                $writerInput,
                $by,
                new SourceContext('matching', (int) $locked->source_system_id, (string) $locked->external_id, [
                    self::CONTEXT_KEY => (int) $locked->id,
                ]),
            );

            if (! $record->exists) {
                return null;
            }

            $this->attach($locked, $record);

            $this->resolve($locked, MatchCandidateStatus::Created, $by, $record);

            return $record;
        });
    }

    /**
     * Link a create approved after the change gate held it to the inbox row it came from, and close the row.
     *
     * Returns null, leaving the row as it is, when the row was resolved meanwhile or its external id now links
     * to another record; the approved record is kept either way.
     */
    public function completeApprovedCreate(int $candidateId, Record $record, Admin $by): ?MatchCandidate
    {
        return $this->completeApproved($candidateId, $record, $by, MatchCandidateStatus::Created);
    }

    /**
     * Close the inbox row an approved link came from as matched; the approval already linked its external id.
     *
     * Returns null, leaving the row as it is, when the row was resolved meanwhile.
     */
    public function completeApprovedLink(int $candidateId, Record $record, Admin $by): ?MatchCandidate
    {
        return $this->completeApproved($candidateId, $record, $by, MatchCandidateStatus::Matched);
    }

    /**
     * Close the row without linking or creating anything.
     *
     * @throws ValidationException
     */
    public function reject(MatchCandidate $candidate, Admin $by): MatchCandidate
    {
        return DB::transaction(fn (): MatchCandidate => $this->resolve($this->lockPending($candidate), MatchCandidateStatus::Rejected, $by));
    }

    /**
     * Re-read the row under a lock and refuse it when another steward already resolved it.
     *
     * @throws ValidationException
     */
    protected function lockPending(MatchCandidate $candidate): MatchCandidate
    {
        $locked = $this->matchCandidateRepository->findForUpdate($candidate->id);

        if (! $locked instanceof MatchCandidate || ! $locked->isPending()) {
            throw ValidationException::withMessages(['status' => trans('masterdata::app.matching.already-resolved')]);
        }

        return $locked;
    }

    /**
     * Link the row's external id to the record, a refresh when the approval already linked it, and close the row.
     */
    protected function completeApproved(int $candidateId, Record $record, Admin $by, MatchCandidateStatus $status): ?MatchCandidate
    {
        $candidate = $this->matchCandidateRepository->find($candidateId);

        if (! $candidate instanceof MatchCandidate || (int) $candidate->entity_type_id !== (int) $record->entity_type_id) {
            return null;
        }

        try {
            return DB::transaction(function () use ($candidate, $record, $by, $status): MatchCandidate {
                $locked = $this->lockPending($candidate);

                $this->attach($locked, $record);

                return $this->resolve($locked, $status, $by, $record);
            });
        } catch (ValidationException) {
            return null;
        }
    }

    /**
     * Attach the row's external id to the record, reporting a conflict as a validation error.
     *
     * @throws ValidationException
     */
    protected function attach(MatchCandidate $candidate, Record $record): void
    {
        try {
            $this->xrefService->attach($record, $candidate->sourceSystem, (string) $candidate->external_id);
        } catch (XrefConflictException $exception) {
            throw ValidationException::withMessages(['record' => $exception->getMessage()]);
        }
    }

    /**
     * Store the decision on the row.
     */
    protected function resolve(MatchCandidate $candidate, MatchCandidateStatus $status, ?Admin $by, ?Record $record = null): MatchCandidate
    {
        $candidate->forceFill([
            'status'            => $status,
            'matched_record_id' => $record?->id,
            'resolved_by'       => $by?->id,
            'resolved_at'       => now(),
        ])->save();

        return $candidate;
    }
}
