<?php

use Webkul\AdminApi\Tests\Traits\ApiHelperTrait;
use Webkul\MasterDataManagement\Models\Hierarchy;
use Webkul\MasterDataManagement\Repositories\EntityTypeRepository;
use Webkul\MasterDataManagement\Repositories\HierarchyRepository;
use Webkul\MasterDataManagement\Services\RecordWriter;

uses(ApiHelperTrait::class);

beforeEach(function () {
    $this->type = app(EntityTypeRepository::class)->create(['code' => $this->uniqueCode('site'), 'status' => 'active', 'code_strategy' => 'source_id', 'is_hierarchical' => true]);
    $this->writer = app(RecordWriter::class);

    app(HierarchyRepository::class)->create(['entity_type_id' => $this->type->id, 'code' => 'sales', 'en_US' => ['label' => 'Sales']]);

    $this->root = $this->writer->create($this->type, ['code' => 'ROOT'], null);
    $this->site = $this->writer->create($this->type, ['code' => 'SITE', 'parent' => $this->root->uuid], null);
});

it('lists the hierarchies of an entity type', function () {
    $this->getJson(route('admin.api.masterdata.hierarchies.index', $this->type->code), $this->getAuthenticationHeaders())
        ->assertOk()
        ->assertJsonPath('data.0.code', Hierarchy::PRIMARY_CODE)
        ->assertJsonPath('data.0.is_primary', true)
        ->assertJsonPath('data.1.code', 'sales')
        ->assertJsonPath('data.1.labels.en_US', 'Sales');
});

it('places a record in an alternate hierarchy and lists its placements', function () {
    $headers = $this->getAuthenticationHeaders();

    $url = route('admin.api.masterdata.records.hierarchies.update', [$this->type->code, $this->root->uuid, 'sales']);

    $this->putJson($url, ['parent' => $this->site->uuid], $headers)
        ->assertOk()
        ->assertJsonFragment(['hierarchy' => 'sales', 'parent' => $this->site->uuid, 'depth' => 1]);

    $this->getJson(route('admin.api.masterdata.records.hierarchies.index', [$this->type->code, $this->site->uuid]), $headers)
        ->assertOk()
        ->assertJsonFragment(['hierarchy' => Hierarchy::PRIMARY_CODE, 'parent' => $this->root->uuid, 'ancestors' => [$this->root->uuid]])
        ->assertJsonFragment(['hierarchy' => 'sales', 'parent' => null, 'depth' => 0]);

    $this->putJson(route('admin.api.masterdata.records.hierarchies.update', [$this->type->code, $this->site->uuid, 'sales']), ['parent' => $this->root->uuid], $headers)
        ->assertUnprocessable()
        ->assertJsonValidationErrors('parent');

    $this->putJson(route('admin.api.masterdata.records.hierarchies.update', [$this->type->code, $this->site->uuid, Hierarchy::PRIMARY_CODE]), ['parent' => null], $headers)
        ->assertUnprocessable();

    $this->deleteJson($url, [], $headers)
        ->assertOk()
        ->assertJsonMissing(['hierarchy' => 'sales']);
});

it('answers 404 for an unknown hierarchy and denies writes without the edit permission', function () {
    $this->putJson(route('admin.api.masterdata.records.hierarchies.update', [$this->type->code, $this->site->uuid, 'unknown']), ['parent' => null], $this->getAuthenticationHeaders())
        ->assertNotFound()
        ->assertJsonPath('code', 'hierarchy_not_found');

    app('auth')->forgetGuards();

    $this->putJson(
        route('admin.api.masterdata.records.hierarchies.update', [$this->type->code, $this->site->uuid, 'sales']),
        ['parent' => null],
        $this->getAuthenticationHeaders('custom', ['api.masterdata', 'api.masterdata.records']),
    )->assertForbidden();
});
