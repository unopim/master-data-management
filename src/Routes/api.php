<?php

use Illuminate\Support\Facades\Route;
use Webkul\MasterDataManagement\Http\Controllers\Api\CodeListController;
use Webkul\MasterDataManagement\Http\Controllers\Api\EntityTypeController;
use Webkul\MasterDataManagement\Http\Controllers\Api\HierarchyController;
use Webkul\MasterDataManagement\Http\Controllers\Api\MatchCandidateController;
use Webkul\MasterDataManagement\Http\Controllers\Api\RecordController;
use Webkul\MasterDataManagement\Http\Controllers\Api\RecordUpsertController;
use Webkul\MasterDataManagement\Http\Controllers\Api\XrefController;

Route::group([
    'prefix'     => 'v1/rest/masterdata',
    'middleware' => [
        'accept.json',
        'auth:api',
        'throttle:rest-api',
        'api.scope',
        'request.locale',
        'cache.headers:private;etag',
    ],
], function (): void {
    Route::get('xref/resolve', [XrefController::class, 'resolve'])->name('admin.api.masterdata.xref.resolve');

    Route::get('entity-types', [EntityTypeController::class, 'index'])->name('admin.api.masterdata.entity_types.index');

    Route::get('entity-types/{typeCode}', [EntityTypeController::class, 'get'])->name('admin.api.masterdata.entity_types.get');

    Route::get('code-lists', [CodeListController::class, 'index'])->name('admin.api.masterdata.code_lists.index');

    Route::get('code-lists/{listCode}', [CodeListController::class, 'get'])->name('admin.api.masterdata.code_lists.get');

    Route::get('{type}/hierarchies', [HierarchyController::class, 'index'])->name('admin.api.masterdata.hierarchies.index');

    Route::get('{type}/records/{uuid}/hierarchies', [HierarchyController::class, 'placements'])->name('admin.api.masterdata.records.hierarchies.index');

    Route::put('{type}/records/{uuid}/hierarchies/{hierarchyCode}', [HierarchyController::class, 'place'])->name('admin.api.masterdata.records.hierarchies.update');

    Route::delete('{type}/records/{uuid}/hierarchies/{hierarchyCode}', [HierarchyController::class, 'remove'])->name('admin.api.masterdata.records.hierarchies.destroy');

    Route::get('{type}/records', [RecordController::class, 'index'])->name('admin.api.masterdata.records.index');

    Route::post('{type}/records', [RecordController::class, 'store'])
        ->middleware('masterdata.idempotent')
        ->name('admin.api.masterdata.records.store');

    Route::put('{type}/records/{uuid}', [RecordController::class, 'update'])
        ->middleware('masterdata.idempotent')
        ->name('admin.api.masterdata.records.update');

    Route::delete('{type}/records/{uuid}', [RecordController::class, 'delete'])
        ->middleware('masterdata.idempotent')
        ->name('admin.api.masterdata.records.delete');

    Route::post('match-candidates', [MatchCandidateController::class, 'store'])->name('admin.api.masterdata.match_candidates.store');

    Route::get('{type}/records/{uuid}', [RecordController::class, 'show'])->name('admin.api.masterdata.records.show');

    Route::put('{type}/records/by-xref/{system}/{externalId}', [RecordUpsertController::class, 'upsert'])
        ->where('externalId', '.+')
        ->name('admin.api.masterdata.records.upsert');
});
