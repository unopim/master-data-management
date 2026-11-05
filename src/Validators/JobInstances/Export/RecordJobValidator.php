<?php

namespace Webkul\MasterDataManagement\Validators\JobInstances\Export;

use Illuminate\Validation\ValidationException;
use Webkul\DataTransfer\Validators\JobInstances\Default\JobValidator;
use Webkul\MasterDataManagement\Enums\EntityTypeStatus;
use Webkul\MasterDataManagement\Enums\ExportFormat;
use Webkul\MasterDataManagement\Enums\RecordAction;
use Webkul\MasterDataManagement\Enums\RecordStatus;
use Webkul\MasterDataManagement\Export\RecordExporter;
use Webkul\MasterDataManagement\Models\EntityType;
use Webkul\MasterDataManagement\Repositories\EntityTypeRepository;
use Webkul\MasterDataManagement\Security\EntityTypePermissions;
use Webkul\User\Models\Admin;

class RecordJobValidator extends JobValidator
{
    public function __construct(
        protected EntityTypeRepository $entityTypeRepository,
        protected EntityTypePermissions $entityTypePermissions,
    ) {
        $this->rules = [
            'filters.'.RecordExporter::FILTER_ENTITY_TYPE            => 'required|string',
            'filters.'.RecordExporter::FILTER_FORMAT                 => 'required|in:'.implode(',', ExportFormat::values()),
            'filters.'.RecordExporter::FILTER_CHANGED_SINCE_LAST_RUN => 'nullable|in:0,1',
        ];

        $this->attributeNames = [
            'filters.'.RecordExporter::FILTER_ENTITY_TYPE            => trans('masterdata::app.exports.records.filters.entity-type'),
            'filters.'.RecordExporter::FILTER_FORMAT                 => trans('data_transfer::app.exporters.fields.file-format'),
            'filters.'.RecordExporter::FILTER_CHANGED_SINCE_LAST_RUN => trans('masterdata::app.exports.records.filters.changed-since-last-run'),
        ];
    }

    /**
     * Validate the filters, then that the entity type is active and viewable by the admin and the statuses exist.
     *
     * @throws ValidationException
     */
    public function validate(array $data, array $options = []): void
    {
        parent::validate($data, $options);

        $filters = (array) ($data['filters'] ?? []);

        $type = $this->entityTypeRepository->findOneByField('code', (string) $filters[RecordExporter::FILTER_ENTITY_TYPE]);

        $admin = auth()->guard('admin')->user();

        if (
            ! $type instanceof EntityType
            || $type->status !== EntityTypeStatus::Active
            || ($admin instanceof Admin && ! $this->entityTypePermissions->allows($admin, (int) $type->id, RecordAction::View))
        ) {
            throw ValidationException::withMessages([
                'filters['.RecordExporter::FILTER_ENTITY_TYPE.']' => [trans('masterdata::app.exports.records.errors.entity-type-invalid')],
            ]);
        }

        $unknown = array_diff(
            RecordExporter::listFilter($filters[RecordExporter::FILTER_STATUSES] ?? null),
            array_column(RecordStatus::cases(), 'value'),
        );

        if ($unknown !== []) {
            throw ValidationException::withMessages([
                'filters['.RecordExporter::FILTER_STATUSES.']' => [trans('masterdata::app.exports.records.errors.status-invalid')],
            ]);
        }
    }
}
