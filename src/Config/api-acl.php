<?php

return [
    [
        'key'    => 'api.masterdata',
        'name'   => 'masterdata::app.api.acl.master-data',
        'module' => 'mdm',
        'route'  => 'admin.api.masterdata.records.show',
        'sort'   => 20,
    ], [
        'key'   => 'api.masterdata.records',
        'name'  => 'masterdata::app.api.acl.records',
        'route' => 'admin.api.masterdata.records.show',
        'sort'  => 1,
    ], [
        'key'   => 'api.masterdata.records',
        'name'  => 'masterdata::app.api.acl.records',
        'route' => 'admin.api.masterdata.xref.resolve',
        'sort'  => 1,
    ], [
        'key'   => 'api.masterdata.records',
        'name'  => 'masterdata::app.api.acl.records',
        'route' => 'admin.api.masterdata.records.index',
        'sort'  => 1,
    ], [
        'key'   => 'api.masterdata.records.create',
        'name'  => 'masterdata::app.api.acl.create',
        'route' => 'admin.api.masterdata.records.store',
        'sort'  => 1,
    ], [
        'key'   => 'api.masterdata.records.edit',
        'name'  => 'masterdata::app.api.acl.edit',
        'route' => 'admin.api.masterdata.records.update',
        'sort'  => 2,
    ], [
        'key'   => 'api.masterdata.records.delete',
        'name'  => 'masterdata::app.api.acl.delete',
        'route' => 'admin.api.masterdata.records.delete',
        'sort'  => 3,
    ], [
        'key'   => 'api.masterdata.entity_types',
        'name'  => 'masterdata::app.api.acl.entity-types',
        'route' => 'admin.api.masterdata.entity_types.index',
        'sort'  => 3,
    ], [
        'key'   => 'api.masterdata.entity_types',
        'name'  => 'masterdata::app.api.acl.entity-types',
        'route' => 'admin.api.masterdata.entity_types.get',
        'sort'  => 3,
    ], [
        'key'   => 'api.masterdata.code_lists',
        'name'  => 'masterdata::app.api.acl.code-lists',
        'route' => 'admin.api.masterdata.code_lists.index',
        'sort'  => 4,
    ], [
        'key'   => 'api.masterdata.code_lists',
        'name'  => 'masterdata::app.api.acl.code-lists',
        'route' => 'admin.api.masterdata.code_lists.get',
        'sort'  => 4,
    ], [
        'key'   => 'api.masterdata.records.upsert',
        'name'  => 'masterdata::app.api.acl.upsert',
        'route' => 'admin.api.masterdata.records.upsert',
        'sort'  => 10,
    ], [
        'key'   => 'api.masterdata.matching',
        'name'  => 'masterdata::app.api.acl.matching',
        'route' => 'admin.api.masterdata.match_candidates.store',
        'sort'  => 2,
    ],
];
