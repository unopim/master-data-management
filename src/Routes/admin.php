<?php

use Illuminate\Support\Facades\Route;
use Webkul\MasterDataManagement\Http\Controllers\Admin\AccessGrantController;
use Webkul\MasterDataManagement\Http\Controllers\Admin\AliasController;
use Webkul\MasterDataManagement\Http\Controllers\Admin\ApprovalController;
use Webkul\MasterDataManagement\Http\Controllers\Admin\CodeController;
use Webkul\MasterDataManagement\Http\Controllers\Admin\CodeListController;
use Webkul\MasterDataManagement\Http\Controllers\Admin\CrosswalkController;
use Webkul\MasterDataManagement\Http\Controllers\Admin\DashboardController;
use Webkul\MasterDataManagement\Http\Controllers\Admin\EntityTypeController;
use Webkul\MasterDataManagement\Http\Controllers\Admin\ExportFilterController;
use Webkul\MasterDataManagement\Http\Controllers\Admin\FieldController;
use Webkul\MasterDataManagement\Http\Controllers\Admin\IconController;
use Webkul\MasterDataManagement\Http\Controllers\Admin\ImportFilterController;
use Webkul\MasterDataManagement\Http\Controllers\Admin\MatchingController;
use Webkul\MasterDataManagement\Http\Controllers\Admin\MatchRuleController;
use Webkul\MasterDataManagement\Http\Controllers\Admin\QualityController;
use Webkul\MasterDataManagement\Http\Controllers\Admin\RecordController;
use Webkul\MasterDataManagement\Http\Controllers\Admin\RecordLifecycleController;
use Webkul\MasterDataManagement\Http\Controllers\Admin\RecordMergeController;
use Webkul\MasterDataManagement\Http\Controllers\Admin\RecordXrefController;
use Webkul\MasterDataManagement\Http\Controllers\Admin\RelationTypeController;
use Webkul\MasterDataManagement\Http\Controllers\Admin\SourceSystemController;
use Webkul\MasterDataManagement\Http\Controllers\Admin\SurvivorshipRuleController;
use Webkul\MasterDataManagement\Repositories\EntityTypeRepository;

Route::bind('entity_type', fn (string $code) => resolve(EntityTypeRepository::class)->findOneByField('code', $code) ?? abort(404));

