<?php

use Illuminate\Database\Eloquent\Builder;
use Webkul\AdminApi\Tests\Traits\ApiHelperTrait;
use Webkul\MasterDataManagement\Models\Record;
use Webkul\MasterDataManagement\Repositories\EntityTypeRepository;
use Webkul\MasterDataManagement\Repositories\SourceSystemRepository;
use Webkul\MasterDataManagement\Security\RecordScope;
use Webkul\MasterDataManagement\Services\RecordWriter;
use Webkul\MasterDataManagement\Services\XrefService;
use Webkul\User\Models\Admin;

uses(ApiHelperTrait::class);

beforeEach(function () {
    $this->type = app(EntityTypeRepository::class)->create(['code' => $this->uniqueCode('line')]);
    $this->writer = app(RecordWriter::class);
    $this->xrefs = app(XrefService::class);
    $this->erp = app(SourceSystemRepository::class)->create(['code' => $this->uniqueCode('erp'), 'name' => 'ERP', 'type' => 'erp']);
    $this->mes = app(SourceSystemRepository::class)->create(['code' => $this->uniqueCode('mes'), 'name' => 'MES', 'type' => 'mes']);
});

it('resolves an external id to all system ids', function () {
    $record = $this->writer->create($this->type, ['label' => 'Line A'], null);
    $this->xrefs->attach($record, $this->erp, 'L-100');
    $this->xrefs->attach($record, $this->mes, 'M-7');
    $this->xrefs->attach($record, $this->mes, 'M-8');

    $this->getJson($this->resolveUrl(['system' => $this->erp->code, 'type' => $this->type->code, 'id' => 'L-100']), $this->getAuthenticationHeaders())
        ->assertOk()
        ->assertJsonPath('data.record.uuid', $record->uuid)
        ->assertJsonPath('data.record.label', 'Line A')
        ->assertJsonPath("data.ids.{$this->erp->code}", ['L-100'])
        ->assertJsonPath("data.ids.{$this->mes->code}", ['M-7', 'M-8']);
});

it('resolves a merged loser id to the survivor', function () {
    $survivor = $this->writer->create($this->type, ['label' => 'Survivor'], null);
    $loser = $this->writer->create($this->type, ['label' => 'Loser'], null);
    $this->xrefs->attach($loser, $this->erp, 'L-LOSER');
    Record::query()->whereKey($loser->id)->update(['merged_into_id' => $survivor->id]);

    $this->getJson($this->resolveUrl(['system' => $this->erp->code, 'type' => $this->type->code, 'id' => 'L-LOSER']), $this->getAuthenticationHeaders())
        ->assertOk()
        ->assertJsonPath('data.record.uuid', $survivor->uuid);
});

it('returns 404 code xref_not_found', function (array $override) {
    $query = $override + ['system' => $this->erp->code, 'type' => $this->type->code, 'id' => 'nope'];

    $this->getJson($this->resolveUrl($query), $this->getAuthenticationHeaders())
        ->assertNotFound()
        ->assertJsonPath('success', false)
        ->assertJsonPath('code', 'xref_not_found');
})->with([
    'unknown id'     => [['id' => 'nope']],
    'unknown system' => [['system' => 'zzz']],
    'unknown type'   => [['type' => 'zzz']],
]);

it('rejects missing resolve params with 422', function () {
    $this->getJson($this->resolveUrl(['system' => $this->erp->code]), $this->getAuthenticationHeaders())
        ->assertUnprocessable()
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', 'Validation failed.')
        ->assertJsonValidationErrors(['type', 'id']);
});

it('shows a record with its ids', function () {
    $record = $this->writer->create($this->type, ['label' => 'Line A'], null);
    $this->xrefs->attach($record, $this->erp, 'L-100');

    $this->getJson(route('admin.api.masterdata.records.show', [$this->type->code, $record->uuid]), $this->getAuthenticationHeaders())
        ->assertOk()
        ->assertJsonPath('data.uuid', $record->uuid)
        ->assertJsonPath("data.ids.{$this->erp->code}", ['L-100']);
});

