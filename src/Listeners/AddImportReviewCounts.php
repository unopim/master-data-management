<?php

namespace Webkul\MasterDataManagement\Listeners;

use Illuminate\Support\Facades\DB;
use Webkul\DataTransfer\Contracts\JobTrack;
use Webkul\DataTransfer\Repositories\JobTrackBatchRepository;
use Webkul\DataTransfer\Repositories\JobTrackRepository;
use Webkul\MasterDataManagement\Events\ChangeRequestBatchSubmitted;
use Webkul\MasterDataManagement\Importers\MasterDataImporter;

/**
 * Completes the summary of a finished master data import with the changes held for approval and the rows refused
 * while importing, summed over its batches; the core summary only adds up created, updated and deleted items. An
 * import that held changes then announces them to their approvers as one batch.
 */
class AddImportReviewCounts
{
    public function __construct(
        protected JobTrackBatchRepository $jobTrackBatchRepository,
        protected JobTrackRepository $jobTrackRepository,
    ) {}

    /**
     * Add the `held` and `skipped` totals to the job's summary and announce the held changes.
     */
    public function handle(JobTrack $import): void
    {
        if (! self::isMasterDataImport($import->jobInstance?->entity_type)) {
            return;
        }

        $grammar = DB::rawQueryGrammar();

        $totals = $this->jobTrackBatchRepository
            ->select(
                DB::raw("SUM(CAST({$grammar->jsonExtract('summary', 'held')} AS DECIMAL)) AS held"),
                DB::raw("SUM(CAST({$grammar->jsonExtract('summary', 'skipped')} AS DECIMAL)) AS skipped"),
            )
            ->where('job_track_id', $import->id)
            ->first();

        $held = (int) ($totals?->held ?? 0);

        $this->jobTrackRepository->update([
            'summary' => array_merge((array) $import->summary, [
                'held'    => $held,
                'skipped' => (int) ($totals?->skipped ?? 0),
            ]),
        ], $import->id);

        if ($held > 0) {
            event(new ChangeRequestBatchSubmitted(
                ChangeRequestBatchSubmitted::importKey((int) $import->id),
                ChangeRequestBatchSubmitted::SOURCE_IMPORT,
                $import->jobInstance?->code,
            ));
        }
    }

    /**
     * Whether the job type is imported by a master data importer.
     */
    public static function isMasterDataImport(mixed $entityType): bool
    {
        $importer = is_string($entityType) && $entityType !== '' ? config('importers.'.$entityType.'.importer') : null;

        return is_string($importer) && is_a($importer, MasterDataImporter::class, true);
    }
}
