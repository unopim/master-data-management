<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Webkul\MasterDataManagement\Repositories\HierarchyRepository;
use Webkul\MasterDataManagement\Services\HierarchyService;

/**
 * Gives every entity type that arranges records by parent a primary hierarchy and copies the records' parent_id,
 * path and depth into its nodes in batches. The records keep those columns as the primary hierarchy's source.
 */
return new class extends Migration
{
    public function up(): void
    {
        $repository = resolve(HierarchyRepository::class);

        $service = resolve(HierarchyService::class);

        DB::table('mdm_entity_types')
            ->where(fn (Builder $types) => $types
                ->where('is_hierarchical', true)
                ->orWhereExists(fn (Builder $records) => $records
                    ->from('mdm_records')
                    ->whereColumn('mdm_records.entity_type_id', 'mdm_entity_types.id')
                    ->whereNotNull('mdm_records.parent_id')))
            ->orderBy('id')
            ->pluck('id')
            ->each(fn (mixed $id) => $service->backfillPrimary($repository->firstOrCreatePrimary((int) $id)[0]));
    }

    public function down(): void
    {
        DB::table('mdm_hierarchies')->where('is_primary', true)->delete();
    }
};
