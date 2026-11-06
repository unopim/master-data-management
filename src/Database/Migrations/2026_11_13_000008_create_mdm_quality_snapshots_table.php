<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mdm_quality_snapshots', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('entity_type_id');
            $table->date('snapshot_date');
            $table->unsignedInteger('record_count')->default(0);
            $table->decimal('completeness_avg', 5, 2)->nullable();
            $table->decimal('validity_rate', 5, 2)->nullable();
            $table->decimal('duplicate_rate', 5, 2)->nullable();
            $table->unsignedInteger('open_tasks')->default(0);
            $table->timestamps();

            $table->unique(['entity_type_id', 'snapshot_date'], 'mdm_quality_snapshots_type_date_unique');

            $table->foreign('entity_type_id', 'mdm_quality_snapshots_type_foreign')
                ->references('id')
                ->on('mdm_entity_types')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mdm_quality_snapshots');
    }
};
