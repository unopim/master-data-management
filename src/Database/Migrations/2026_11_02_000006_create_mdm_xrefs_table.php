<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * External ids are opaque and case-sensitive, so the MySQL family gets a binary collation to match PostgreSQL's exact comparison.
     */
    public function up(): void
    {
        Schema::create('mdm_xrefs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('record_id');
            $table->unsignedBigInteger('entity_type_id');
            $table->unsignedBigInteger('source_system_id');
            $externalId = $table->string('external_id');

            if (DB::connection()->getDriverName() !== 'pgsql') {
                $externalId->collation('utf8mb4_bin');
            }

            $table->string('status', 32)->default('active');
            $table->string('last_pushed_hash', 64)->nullable();
            $table->timestamp('first_seen_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();

            $table->unique(['source_system_id', 'entity_type_id', 'external_id'], 'mdm_xrefs_system_type_external_unique');
            $table->index('record_id', 'mdm_xrefs_record_index');
            $table->index('entity_type_id', 'mdm_xrefs_entity_type_index');

            $table->foreign('record_id', 'mdm_xrefs_record_foreign')
                ->references('id')
                ->on('mdm_records')
                ->cascadeOnDelete();
            $table->foreign('entity_type_id', 'mdm_xrefs_type_foreign')
                ->references('id')
                ->on('mdm_entity_types')
                ->restrictOnDelete();
            $table->foreign('source_system_id', 'mdm_xrefs_system_foreign')
                ->references('id')
                ->on('mdm_source_systems')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mdm_xrefs');
    }
};