it('returns 301 for a merged uuid', function () {
    $survivor = $this->writer->create($this->type, ['label' => 'Survivor'], null);
    $loser = $this->writer->create($this->type, ['label' => 'Loser'], null);
    Record::query()->whereKey($loser->id)->update(['merged_into_id' => $survivor->id]);

    $this->getJson(route('admin.api.masterdata.records.show', [$this->type->code, $loser->uuid]), $this->getAuthenticationHeaders())
        ->assertStatus(301)
        ->assertHeader('Location', route('admin.api.masterdata.records.show', [$this->type->code, $survivor->uuid]));
});

it('returns 404 for an unknown uuid or a uuid of another type', function () {
    $other = app(EntityTypeRepository::class)->create(['code' => $this->uniqueCode('asset')]);
    $record = $this->writer->create($other, ['label' => 'Pump'], null);
    $headers = $this->getAuthenticationHeaders();

    $this->getJson(route('admin.api.masterdata.records.show', [$this->type->code, $record->uuid]), $headers)
        ->assertNotFound()
        ->assertJsonPath('success', false)
        ->assertJsonPath('code', 'record_not_found');

    $this->getJson(route('admin.api.masterdata.records.show', [$this->type->code, fake()->uuid()]), $headers)
        ->assertNotFound();

    $this->getJson(route('admin.api.masterdata.records.show', ['zzz', $record->uuid]), $headers)
        ->assertNotFound()
        ->assertJsonPath('code', 'entity_type_not_found');
});

it('denies an api key without masterdata scope', function () {
    $record = $this->writer->create($this->type, ['label' => 'Line A'], null);
    $headers = $this->getAuthenticationHeaders('custom', ['api.settings.channels']);

    $this->getJson($this->resolveUrl(['system' => $this->erp->code, 'type' => $this->type->code, 'id' => 'x']), $headers)->assertForbidden();
    $this->getJson(route('admin.api.masterdata.records.show', [$this->type->code, $record->uuid]), $headers)->assertForbidden();
});

it('allows an api key with the masterdata records scope', function () {
    $headers = $this->getAuthenticationHeaders('custom', ['api.masterdata.records']);

    $this->getJson($this->resolveUrl(['system' => $this->erp->code, 'type' => $this->type->code, 'id' => 'x']), $headers)->assertNotFound();
});

it('applies the record scope to api reads', function () {
    $hidden = $this->writer->create($this->type, ['label' => 'Hidden'], null);
    $visible = $this->writer->create($this->type, ['label' => 'Visible'], null);
    $this->xrefs->attach($hidden, $this->erp, 'H-1');
    $this->xrefs->attach($visible, $this->erp, 'V-1');
    $headers = $this->getAuthenticationHeaders();

    app()->instance(RecordScope::class, new class($hidden->id) extends RecordScope
    {
        public function __construct(protected int $hiddenId) {}

        public function apply(Builder $query, Admin $admin, int $entityTypeId): Builder
        {
            return $query->whereKeyNot($this->hiddenId);
        }
    });

    $this->getJson($this->resolveUrl(['system' => $this->erp->code, 'type' => $this->type->code, 'id' => 'H-1']), $headers)
        ->assertNotFound()->assertJsonPath('code', 'xref_not_found');
    $this->getJson(route('admin.api.masterdata.records.show', [$this->type->code, $hidden->uuid]), $headers)
        ->assertNotFound()->assertJsonPath('code', 'record_not_found');
    $this->getJson($this->resolveUrl(['system' => $this->erp->code, 'type' => $this->type->code, 'id' => 'V-1']), $headers)->assertOk();
    $this->getJson(route('admin.api.masterdata.records.show', [$this->type->code, $visible->uuid]), $headers)->assertOk();
});
