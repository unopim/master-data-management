<?php

namespace Webkul\MasterDataManagement\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\MasterDataManagement\Enums\RecordStatus;
use Webkul\MasterDataManagement\Exceptions\StaleRecordException;
use Webkul\MasterDataManagement\Http\Controllers\Concerns\ResolvesAdmin;
use Webkul\MasterDataManagement\Http\Controllers\Concerns\RespondsWithRedirect;
use Webkul\MasterDataManagement\Http\Requests\RecordMergePreviewRequest;
use Webkul\MasterDataManagement\Http\Requests\RecordMergeRequest;
use Webkul\MasterDataManagement\Http\Requests\RecordOptionsRequest;
use Webkul\MasterDataManagement\Models\Field;
use Webkul\MasterDataManagement\Models\Record;
use Webkul\MasterDataManagement\Models\RecordMerge;
use Webkul\MasterDataManagement\Models\SurvivorshipRule;
use Webkul\MasterDataManagement\Repositories\FieldRepository;
use Webkul\MasterDataManagement\Repositories\RecordMergeRepository;
use Webkul\MasterDataManagement\Repositories\RecordRepository;
use Webkul\MasterDataManagement\Services\RecordMerger;
use Webkul\MasterDataManagement\Services\RecordPresenter;
use Webkul\MasterDataManagement\ValueObjects\SourceContext;

/**
 * Merging a duplicate into the record on its page, and undoing a standing merge from the survivor.
 */
class RecordMergeController extends Controller
{
    use ResolvesAdmin, RespondsWithRedirect;

    protected const int OPTIONS_LIMIT = 50;

    public function __construct(
        protected RecordRepository $recordRepository,
        protected RecordMergeRepository $recordMergeRepository,
        protected FieldRepository $fieldRepository,
        protected RecordMerger $recordMerger,
        protected RecordPresenter $recordPresenter,
    ) {}

    /**
     * Show the merge screen; with a chosen duplicate, compare both records field by field.
     */
    public function create(RecordMergePreviewRequest $request, string $uuid): View
    {
        $survivor = $this->mergeableRecord($uuid);

        $merged = null;

        $preview = [];

        if (is_string($with = $request->validated('with')) && $with !== '') {
            $merged = $this->recordRepository->findByUuid($with) ?? abort(404);

            abort_unless((int) $merged->entity_type_id === (int) $survivor->entity_type_id, 404);

            $preview = array_map(fn (array $row): array => [
                'survivor'  => $this->recordPresenter->mergeSide($row['survivor']),
                'merged'    => $this->recordPresenter->mergeSide($row['merged']),
                'suggested' => $row['suggested'],
            ], $this->recordMerger->preview($survivor, $merged));
        }

        return view('masterdata::records.merge', [
            'survivor'    => $survivor,
            'merged'      => $merged,
            'preview'     => $preview,
            'fieldLabels' => $this->fieldLabels($survivor),
        ]);
    }

    /**
     * Search the records the survivor can absorb: same type, draft or active, not itself.
     */
    public function options(RecordOptionsRequest $request, string $uuid): JsonResponse
    {
        $survivor = $this->mergeableRecord($uuid);

        $uuids = array_values(array_map(strval(...), (array) $request->validated('identifiers.values', [])));

        $options = $this->recordRepository
            ->parentCandidates($survivor, [(int) $survivor->entity_type_id], self::OPTIONS_LIMIT, $this->admin(), (string) $request->validated('query', ''), $uuids)
            ->map($this->recordPresenter->option(...))
            ->values();

        return new JsonResponse(['options' => $options, 'lastPage' => 1]);
    }

    /**
     * Merge the chosen duplicate into the record.
     *
     * @throws ValidationException
     */
    public function store(RecordMergeRequest $request, string $uuid): JsonResponse
    {
        $survivor = $this->mergeableRecord($uuid);

        $merged = $this->recordRepository->findByUuid((string) $request->validated('merged'))
            ?? throw ValidationException::withMessages(['merged' => trans('validation.exists', ['attribute' => trans('masterdata::app.records.merge.duplicate')])]);

        $this->authorize('merge', $merged);

        try {
            $result = $this->recordMerger->merge(
                $survivor,
                $merged,
                (array) $request->validated('choices', []),
                (int) $request->validated('survivor_revision'),
                (int) $request->validated('merged_revision'),
                $this->admin(),
                new SourceContext('admin'),
            );
        } catch (StaleRecordException $exception) {
            throw ValidationException::withMessages(['merged' => $exception->getMessage()]);
        }

        $url = route('admin.masterdata.records.edit', $survivor->uuid);

        return $result->isPending()
            ? $this->pendingResponse($url)
            : $this->redirectResponse($url, trans('masterdata::app.records.merge.success', ['code' => $merged->code]));
    }

    /**
     * Undo a standing merge into the record.
     *
     * @throws ValidationException
     */
    public function destroy(string $uuid, int $mergeId): JsonResponse
    {
        $survivor = $this->recordRepository->findByUuid($uuid) ?? abort(404);

        $this->authorize('merge', $survivor);

        $merge = $this->recordMergeRepository->find($mergeId);

        abort_unless($merge instanceof RecordMerge && $merge->isActive() && (int) $merge->survivor_id === (int) $survivor->id, 404);

        $merged = $this->recordRepository->find((int) $merge->merged_id) ?? abort(404);

        try {
            $result = $this->recordMerger->unmerge($merged, $this->admin(), new SourceContext('admin'));
        } catch (StaleRecordException $exception) {
            throw ValidationException::withMessages(['merge' => $exception->getMessage()]);
        }

        $url = route('admin.masterdata.records.edit', $survivor->uuid);

        return $result->isPending()
            ? $this->pendingResponse($url)
            : $this->redirectResponse($url, trans('masterdata::app.records.merge.unmerge-success', ['code' => $merged->code]));
    }

    /**
     * Find a draft or active record the admin may merge into, else 404 / 403 / 422.
     */
    protected function mergeableRecord(string $uuid): Record
    {
        $record = $this->recordRepository->findByUuid($uuid) ?? abort(404);

        abort_unless($record->uuid === $uuid, 404);

        $this->authorize('merge', $record);

        abort_unless(in_array($record->status, [RecordStatus::Draft, RecordStatus::Active], true), 422, trans('masterdata::app.records.not-editable'));

        return $record;
    }

    /**
     * The type's field labels by field code, and the record label's name for a label survivorship rule.
     *
     * @return array<string, string>
     */
    protected function fieldLabels(Record $record): array
    {
        return $this->fieldRepository->activeFor((int) $record->entity_type_id)
            ->mapWithKeys(fn (Field $field): array => [(string) $field->code => is_string($field->label) && $field->label !== '' ? $field->label : (string) $field->code])
            ->all() + [SurvivorshipRule::TARGET_LABEL => trans('masterdata::app.survivorship-rules.targets.label')];
    }
}
