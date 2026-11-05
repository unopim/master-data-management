<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * External ids are opaque and case-sensitive, so the MySQL family gets the same binary collation as `mdm_xrefs`.
     */
    public function up(): void
    {
        Schema::create('mdm_match_candidates', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('source_system_id');
            $table->unsignedBigInteger('entity_type_id');

            $externalId = $table->string('external_id');

            if (DB::connection()->getDriverName() !== 'pgsql') {
                $externalId->collation('utf8mb4_bin');
            }

            $table->string('label')->nullable();
            $table->json('payload')->nullable();
            $table->string('status', 32)->default('pending');
            $table->decimal('top_score', 5, 4)->nullable();
            $table->unsignedBigInteger('matched_record_id')->nullable();
            $table->unsignedInteger('submitted_by')->nullable();
            $table->unsignedInteger('resolved_by')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->unique(['source_system_id', 'entity_type_id', 'external_id'], 'mdm_match_candidates_system_type_external_unique');
            $table->index(['status', 'entity_type_id'], 'mdm_match_candidates_status_type_index');
            $table->index('entity_type_id', 'mdm_match_candidates_entity_type_index');
            $table->index('matched_record_id', 'mdm_match_candidates_matched_record_index');

            $table->foreign('source_system_id', 'mdm_match_candidates_system_foreign')
                ->references('id')
                ->on('mdm_source_systems')
                ->restrictOnDelete();

            $table->foreign('entity_type_id', 'mdm_match_candidates_type_foreign')
                ->references('id')
                ->on('mdm_entity_types')
                ->restrictOnDelete();

            $table->foreign('matched_record_id', 'mdm_match_candidates_record_foreign')
                ->references('id')
                ->on('mdm_records')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mdm_match_candidates');
    }
};
