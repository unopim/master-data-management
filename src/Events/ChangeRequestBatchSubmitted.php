<?php

namespace Webkul\MasterDataManagement\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * One import run or mass action finished holding changes for approval; its change requests carry the batch key in
 * their context under `CONTEXT_KEY`, so their approvers get one notice for the whole batch instead of one per change.
 */
class ChangeRequestBatchSubmitted implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    /**
     * The change request context key holding the batch key.
     */
    public const string CONTEXT_KEY = 'batch';

    /**
     * The batch source of an import run.
     */
    public const string SOURCE_IMPORT = 'import';

    /**
     * The batch source of a mass retire.
     */
    public const string SOURCE_MASS_RETIRE = 'mass-retire';

    /**
     * @param  string  $batch  The key the batch's change requests carry.
     * @param  string  $source  What held the changes, naming the `masterdata::app.approvals.notification.batch.*` text.
     * @param  string|null  $name  The import job code, for an import run.
     */
    public function __construct(
        public readonly string $batch,
        public readonly string $source,
        public readonly ?string $name = null,
    ) {}

    /**
     * The batch key of an import run.
     */
    public static function importKey(int $jobTrackId): string
    {
        return self::SOURCE_IMPORT.':'.$jobTrackId;
    }
}
