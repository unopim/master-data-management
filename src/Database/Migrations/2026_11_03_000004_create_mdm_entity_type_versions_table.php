<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mdm_entity_type_versions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('entity_type_id');
            $table->unsignedInteger('version');
            $table->json('fields');
            $table->unsignedInteger('published_by')->nullable();
            $table->timestamp('published_at');

            $table->unique(['entity_type_id', 'version'], 'mdm_etv_type_version_unique');

            $table->foreign('entity_type_id', 'mdm_etv_type_foreign')
                ->references('id')
                ->on('mdm_entity_types')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mdm_entity_type_versions');
    }
};
