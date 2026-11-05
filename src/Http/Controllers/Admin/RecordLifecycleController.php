<?php

namespace Webkul\MasterDataManagement\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\MasterDataManagement\Exceptions\StaleRecordException;
use Webkul\MasterDataManagement\Http\Controllers\Concerns\ResolvesAdmin;
use Webkul\MasterDataManagement\Http\Controllers\Concerns\RespondsWithRedirect;
use Webkul\MasterDataManagement\Http\Requests\RecordEraseRequest;
use Webkul\MasterDataManagement\Http\Requests\RecordMassRetireRequest;
use Webkul\MasterDataManagement\Models\EntityType;
use Webkul\MasterDataManagement\Repositories\RecordRepository;
use Webkul\MasterDataManagement\Security\RecordPolicy;
use Webkul\MasterDataManagement\Services\RecordEraser;
use Webkul\MasterDataManagement\Services\RecordWriter;
use Webkul\MasterDataManagement\ValueObjects\SourceContext;

class RecordLifecycleController extends Controller
{
    use ResolvesAdmin, RespondsWithRedirect;

    public function __construct(
        protected RecordRepository $recordRepository,
        protected RecordWriter $recordWriter,
        protected RecordEraser $recordEraser,
    ) {}

    /**
     * Permanently erase a retired record at the revision the editor read.
     *
     * @throws ValidationException
     */
    public function erase(RecordEraseRequest $request, string $uuid): JsonResponse
    {
        $record = $this->recordRepository->findByUuid($uuid) ?? abort(404);

        $this->authorize('erase', $record);

        try {
            $record = $this->recordEraser->erase($record, (int) $request->validated('revision'), $this->admin());
        } catch (StaleRecordException $exception) {
            return new JsonResponse(['message' => $exception->getMessage()], JsonResponse::HTTP_CONFLICT);
        }

        return $this->redirectResponse(route('admin.masterdata.records.index', $record->entityType->code), trans('masterdata::app.records.erase.success'));
    }

    /**
     * Retire the selected records of the entity type at their current revision, each through the change gate.
     *
     * Records outside the type or the admin's row scope are ignored; records that are no longer live or change
     * while the selection is processed are skipped and counted.
     */
    public function massRetire(RecordMassRetireRequest $request, EntityType $entityType): JsonResponse
    {
        Gate::authorize('delete', RecordPolicy::subjectForEntityType((int) $entityType->id));

        $ids = $request->recordIds();
        $records = $this->recordRepository->findManyForEntityType((int) $entityType->id, $ids, $this->admin());

        $counts = ['retired' => 0, 'pending' => 0, 'skipped' => count($ids) - $records->count()];

        foreach ($records as $record) {
            if (Gate::denies('delete', $record)) {
                $counts['skipped']++;

                continue;
            }

            try {
                $result = $this->recordWriter->submitRetire($record, (int) $record->revision, $this->admin(), new SourceContext('admin'));
            } catch (ValidationException|StaleRecordException) {
                $counts['skipped']++;

                continue;
            }

            $counts[$result->isPending() ? 'pending' : 'retired']++;
        }

        $done = $counts['retired'] + $counts['pending'] > 0;

        return new JsonResponse(
            ['message' => $this->massRetireMessage($counts)] + $counts,
            $done ? JsonResponse::HTTP_OK : JsonResponse::HTTP_BAD_REQUEST,
        );
    }

    /**
     * @param  array{retired: int, pending: int, skipped: int}  $counts
     */
    protected function massRetireMessage(array $counts): string
    {
        if ($counts['retired'] + $counts['pending'] === 0) {
            return trans('masterdata::app.records.mass-retire.none');
        }

        return collect(['success' => $counts['retired'], 'pending' => $counts['pending'], 'skipped' => $counts['skipped']])
            ->filter()
            ->map(fn (int $count, string $key): string => trans('masterdata::app.records.mass-retire.'.$key, ['count' => $count]))
            ->implode(' ');
    }
}
