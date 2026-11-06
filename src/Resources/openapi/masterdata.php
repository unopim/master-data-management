<?php

use Webkul\MasterDataManagement\Enums\ApiErrorCode;
use Webkul\MasterDataManagement\Enums\CodeListVersionStatus;
use Webkul\MasterDataManagement\Enums\EntityTypeStatus;
use Webkul\MasterDataManagement\Enums\MatchCandidateStatus;
use Webkul\MasterDataManagement\Enums\RecordStatus;
use Webkul\MasterDataManagement\Http\Requests\Api\ShowRecordRequest;

$ref = fn (string $name): array => ['$ref' => '#/components/schemas/'.$name];

$param = fn (string $name): array => ['$ref' => '#/components/parameters/'.$name];

$response = fn (string $name): array => ['$ref' => '#/components/responses/'.$name];

$json = fn (array $schema, string $description): array => [
    'description' => $description,
    'content'     => ['application/json' => ['schema' => $schema]],
];

$data = fn (array $schema): array => [
    'type'       => 'object',
    'required'   => ['data'],
    'properties' => ['data' => $schema],
];

$page = fn (string $item): array => [
    'type'       => 'object',
    'required'   => ['data', 'meta'],
    'properties' => [
        'data' => ['type' => 'array', 'items' => $ref($item)],
        'meta' => [
            'type'       => 'object',
            'properties' => [
                'next_cursor' => ['type' => ['string', 'null']],
                'limit'       => ['type' => 'integer'],
            ],
        ],
    ],
];

$translations = ['type' => 'object', 'additionalProperties' => ['type' => 'string'], 'description' => 'Values keyed by locale code.'];

$etag = ['ETag' => ['description' => 'The record revision, sent back in `If-Match` to update or retire it.', 'schema' => ['type' => 'string']]];

$recordWrite = [
    'required' => true,
    'content'  => ['application/json' => ['schema' => $ref('RecordInput')]],
];

