<?php

use Webkul\MasterDataManagement\Http\Controllers\Admin\ImportFilterController;
use Webkul\MasterDataManagement\Importers\CodeList\Importer as CodeListImporter;
use Webkul\MasterDataManagement\Importers\Record\Importer as RecordImporter;
use Webkul\MasterDataManagement\Importers\Xref\Importer as XrefImporter;
use Webkul\MasterDataManagement\Validators\JobInstances\Import\CodeListJobValidator;
use Webkul\MasterDataManagement\Validators\JobInstances\Import\RecordJobValidator;
use Webkul\MasterDataManagement\Validators\JobInstances\Import\XrefJobValidator;

$samples = dirname(__DIR__).'/Resources/samples';

return [
    'masterdata_records' => [
        'module'           => 'mdm',
        'title'            => 'masterdata::app.imports.records.title',
        'importer'         => RecordImporter::class,
        'validator'        => RecordJobValidator::class,
        'has_file_options' => true,
        'sample_path'      => 'data-transfer/samples/import/masterdata/records.csv',
        'sample_source'    => $samples.'/records.csv',
        'completed_info'   => 'masterdata::app.imports.completed-info',
        'filters'          => [
            'fields' => [
                [
                    'name'       => RecordImporter::FILTER_ENTITY_TYPE,
                    'title'      => 'masterdata::app.imports.filters.entity-type',
                    'info'       => 'masterdata::app.imports.filters.entity-type-info',
                    'type'       => 'select',
                    'async'      => true,
                    'list_route' => 'admin.masterdata.imports.filters.entity_types',
                    'track_by'   => 'code',
                    'label_by'   => 'label',
                    'required'   => true,
                    'validation' => 'required',
                ], [
                    'name'       => RecordImporter::FILTER_SOURCE_SYSTEM,
                    'title'      => 'masterdata::app.imports.filters.source-system',
                    'info'       => 'masterdata::app.imports.filters.source-system-info',
                    'type'       => 'select',
                    'async'      => true,
                    'list_route' => 'admin.masterdata.imports.filters.source_systems',
                    'track_by'   => 'code',
                    'label_by'   => 'label',
                    'required'   => false,
                ], [
                    'name'       => RecordImporter::FILTER_MAPPING,
                    'title'      => 'masterdata::app.imports.filters.mapping',
                    'info'       => 'masterdata::app.imports.filters.mapping-info',
                    'type'       => 'textarea',
                    'required'   => false,
                    'full_width' => true,
                ],
            ],
        ],
    ],

    'masterdata_xrefs' => [
        'module'           => 'mdm',
        'title'            => 'masterdata::app.imports.xrefs.title',
        'importer'         => XrefImporter::class,
        'validator'        => XrefJobValidator::class,
        'has_file_options' => true,
        'sample_path'      => 'data-transfer/samples/import/masterdata/xrefs.csv',
        'sample_source'    => $samples.'/xrefs.csv',
        'completed_info'   => 'masterdata::app.imports.completed-info',
        'filters'          => [
            'fields' => [
                [
                    'name'         => XrefImporter::FILTER_ENTITY_TYPE,
                    'title'        => 'masterdata::app.imports.filters.entity-type',
                    'info'         => 'masterdata::app.imports.filters.entity-type-info',
                    'type'         => 'select',
                    'async'        => true,
                    'list_route'   => 'admin.masterdata.imports.filters.entity_types',
                    'query_params' => ['access' => ImportFilterController::ACCESS_EDIT],
                    'track_by'     => 'code',
                    'label_by'     => 'label',
                    'required'     => true,
                    'validation'   => 'required',
                ], [
                    'name'       => XrefImporter::FILTER_SOURCE_SYSTEM,
                    'title'      => 'masterdata::app.imports.filters.source-system',
                    'info'       => 'masterdata::app.imports.filters.source-system-info',
                    'type'       => 'select',
                    'async'      => true,
                    'list_route' => 'admin.masterdata.imports.filters.source_systems',
                    'track_by'   => 'code',
                    'label_by'   => 'label',
                    'required'   => true,
                    'validation' => 'required',
                ],
            ],
        ],
    ],

    'masterdata_code_lists' => [
        'module'           => 'mdm',
        'title'            => 'masterdata::app.imports.code-lists.title',
        'importer'         => CodeListImporter::class,
        'validator'        => CodeListJobValidator::class,
        'has_file_options' => true,
        'sample_path'      => 'data-transfer/samples/import/masterdata/code-lists.csv',
        'sample_source'    => $samples.'/code-lists.csv',
        'completed_info'   => 'masterdata::app.imports.code-lists.completed-info',
        'filters'          => [
            'fields' => [
                [
                    'name'       => CodeListImporter::FILTER_CODE_LIST,
                    'title'      => 'masterdata::app.imports.filters.code-list',
                    'info'       => 'masterdata::app.imports.filters.code-list-info',
                    'type'       => 'select',
                    'async'      => true,
                    'list_route' => 'admin.masterdata.imports.filters.code_lists',
                    'track_by'   => 'code',
                    'label_by'   => 'label',
                    'required'   => true,
                    'validation' => 'required',
                ],
            ],
        ],
    ],
];
