<?php

use Webkul\MasterDataManagement\Enums\ExportFormat;
use Webkul\MasterDataManagement\Enums\RecordStatus;
use Webkul\MasterDataManagement\Export\RecordExporter;
use Webkul\MasterDataManagement\Repositories\RecordRepository;
use Webkul\MasterDataManagement\Validators\JobInstances\Export\RecordJobValidator;

return [
    'masterdata_records' => [
        'module'         => 'mdm',
        'title'          => 'masterdata::app.exports.records.title',
        'exporter'       => RecordExporter::class,
        'source'         => RecordRepository::class,
        'validator'      => RecordJobValidator::class,
        'completed_info' => 'masterdata::app.exports.records.completed-info',
        'filters'        => [
            'fields' => [
                [
                    'name'       => RecordExporter::FILTER_ENTITY_TYPE,
                    'title'      => 'masterdata::app.exports.records.filters.entity-type',
                    'type'       => 'select',
                    'required'   => true,
                    'validation' => 'required',
                    'async'      => true,
                    'list_route' => 'admin.masterdata.exports.filters.entity_types',
                    'track_by'   => 'code',
                    'label_by'   => 'label',
                ], [
                    'name'       => RecordExporter::FILTER_FORMAT,
                    'title'      => 'data_transfer::app.exporters.fields.file-format',
                    'type'       => 'select',
                    'required'   => true,
                    'validation' => 'required',
                    'default'    => ExportFormat::Jsonl->value,
                    'options'    => [
                        [
                            'value' => ExportFormat::Jsonl->value,
                            'label' => 'masterdata::app.exports.records.formats.jsonl',
                        ], [
                            'value' => ExportFormat::Csv->value,
                            'label' => 'masterdata::app.exports.records.formats.csv',
                        ],
                    ],
                ], [
                    'name'     => RecordExporter::FILTER_CHANGED_SINCE_LAST_RUN,
                    'title'    => 'masterdata::app.exports.records.filters.changed-since-last-run',
                    'info'     => 'masterdata::app.exports.records.filters.changed-since-last-run-info',
                    'type'     => 'boolean',
                    'required' => false,
                    'default'  => '0',
                ], [
                    'name'     => RecordExporter::FILTER_STATUSES,
                    'title'    => 'masterdata::app.exports.records.filters.statuses',
                    'info'     => 'masterdata::app.exports.records.filters.statuses-info',
                    'type'     => 'multiselect',
                    'required' => false,
                    'options'  => array_map(fn (RecordStatus $status): array => [
                        'value' => $status->value,
                        'label' => 'masterdata::app.records.statuses.'.$status->value,
                    ], RecordStatus::cases()),
                ],
            ],
        ],
    ],
];
