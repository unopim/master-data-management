<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mdm_records', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('entity_type_id');
            $table->string('code');
            $table->string('label')->nullable();
            $table->json('values')->nullable();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->string('path')->nullable();
            $table->unsignedSmallInteger('depth')->default(0);
            $table->string('status', 32)->default('active');
            $table->unsignedBigInteger('merged_into_id')->nullable();
            $table->decimal('dq_score', 5, 2)->nullable();
            $table->unsignedInteger('schema_version')->default(0);
            $table->unsignedInteger('revision')->default(1);
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['entity_type_id', 'code'], 'mdm_records_type_code_unique');
            $table->index(['entity_type_id', 'status'], 'mdm_records_type_status_index');
            $table->index('path', 'mdm_records_path_index');
            $table->index('parent_id', 'mdm_records_parent_index');
            $table->index('merged_into_id', 'mdm_records_merged_into_index');
            $table->index(['updated_at', 'id'], 'mdm_records_updated_id_index');

            $table->foreign('entity_type_id', 'mdm_records_type_foreign')
                ->references('id')
                ->on('mdm_entity_types')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mdm_records');
    }
};
