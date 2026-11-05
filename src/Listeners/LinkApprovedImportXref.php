<?php

namespace Webkul\MasterDataManagement\Listeners;

use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Webkul\MasterDataManagement\Enums\ChangeOperation;
use Webkul\MasterDataManagement\Enums\ChangeRequestStatus;
use Webkul\MasterDataManagement\Events\ChangeRequestDecided;
use Webkul\MasterDataManagement\Exceptions\XrefConflictException;
use Webkul\MasterDataManagement\Importers\Record\Importer;
use Webkul\MasterDataManagement\Models\Record;
use Webkul\MasterDataManagement\Models\SourceSystem;
use Webkul\MasterDataManagement\Repositories\RecordRepository;
use Webkul\MasterDataManagement\Repositories\SourceSystemRepository;
use Webkul\MasterDataManagement\Services\XrefWriter;
use Webkul\MasterDataManagement\ValueObjects\SourceContext;
use Webkul\User\Models\Admin;

/**
 * Completes an imported create that waited for approval: the approved record gets the external id its import row
 * carried. An id another record claimed meanwhile is not linked and is logged instead.
 */
class LinkApprovedImportXref
{
    public function __construct(
        protected RecordRepository $recordRepository,
        protected SourceSystemRepository $sourceSystemRepository,
        protected XrefWriter $xrefWriter,
    ) {}

    /**
     * Link the external id of an approved imported create, as the approver.
     */
    public function handle(ChangeRequestDecided $event): void
    {
        $changeRequest = $event->changeRequest;

        $link = $changeRequest->context[Importer::CONTEXT_XREF] ?? null;

        if (
            $changeRequest->status !== ChangeRequestStatus::Approved
            || $changeRequest->operation !== ChangeOperation::Create
            || $changeRequest->record_id === null
            || ! is_array($link)
        ) {
            return;
        }

        $record = $this->recordRepository->find($changeRequest->record_id);

        $system = $this->sourceSystemRepository->find((int) ($link[XrefWriter::INPUT_SYSTEM] ?? 0));

        $decider = $changeRequest->decider;

        if (! $record instanceof Record || ! $system instanceof SourceSystem || ! $decider instanceof Admin) {
            return;
        }

        try {
            $this->xrefWriter->link($record, $system, (string) ($link[XrefWriter::INPUT_EXTERNAL_ID] ?? ''), $decider, new SourceContext('approval'));
        } catch (XrefConflictException|ValidationException $exception) {
            Log::warning($exception->getMessage(), [
                'change_request_id' => (int) $changeRequest->id,
                'record_id'         => (int) $record->id,
            ]);
        }
    }
}
