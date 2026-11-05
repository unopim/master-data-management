<?php

namespace Webkul\MasterDataManagement\Demo\Services;

use Webkul\MasterDataManagement\Matching\MatchInbox;
use Webkul\MasterDataManagement\Models\ChangeRequest;
use Webkul\MasterDataManagement\Models\EntityType;
use Webkul\MasterDataManagement\Models\Field;
use Webkul\MasterDataManagement\Models\Record;
use Webkul\MasterDataManagement\Models\SourceSystem;
use Webkul\MasterDataManagement\Quality\QualityScanner;
use Webkul\MasterDataManagement\Repositories\ChangeRequestRepository;
use Webkul\MasterDataManagement\Repositories\FieldRepository;
use Webkul\MasterDataManagement\Repositories\RecordRepository;
use Webkul\MasterDataManagement\Services\ChangeRequestService;
use Webkul\MasterDataManagement\Services\FieldService;
use Webkul\MasterDataManagement\Services\RecordWriter;
use Webkul\MasterDataManagement\Services\SchemaPublisher;
use Webkul\MasterDataManagement\Services\XrefService;
use Webkul\MasterDataManagement\ValueObjects\RecordWriteResult;
use Webkul\MasterDataManagement\ValueObjects\SourceContext;
use Webkul\User\Models\Admin;

/**
 * Seeds the governance side of the demo: the steward's changes, a few decided by the data owner and the rest
 * pending, an inbox of unmatched source rows and a first quality scan.
 */
class StewardshipInstaller
{
    public const STEWARD_EMAIL = 'site.steward@example.com';

    public function __construct(
        protected FieldRepository $fieldRepository,
        protected FieldService $fieldService,
        protected SchemaPublisher $schemaPublisher,
        protected RecordRepository $recordRepository,
        protected RecordWriter $recordWriter,
        protected ChangeRequestService $changeRequestService,
        protected ChangeRequestRepository $changeRequestRepository,
        protected MatchInbox $matchInbox,
        protected QualityScanner $qualityScanner,
        protected XrefService $xrefService,
    ) {}

    /**
     * @param  array<string, mixed>  $dataset
     * @param  array<string, EntityType>  $types
     * @param  array<string, SourceSystem>  $systems
     * @return array{change_requests: int, match_candidates: int, scanned: int}
     */
    public function install(array $dataset, array $types, array $systems, Admin $owner, Admin $steward): array
    {
        $this->requireCriticality($types['equipment']);

        $changeRequests = ! $this->hasRequestsBy($steward)
            ? $this->seedChangeRequests($types, $steward, $owner)
            : 0;

        $candidates = 0;

        foreach ($dataset['match_inbox'] as $row) {
            if ($this->xrefService->resolve($systems[$row['system']], (int) $types[$row['type']]->id, $row['external_id']) instanceof Record) {
                continue;
            }

            $this->matchInbox->submit($systems[$row['system']], $types[$row['type']], $row['external_id'], [
                'code'   => $row['code'],
                'label'  => $row['label'],
                'values' => ['common' => ['name' => $row['label']]],
            ], $steward);

            $candidates++;
        }

        $scanned = 0;

        foreach ($types as $type) {
            $scanned += $this->qualityScanner->scan((int) $type->id);
        }

        return [
            'change_requests'  => $changeRequests,
            'match_candidates' => $candidates,
            'scanned'          => $scanned,
        ];
    }

    protected function hasRequestsBy(Admin $steward): bool
    {
        return $this->changeRequestRepository->getModel()->newQuery()
            ->where('requested_by', $steward->id)
            ->exists();
    }

    /**
     * Make criticality required once assets are loaded, so the assets loaded without one surface as quality issues.
     */
    protected function requireCriticality(EntityType $equipment): void
    {
        $field = $this->fieldRepository->findByCode((int) $equipment->id, 'criticality');

        if (! $field instanceof Field || $field->is_required) {
            return;
        }

        $this->fieldService->update($field, ['is_required' => true]);

        $this->schemaPublisher->publish($equipment->refresh());
    }

    /**
     * Raise the steward's changes; the owner approves one and rejects one, the rest wait in the approvals inbox.
     *
     * @param  array<string, EntityType>  $types
     */
    protected function seedChangeRequests(array $types, Admin $steward, Admin $owner): int
    {
        $source = new SourceContext('admin');

        $approved = $this->stewardUpdate($types['equipment'], 'LKV-L01-A1-E2', ['criticality' => 'B'], $steward, $source);

        if ($approved instanceof ChangeRequest) {
            $this->changeRequestService->approve($approved, $owner, 'Checked against the maintenance strategy: production critical.');
        }

        $rejected = $this->stewardUpdate($types['equipment'], 'RHN-L01-A2-E1', ['operating_status' => 'decommissioned'], $steward, $source);

        if ($rejected instanceof ChangeRequest) {
            $this->changeRequestService->reject($rejected, $owner, 'Mixer is still scheduled in MES; raise again after the line review.');
        }

        $this->stewardUpdate($types['site'], 'LKV', ['site_manager_email' => 'lakeview.operations.lead@example.com'], $steward, $source);

        $this->stewardUpdate($types['line'], 'RHN-L02', ['rated_speed_ppm' => 300], $steward, $source);

        $retiring = $this->recordRepository->findByCode((int) $types['equipment']->id, 'PNE-L03-A2-E2');

        if ($retiring instanceof Record) {
            $this->recordWriter->submitRetire($retiring, (int) $retiring->revision, $steward, $source);
        }

        $packing = $this->recordRepository->findByCode((int) $types['department']->id, 'LKV-PCK');

        $this->recordWriter->submitCreate($types['line'], [
            'code'   => 'LKV-L04',
            'label'  => 'Lakeview Line 4 Packing',
            'parent' => $packing?->uuid,
            'values' => ['common' => [
                'name'             => 'Lakeview Line 4 Packing',
                'line_type'        => 'packing',
                'rated_speed_ppm'  => 80,
                'shifts_per_day'   => 2,
                'operating_status' => 'idle',
            ]],
        ], $steward, $source);

        return 6;
    }

    /**
     * Submit the steward's edit and return the change request it opened, if the gate held it.
     *
     * @param  array<string, mixed>  $values
     */
    protected function stewardUpdate(EntityType $type, string $code, array $values, Admin $steward, SourceContext $source): ?ChangeRequest
    {
        $record = $this->recordRepository->findByCode((int) $type->id, $code);

        if (! $record instanceof Record) {
            return null;
        }

        $result = $this->recordWriter->submitUpdate($record, ['values' => ['common' => $values]], (int) $record->revision, $steward, $source);

        return $this->heldRequest($result);
    }

    protected function heldRequest(RecordWriteResult $result): ?ChangeRequest
    {
        if (! $result->isPending() || ! is_numeric($result->gate->reference)) {
            return null;
        }

        return $this->changeRequestRepository->find((int) $result->gate->reference);
    }
}