Route::group(['middleware' => ['admin'], 'prefix' => config('app.admin_url')], function (): void {
    Route::prefix('masterdata')->group(function (): void {
        Route::get('', [DashboardController::class, 'home'])->name('admin.masterdata.home');
        Route::get('records', [DashboardController::class, 'index'])->name('admin.masterdata.dashboard.index');

        Route::controller(EntityTypeController::class)->prefix('entity-types')->group(function (): void {
            Route::get('', 'index')->name('admin.masterdata.entity_types.index');
            Route::get('create', 'create')->name('admin.masterdata.entity_types.create');
            Route::post('', 'store')->name('admin.masterdata.entity_types.store');
            Route::get('{id}/edit', 'edit')->name('admin.masterdata.entity_types.edit')->whereNumber('id');
            Route::put('{id}', 'update')->name('admin.masterdata.entity_types.update')->whereNumber('id');
            Route::put('{id}/permissions', 'updatePermissions')->name('admin.masterdata.entity_types.permissions.update')->whereNumber('id');
            Route::post('{id}/publish', 'publish')->name('admin.masterdata.entity_types.publish')->whereNumber('id');
            Route::delete('{id}', 'destroy')->name('admin.masterdata.entity_types.destroy')->whereNumber('id');
            Route::post('mass-delete', 'massDestroy')->name('admin.masterdata.entity_types.mass_delete');
        });

        Route::controller(FieldController::class)->prefix('entity-types/{entityTypeId}/fields')->whereNumber('entityTypeId')->group(function (): void {
            Route::get('', 'index')->name('admin.masterdata.fields.index');
            Route::post('', 'store')->name('admin.masterdata.fields.store');
            Route::put('{id}', 'update')->name('admin.masterdata.fields.update')->whereNumber('id');
            Route::delete('{id}', 'destroy')->name('admin.masterdata.fields.destroy')->whereNumber('id');
        });

        Route::controller(RelationTypeController::class)->prefix('entity-types/{entityTypeId}/relation-types')->whereNumber('entityTypeId')->group(function (): void {
            Route::get('', 'index')->name('admin.masterdata.relation_types.index');
            Route::post('', 'store')->name('admin.masterdata.relation_types.store');
            Route::put('{id}', 'update')->name('admin.masterdata.relation_types.update')->whereNumber('id');
            Route::delete('{id}', 'destroy')->name('admin.masterdata.relation_types.destroy')->whereNumber('id');
        });

        Route::controller(MatchRuleController::class)->prefix('entity-types/{entityTypeId}/match-rules')->whereNumber('entityTypeId')->group(function (): void {
            Route::get('', 'index')->name('admin.masterdata.match_rules.index');
            Route::post('', 'store')->name('admin.masterdata.match_rules.store');
            Route::put('{id}', 'update')->name('admin.masterdata.match_rules.update')->whereNumber('id');
            Route::delete('{id}', 'destroy')->name('admin.masterdata.match_rules.destroy')->whereNumber('id');
        });

        Route::controller(SurvivorshipRuleController::class)->prefix('entity-types/{entityTypeId}/survivorship-rules')->whereNumber('entityTypeId')->group(function (): void {
            Route::get('', 'index')->name('admin.masterdata.survivorship_rules.index');
            Route::post('', 'store')->name('admin.masterdata.survivorship_rules.store');
            Route::put('{id}', 'update')->name('admin.masterdata.survivorship_rules.update')->whereNumber('id');
            Route::delete('{id}', 'destroy')->name('admin.masterdata.survivorship_rules.destroy')->whereNumber('id');
        });

        Route::controller(AliasController::class)->prefix('code-lists/aliases')->group(function (): void {
            Route::get('', 'index')->name('admin.masterdata.aliases.index');
            Route::post('', 'store')->name('admin.masterdata.aliases.store');
            Route::put('{id}', 'update')->name('admin.masterdata.aliases.update')->whereNumber('id');
            Route::delete('{id}', 'destroy')->name('admin.masterdata.aliases.destroy')->whereNumber('id');
        });

        Route::controller(CodeListController::class)->prefix('code-lists')->group(function (): void {
            Route::get('', 'index')->name('admin.masterdata.code_lists.index');
            Route::get('create', 'create')->name('admin.masterdata.code_lists.create');
            Route::get('options', 'options')->name('admin.masterdata.code_lists.options');
            Route::post('', 'store')->name('admin.masterdata.code_lists.store');
            Route::get('{id}/edit', 'edit')->name('admin.masterdata.code_lists.edit')->whereNumber('id');
            Route::put('{id}', 'update')->name('admin.masterdata.code_lists.update')->whereNumber('id');
            Route::delete('{id}', 'destroy')->name('admin.masterdata.code_lists.destroy')->whereNumber('id');
            Route::post('{id}/versions', 'storeVersion')->name('admin.masterdata.code_lists.versions.store')->whereNumber('id');
            Route::post('{id}/versions/{versionId}/publish', 'publishVersion')->name('admin.masterdata.code_lists.versions.publish')->whereNumber(['id', 'versionId']);
        });

        Route::controller(CodeController::class)->prefix('code-lists/{id}/versions/{versionId}/codes')->whereNumber(['id', 'versionId'])->group(function (): void {
            Route::get('', 'index')->name('admin.masterdata.codes.index');
            Route::post('', 'store')->name('admin.masterdata.codes.store');
            Route::put('{codeId}', 'update')->name('admin.masterdata.codes.update')->whereNumber('codeId');
            Route::delete('{codeId}', 'destroy')->name('admin.masterdata.codes.destroy')->whereNumber('codeId');
        });

        Route::controller(RecordController::class)->prefix('records')->group(function (): void {
            Route::get('{entity_type}', 'index')->name('admin.masterdata.records.index')->where('entity_type', '[A-Za-z0-9_\-]+');
            Route::get('{entity_type}/create', 'create')->name('admin.masterdata.records.create')->where('entity_type', '[A-Za-z0-9_\-]+');
            Route::get('{entity_type}/options', 'options')->name('admin.masterdata.records.options')->where('entity_type', '[A-Za-z0-9_\-]+');
            Route::post('{entity_type}', 'store')->name('admin.masterdata.records.store')->where('entity_type', '[A-Za-z0-9_\-]+');
            Route::get('record/{uuid}', 'edit')->name('admin.masterdata.records.edit')->whereUuid('uuid');
            Route::put('record/{uuid}', 'update')->name('admin.masterdata.records.update')->whereUuid('uuid');
            Route::post('record/{uuid}/retire', 'retire')->name('admin.masterdata.records.retire')->whereUuid('uuid');
            Route::post('record/{uuid}/reactivate', 'reactivate')->name('admin.masterdata.records.reactivate')->whereUuid('uuid');
            Route::put('record/{uuid}/parent', 'updateParent')->name('admin.masterdata.records.hierarchy.update')->whereUuid('uuid');
        });

        Route::controller(RecordLifecycleController::class)->prefix('records')->group(function (): void {
            Route::post('{entity_type}/mass-retire', 'massRetire')->name('admin.masterdata.records.mass_retire')->where('entity_type', '[A-Za-z0-9_\-]+');
            Route::post('record/{uuid}/erase', 'erase')->name('admin.masterdata.records.erase')->whereUuid('uuid');
        });

        Route::controller(RecordMergeController::class)->prefix('records/record/{uuid}')->whereUuid('uuid')->group(function (): void {
            Route::get('merge', 'create')->name('admin.masterdata.records.merge.create');
            Route::get('merge/options', 'options')->name('admin.masterdata.records.merge.options');
            Route::post('merge', 'store')->name('admin.masterdata.records.merge.store');
            Route::delete('merges/{mergeId}', 'destroy')->name('admin.masterdata.records.merge.destroy')->whereNumber('mergeId');
        });

        Route::controller(RecordXrefController::class)->prefix('records/record/{uuid}/xrefs')->whereUuid('uuid')->group(function (): void {
            Route::post('', 'store')->name('admin.masterdata.records.xrefs.store');
            Route::delete('{xrefId}', 'destroy')->name('admin.masterdata.records.xrefs.destroy')->whereNumber('xrefId');
        });

        Route::controller(MatchingController::class)->prefix('matching')->group(function (): void {
            Route::get('', 'index')->name('admin.masterdata.matching.index');
            Route::post('', 'store')->name('admin.masterdata.matching.store');
            Route::get('{id}', 'show')->name('admin.masterdata.matching.show')->whereNumber('id');
            Route::post('{id}/accept', 'accept')->name('admin.masterdata.matching.accept')->whereNumber('id');
            Route::post('{id}/create-record', 'createRecord')->name('admin.masterdata.matching.create_record')->whereNumber('id');
            Route::post('{id}/reject', 'reject')->name('admin.masterdata.matching.reject')->whereNumber('id');
        });

        Route::controller(ApprovalController::class)->prefix('approvals')->group(function (): void {
            Route::get('', 'index')->name('admin.masterdata.approvals.index');
            Route::get('{id}', 'show')->name('admin.masterdata.approvals.show')->whereNumber('id');
            Route::post('{id}/approve', 'approve')->name('admin.masterdata.approvals.approve')->whereNumber('id');
            Route::post('{id}/reject', 'reject')->name('admin.masterdata.approvals.reject')->whereNumber('id');
        });

        Route::get('crosswalk', [CrosswalkController::class, 'index'])->name('admin.masterdata.crosswalk.index');

        Route::get('exports/filters/entity-types', [ExportFilterController::class, 'entityTypes'])->name('admin.masterdata.exports.filters.entity_types');

        Route::controller(ImportFilterController::class)->prefix('imports/filters')->group(function (): void {
            Route::get('entity-types', 'entityTypes')->name('admin.masterdata.imports.filters.entity_types');
            Route::get('source-systems', 'sourceSystems')->name('admin.masterdata.imports.filters.source_systems');
            Route::get('code-lists', 'codeLists')->name('admin.masterdata.imports.filters.code_lists');
        });

        Route::controller(QualityController::class)->prefix('quality')->group(function (): void {
            Route::get('', 'index')->name('admin.masterdata.quality.index');
            Route::post('entity-types/{id}/scan', 'scan')->name('admin.masterdata.quality.scan')->whereNumber('id');
            Route::post('issues/{id}/resolve', 'resolve')->name('admin.masterdata.quality.issues.resolve')->whereNumber('id');
            Route::post('issues/{id}/ignore', 'ignore')->name('admin.masterdata.quality.issues.ignore')->whereNumber('id');
        });

        Route::controller(IconController::class)->prefix('icons')->group(function (): void {
            Route::get('', 'index')->name('admin.masterdata.icons.index');
            Route::get('create', 'create')->name('admin.masterdata.icons.create');
            Route::post('', 'store')->name('admin.masterdata.icons.store');
            Route::get('{id}/edit', 'edit')->name('admin.masterdata.icons.edit')->whereNumber('id');
            Route::get('{id}/file', 'file')->name('admin.masterdata.icons.file')->whereNumber('id');
            Route::put('{id}', 'update')->name('admin.masterdata.icons.update')->whereNumber('id');
            Route::delete('{id}', 'destroy')->name('admin.masterdata.icons.destroy')->whereNumber('id');
        });

        Route::controller(AccessGrantController::class)->prefix('access')->group(function (): void {
            Route::get('', 'index')->name('admin.masterdata.access.index');
            Route::get('create', 'create')->name('admin.masterdata.access.create');
            Route::get('options', 'options')->name('admin.masterdata.access.options');
            Route::post('', 'store')->name('admin.masterdata.access.store');
            Route::get('{id}/edit', 'edit')->name('admin.masterdata.access.edit')->whereNumber('id');
            Route::put('{id}', 'update')->name('admin.masterdata.access.update')->whereNumber('id');
            Route::delete('{id}', 'destroy')->name('admin.masterdata.access.destroy')->whereNumber('id');
        });

        Route::controller(SourceSystemController::class)->prefix('source-systems')->group(function (): void {
            Route::get('', 'index')->name('admin.masterdata.source_systems.index');
            Route::get('create', 'create')->name('admin.masterdata.source_systems.create');
            Route::get('options', 'options')->name('admin.masterdata.source_systems.options');
            Route::post('', 'store')->name('admin.masterdata.source_systems.store');
            Route::get('{id}/edit', 'edit')->name('admin.masterdata.source_systems.edit')->whereNumber('id');
            Route::put('{id}', 'update')->name('admin.masterdata.source_systems.update')->whereNumber('id');
            Route::delete('{id}', 'destroy')->name('admin.masterdata.source_systems.destroy')->whereNumber('id');
        });
    });
});
