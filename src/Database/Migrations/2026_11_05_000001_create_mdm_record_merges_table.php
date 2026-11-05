<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mdm_record_merges', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('entity_type_id');
            $table->unsignedBigInteger('survivor_id');
            $table->unsignedBigInteger('merged_id');
            $table->json('survivor_before');
            $table->json('merged_before');
            $table->json('result');
            $table->json('moved');
            $table->unsignedInteger('merged_by')->nullable();
            $table->timestamp('merged_at');
            $table->unsignedInteger('unmerged_by')->nullable();
            $table->timestamp('unmerged_at')->nullable();

            $table->index(['survivor_id', 'unmerged_at'], 'mdm_record_merges_survivor_index');
            $table->index(['merged_id', 'unmerged_at'], 'mdm_record_merges_merged_index');

            $table->foreign('entity_type_id', 'mdm_record_merges_type_foreign')
                ->references('id')
                ->on('mdm_entity_types')
                ->cascadeOnDelete();

            $table->foreign('survivor_id', 'mdm_record_merges_survivor_foreign')
                ->references('id')
                ->on('mdm_records')
                ->cascadeOnDelete();

            $table->foreign('merged_id', 'mdm_record_merges_merged_foreign')
                ->references('id')
                ->on('mdm_records')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mdm_record_merges');
    }
};