return [
    'openapi' => '3.1.0',
    'info'    => [
        'title'       => 'UnoPim Master Data API',
        'version'     => '1.0.0',
        'description' => 'Read and write master data records, entity type schemas, code lists, the cross-reference crosswalk and the match inbox. Every operation needs an API key with the matching master data permission; writes pass the change gate of their entity type, so a held write answers 202 with the change request id.',
    ],
    'security' => [['bearerAuth' => []], ['oauth2' => []]],
    'tags'     => [
        ['name' => 'Records', 'description' => 'Golden records of an entity type.'],
        ['name' => 'Crosswalk', 'description' => 'Source system ids of golden records.'],
        ['name' => 'Schema', 'description' => 'Entity types with their fields and rules.'],
        ['name' => 'Code lists', 'description' => 'Reference code lists and their versions.'],
        ['name' => 'Matching', 'description' => 'Source records waiting to be matched.'],
        ['name' => 'Documentation', 'description' => 'This document.'],
    ],
    'paths' => [
        '/v1/rest/masterdata/openapi.json' => [
            'get' => [
                'tags'        => ['Documentation'],
                'operationId' => 'getMasterDataOpenApi',
                'summary'     => 'The OpenAPI document of the master data API.',
                'responses'   => [
                    '200' => $json(['type' => 'object'], 'The OpenAPI 3.1 document.'),
                    '401' => $response('Unauthenticated'),
                    '403' => $response('Forbidden'),
                ],
            ],
        ],
        '/v1/rest/masterdata/xref/resolve' => [
            'get' => [
                'tags'        => ['Crosswalk'],
                'operationId' => 'resolveXref',
                'summary'     => 'Resolve a source system id to its golden record and all of its system ids.',
                'parameters'  => [
                    ['name' => 'system', 'in' => 'query', 'required' => true, 'schema' => ['type' => 'string', 'maxLength' => 255], 'description' => 'Source system code.'],
                    ['name' => 'type', 'in' => 'query', 'required' => true, 'schema' => ['type' => 'string', 'maxLength' => 255], 'description' => 'Entity type code.'],
                    ['name' => 'id', 'in' => 'query', 'required' => true, 'schema' => ['type' => 'string', 'maxLength' => 255], 'description' => 'The id in the source system.'],
                ],
                'responses' => [
                    '200' => $json($data($ref('Xref')), 'The golden record and its ids per source system.'),
                    '401' => $response('Unauthenticated'),
                    '403' => $response('Forbidden'),
                    '404' => $response('NotFound'),
                    '422' => $response('ValidationFailed'),
                ],
            ],
        ],
        '/v1/rest/masterdata/entity-types' => [
            'get' => [
                'tags'        => ['Schema'],
                'operationId' => 'listEntityTypes',
                'summary'     => 'List the entity types the API key may read, with their active fields and rules.',
                'responses'   => [
                    '200' => $json($data(['type' => 'array', 'items' => $ref('EntityType')]), 'The entity types.'),
                    '401' => $response('Unauthenticated'),
                    '403' => $response('Forbidden'),
                ],
            ],
        ],
        '/v1/rest/masterdata/entity-types/{typeCode}' => [
            'get' => [
                'tags'        => ['Schema'],
                'operationId' => 'getEntityType',
                'summary'     => 'Show an entity type with its active fields and rules.',
                'parameters'  => [
                    ['name' => 'typeCode', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'string'], 'description' => 'Entity type code.'],
                ],
                'responses' => [
                    '200' => $json($data($ref('EntityType')), 'The entity type.'),
                    '401' => $response('Unauthenticated'),
                    '403' => $response('Forbidden'),
                    '404' => $response('NotFound'),
                ],
            ],
        ],
        '/v1/rest/masterdata/code-lists' => [
            'get' => [
                'tags'        => ['Code lists'],
                'operationId' => 'listCodeLists',
                'summary'     => 'List the code lists by cursor.',
                'parameters'  => [$param('Limit'), $param('Cursor')],
                'responses'   => [
                    '200' => $json($page('CodeList'), 'A page of code lists.'),
                    '401' => $response('Unauthenticated'),
                    '403' => $response('Forbidden'),
                    '422' => $response('ValidationFailed'),
                ],
            ],
        ],
        '/v1/rest/masterdata/code-lists/{listCode}' => [
            'get' => [
                'tags'        => ['Code lists'],
                'operationId' => 'getCodeList',
                'summary'     => 'Show a code list with the codes of the version in effect today.',
                'parameters'  => [
                    ['name' => 'listCode', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'string'], 'description' => 'Code list code.'],
                    ['name' => 'include', 'in' => 'query', 'required' => false, 'schema' => ['type' => 'string', 'enum' => ['versions']], 'description' => 'Add every version of the list.'],
                ],
                'responses' => [
                    '200' => $json($data($ref('CodeList')), 'The code list.'),
                    '401' => $response('Unauthenticated'),
                    '403' => $response('Forbidden'),
                    '404' => $response('NotFound'),
                    '422' => $response('ValidationFailed'),
                ],
            ],
        ],
        '/v1/rest/masterdata/{type}/records' => [
            'parameters' => [$param('Type')],
            'get'        => [
                'tags'        => ['Records'],
                'operationId' => 'listRecords',
                'summary'     => 'List the records of an entity type by cursor, ordered by id.',
                'parameters'  => [
                    $param('Limit'),
                    $param('Cursor'),
                    ['name' => 'filter[code]', 'in' => 'query', 'required' => false, 'schema' => ['type' => 'string', 'maxLength' => 255], 'description' => 'Exact record code.'],
                    ['name' => 'filter[status]', 'in' => 'query', 'required' => false, 'schema' => ['type' => 'string', 'enum' => array_column(RecordStatus::cases(), 'value')]],
                    ['name' => 'filter[updated_since]', 'in' => 'query', 'required' => false, 'schema' => ['type' => 'string', 'format' => 'date-time']],
                    [
                        'name'        => 'filter',
                        'in'          => 'query',
                        'required'    => false,
                        'style'       => 'deepObject',
                        'explode'     => true,
                        'schema'      => ['type' => 'object', 'additionalProperties' => ['type' => 'string', 'maxLength' => 255]],
                        'description' => 'Exact value of a searchable field, as `filter[field_code]=value`.',
                    ],
                    $param('RecordInclude'),
                ],
                'responses' => [
                    '200' => $json($page('Record'), 'A page of records.'),
                    '401' => $response('Unauthenticated'),
                    '403' => $response('Forbidden'),
                    '404' => $response('NotFound'),
                    '422' => $response('ValidationFailed'),
                ],
            ],
            'post' => [
                'tags'        => ['Records'],
                'operationId' => 'createRecord',
                'summary'     => 'Create a record.',
                'parameters'  => [$param('IdempotencyKey')],
                'requestBody' => $recordWrite,
                'responses'   => [
                    '201' => $json($data($ref('Record')), 'The created record.') + ['headers' => $etag + ['Location' => ['schema' => ['type' => 'string', 'format' => 'uri']]]],
                    '202' => $response('PendingApproval'),
                    '401' => $response('Unauthenticated'),
                    '403' => $response('Forbidden'),
                    '404' => $response('NotFound'),
                    '409' => $response('Conflict'),
                    '422' => $response('ValidationFailed'),
                ],
            ],
        ],
        '/v1/rest/masterdata/{type}/records/{uuid}' => [
            'parameters' => [$param('Type'), $param('Uuid')],
            'get'        => [
                'tags'        => ['Records'],
                'operationId' => 'getRecord',
                'summary'     => 'Show a record; a merged uuid redirects permanently to the surviving record.',
                'parameters'  => [$param('RecordInclude')],
                'responses'   => [
                    '200' => $json($data($ref('Record')), 'The record.') + ['headers' => $etag],
                    '301' => ['description' => 'The record was merged; `Location` names the surviving record.'],
                    '401' => $response('Unauthenticated'),
                    '403' => $response('Forbidden'),
                    '404' => $response('NotFound'),
                ],
            ],
            'put' => [
                'tags'        => ['Records'],
                'operationId' => 'updateRecord',
                'summary'     => 'Partially update a record at the revision named by `If-Match`.',
                'parameters'  => [$param('IfMatch'), $param('IdempotencyKey')],
                'requestBody' => $recordWrite,
                'responses'   => [
                    '200' => $json($data($ref('Record')), 'The updated record.') + ['headers' => $etag],
                    '202' => $response('PendingApproval'),
                    '401' => $response('Unauthenticated'),
                    '403' => $response('Forbidden'),
                    '404' => $response('NotFound'),
                    '409' => $response('Conflict'),
                    '412' => $response('Conflict'),
                    '422' => $response('ValidationFailed'),
                    '428' => $response('Conflict'),
                ],
            ],
            'delete' => [
                'tags'        => ['Records'],
                'operationId' => 'retireRecord',
                'summary'     => 'Retire a record at the revision named by `If-Match`.',
                'parameters'  => [$param('IfMatch'), $param('IdempotencyKey')],
                'responses'   => [
                    '200' => $json($data($ref('Record')), 'The retired record.') + ['headers' => $etag],
                    '202' => $response('PendingApproval'),
                    '401' => $response('Unauthenticated'),
                    '403' => $response('Forbidden'),
                    '404' => $response('NotFound'),
                    '409' => $response('Conflict'),
                    '412' => $response('Conflict'),
                    '428' => $response('Conflict'),
                ],
            ],
        ],
        '/v1/rest/masterdata/{type}/records/by-xref/{system}/{externalId}' => [
            'parameters' => [
                $param('Type'),
                ['name' => 'system', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'string'], 'description' => 'Source system code.'],
                ['name' => 'externalId', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'string'], 'description' => 'The id in the source system; may contain slashes.'],
            ],
            'put' => [
                'tags'        => ['Records', 'Crosswalk'],
                'operationId' => 'upsertRecordByXref',
                'summary'     => 'Update the record a source system id resolves to, else create one and link the id.',
                'parameters'  => [
                    ['name' => 'If-Match', 'in' => 'header', 'required' => false, 'schema' => ['type' => 'string'], 'description' => 'Optional revision guarding the update.'],
                ],
                'requestBody' => $recordWrite,
                'responses'   => [
                    '200' => $json($data($ref('Record')), 'The updated record.') + ['headers' => $etag],
                    '201' => $json($data($ref('Record')), 'The created and linked record.') + ['headers' => $etag],
                    '202' => $response('PendingApproval'),
                    '401' => $response('Unauthenticated'),
                    '403' => $response('Forbidden'),
                    '404' => $response('NotFound'),
                    '409' => $response('Conflict'),
                    '412' => $response('Conflict'),
                    '422' => $response('ValidationFailed'),
                ],
            ],
        ],
        '/v1/rest/masterdata/match-candidates' => [
            'post' => [
                'tags'        => ['Matching'],
                'operationId' => 'submitMatchCandidate',
                'summary'     => 'Submit a source record to the match inbox; reopening the row held for the same id answers 200.',
                'requestBody' => [
                    'required' => true,
                    'content'  => ['application/json' => ['schema' => $ref('MatchCandidateInput')]],
                ],
                'responses' => [
                    '200' => $json($data($ref('MatchCandidate')), 'The reopened inbox row.'),
                    '201' => $json($data($ref('MatchCandidate')), 'The new inbox row.'),
                    '401' => $response('Unauthenticated'),
                    '403' => $response('Forbidden'),
                    '404' => $response('NotFound'),
                    '409' => $response('Conflict'),
                    '422' => $response('ValidationFailed'),
                ],
            ],
        ],
    ],
    'components' => [
        'securitySchemes' => [
            'bearerAuth' => [
                'type'        => 'http',
                'scheme'      => 'bearer',
                'description' => 'An OAuth access token of an API integration.',
            ],
            'oauth2' => [
                'type'  => 'oauth2',
                'flows' => [
                    'password' => [
                        'tokenUrl' => '/oauth/token',
                        'scopes'   => (object) [],
                    ],
                ],
            ],
        ],
        'parameters' => [
            'Type'           => ['name' => 'type', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'string'], 'description' => 'Entity type code.'],
            'Uuid'           => ['name' => 'uuid', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'string', 'format' => 'uuid'], 'description' => 'Record uuid.'],
            'Limit'          => ['name' => 'limit', 'in' => 'query', 'required' => false, 'schema' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 100]],
            'Cursor'         => ['name' => 'cursor', 'in' => 'query', 'required' => false, 'schema' => ['type' => 'string', 'maxLength' => 2048], 'description' => 'The `meta.next_cursor` of the previous page.'],
            'RecordInclude'  => ['name' => 'include', 'in' => 'query', 'required' => false, 'schema' => ['type' => 'string'], 'description' => 'Comma separated: '.implode(', ', ShowRecordRequest::INCLUDES).'.'],
            'IfMatch'        => ['name' => 'If-Match', 'in' => 'header', 'required' => true, 'schema' => ['type' => 'string'], 'description' => 'The record revision from its `ETag`.'],
            'IdempotencyKey' => ['name' => 'Idempotency-Key', 'in' => 'header', 'required' => false, 'schema' => ['type' => 'string', 'maxLength' => 255], 'description' => 'A repeated key replays the stored response instead of writing again.'],
        ],
        'responses' => [
            'Unauthenticated'  => $json($ref('Error'), 'The access token is missing or invalid.'),
            'Forbidden'        => $json($ref('Error'), 'The API key lacks the permission or the entity type access.'),
            'NotFound'         => $json($ref('Error'), 'The entity type, record, code list or source system does not exist.'),
            'Conflict'         => $json($ref('Error'), 'The write conflicts with the record state: stale or missing revision, merged record, pending change or linked id.'),
            'PendingApproval'  => $json($ref('Error'), 'The write is held for approval; `change_request_id` names the change request.'),
            'ValidationFailed' => $json($ref('ValidationError'), 'The request is invalid.'),
        ],
        'schemas' => [
            'Error' => [
                'type'       => 'object',
                'properties' => [
                    'success'           => ['type' => 'boolean'],
                    'message'           => ['type' => 'string'],
                    'code'              => ['type' => 'string', 'examples' => array_column(ApiErrorCode::cases(), 'value')],
                    'change_request_id' => ['type' => 'integer'],
                ],
            ],
            'ValidationError' => [
                'type'       => 'object',
                'properties' => [
                    'message' => ['type' => 'string'],
                    'errors'  => ['type' => 'object', 'additionalProperties' => ['type' => 'array', 'items' => ['type' => 'string']]],
                ],
            ],
            'Values' => [
                'type'        => 'object',
                'description' => 'Field values keyed by field code: `common` for shared values, `locale_specific` per locale code.',
                'properties'  => [
                    'common'          => ['type' => 'object', 'additionalProperties' => true],
                    'locale_specific' => ['type' => 'object', 'additionalProperties' => ['type' => 'object', 'additionalProperties' => true]],
                ],
            ],
            'Record' => [
                'type'       => 'object',
                'properties' => [
                    'uuid'           => ['type' => 'string', 'format' => 'uuid'],
                    'code'           => ['type' => 'string'],
                    'label'          => ['type' => ['string', 'null']],
                    'status'         => ['type' => 'string', 'enum' => array_column(RecordStatus::cases(), 'value')],
                    'values'         => $ref('Values'),
                    'references'     => ['type' => 'object', 'additionalProperties' => true],
                    'relations'      => ['type' => 'object', 'additionalProperties' => true],
                    'revision'       => ['type' => 'integer'],
                    'schema_version' => ['type' => 'integer'],
                    'ids'            => ['type' => 'object', 'additionalProperties' => ['type' => 'array', 'items' => ['type' => 'string']], 'description' => 'External ids keyed by source system code.'],
                    'created_at'     => ['type' => 'string', 'format' => 'date-time'],
                    'updated_at'     => ['type' => 'string', 'format' => 'date-time'],
                ],
            ],
            'RecordInput' => [
                'type'       => 'object',
                'properties' => [
                    'code'   => ['type' => 'string', 'maxLength' => 255],
                    'label'  => ['type' => ['string', 'null'], 'maxLength' => 255],
                    'status' => ['type' => 'string', 'enum' => array_column(RecordStatus::cases(), 'value')],
                    'values' => $ref('Values'),
                    'parent' => ['type' => ['string', 'null'], 'description' => 'The uuid of the parent record.'],
                ],
            ],
            'Xref' => [
                'type'       => 'object',
                'properties' => [
                    'record' => $ref('Record'),
                    'ids'    => ['type' => 'object', 'additionalProperties' => ['type' => 'array', 'items' => ['type' => 'string']]],
                ],
            ],
            'Field' => [
                'type'       => 'object',
                'properties' => [
                    'code'     => ['type' => 'string'],
                    'type'     => ['type' => 'string'],
                    'labels'   => $translations,
                    'group'    => ['type' => ['string', 'null']],
                    'position' => ['type' => 'integer'],
                    'flags'    => [
                        'type'                 => 'object',
                        'additionalProperties' => ['type' => 'boolean'],
                    ],
                    'validation'            => ['type' => 'object', 'additionalProperties' => true],
                    'code_list'             => ['type' => ['string', 'null']],
                    'reference_entity_type' => ['type' => ['string', 'null']],
                ],
            ],
            'MatchRule' => [
                'type'       => 'object',
                'properties' => [
                    'target'      => ['type' => 'string'],
                    'comparator'  => ['type' => 'string'],
                    'weight'      => ['type' => 'number'],
                    'is_blocking' => ['type' => 'boolean'],
                    'options'     => ['type' => 'object', 'additionalProperties' => true],
                    'sort_order'  => ['type' => 'integer'],
                ],
            ],
            'SurvivorshipRule' => [
                'type'       => 'object',
                'properties' => [
                    'target'     => ['type' => ['string', 'null']],
                    'strategy'   => ['type' => 'string'],
                    'options'    => ['type' => 'object', 'additionalProperties' => true],
                    'sort_order' => ['type' => 'integer'],
                ],
            ],
            'EntityType' => [
                'type'       => 'object',
                'properties' => [
                    'code'                 => ['type' => 'string'],
                    'names'                => $translations,
                    'icon'                 => ['type' => ['string', 'null']],
                    'label_field_code'     => ['type' => ['string', 'null']],
                    'is_hierarchical'      => ['type' => 'boolean'],
                    'is_scoped'            => ['type' => 'boolean'],
                    'allowed_parent_types' => ['type' => 'array', 'items' => ['type' => 'string']],
                    'approval_required'    => ['type' => 'boolean'],
                    'mastering_style'      => ['type' => 'string'],
                    'code_strategy'        => ['type' => 'string'],
                    'schema_version'       => ['type' => 'integer'],
                    'status'               => ['type' => 'string', 'enum' => array_column(EntityTypeStatus::cases(), 'value')],
                    'fields'               => ['type' => 'array', 'items' => $ref('Field')],
                    'match_rules'          => ['type' => 'array', 'items' => $ref('MatchRule')],
                    'survivorship_rules'   => ['type' => 'array', 'items' => $ref('SurvivorshipRule')],
                ],
            ],
            'Code' => [
                'type'       => 'object',
                'properties' => [
                    'code'        => ['type' => 'string'],
                    'parent_code' => ['type' => ['string', 'null']],
                    'sort'        => ['type' => 'integer'],
                    'labels'      => $translations,
                    'attributes'  => ['type' => 'object', 'additionalProperties' => true],
                ],
            ],
            'CodeListVersion' => [
                'type'       => 'object',
                'properties' => [
                    'number'         => ['type' => 'integer'],
                    'status'         => ['type' => 'string', 'enum' => array_column(CodeListVersionStatus::cases(), 'value')],
                    'effective_from' => ['type' => ['string', 'null'], 'format' => 'date'],
                    'effective_to'   => ['type' => ['string', 'null'], 'format' => 'date'],
                    'codes'          => ['type' => 'array', 'items' => $ref('Code')],
                ],
            ],
            'CodeList' => [
                'type'       => 'object',
                'properties' => [
                    'code'              => ['type' => 'string'],
                    'names'             => $translations,
                    'owner'             => ['type' => ['string', 'null']],
                    'is_versioned'      => ['type' => 'boolean'],
                    'effective_version' => ['oneOf' => [$ref('CodeListVersion'), ['type' => 'null']]],
                    'versions'          => ['type' => 'array', 'items' => $ref('CodeListVersion')],
                ],
            ],
            'MatchCandidateInput' => [
                'type'       => 'object',
                'required'   => ['system', 'type', 'id'],
                'properties' => [
                    'system' => ['type' => 'string', 'maxLength' => 255],
                    'type'   => ['type' => 'string', 'maxLength' => 255],
                    'id'     => ['type' => 'string', 'maxLength' => 255],
                    'code'   => ['type' => ['string', 'null'], 'maxLength' => 255],
                    'label'  => ['type' => ['string', 'null'], 'maxLength' => 255, 'description' => 'Required without a code.'],
                    'values' => $ref('Values'),
                ],
            ],
            'MatchCandidate' => [
                'type'       => 'object',
                'properties' => [
                    'id'           => ['type' => 'integer'],
                    'system'       => ['type' => ['string', 'null']],
                    'type'         => ['type' => ['string', 'null']],
                    'external_id'  => ['type' => 'string'],
                    'label'        => ['type' => ['string', 'null']],
                    'status'       => ['type' => 'string', 'enum' => array_column(MatchCandidateStatus::cases(), 'value')],
                    'top_score'    => ['type' => ['number', 'null']],
                    'auto_matched' => ['type' => 'boolean'],
                    'suggestions'  => [
                        'type'  => 'array',
                        'items' => [
                            'type'       => 'object',
                            'properties' => [
                                'uuid'   => ['type' => 'string', 'format' => 'uuid'],
                                'code'   => ['type' => 'string'],
                                'label'  => ['type' => ['string', 'null']],
                                'score'  => ['type' => 'number'],
                                'reason' => ['type' => ['string', 'null']],
                            ],
                        ],
                    ],
                    'created_at' => ['type' => 'string', 'format' => 'date-time'],
                ],
            ],
        ],
    ],
];
