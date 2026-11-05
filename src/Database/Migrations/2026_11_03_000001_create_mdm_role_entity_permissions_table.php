<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mdm_role_entity_permissions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedInteger('role_id');
            $table->unsignedBigInteger('entity_type_id');
            $table->json('actions');
            $table->timestamps();

            $table->unique(['role_id', 'entity_type_id'], 'mdm_rep_role_type_unique');
            $table->index('entity_type_id', 'mdm_rep_entity_type_index');

            $table->foreign('role_id', 'mdm_rep_role_foreign')
                ->references('id')
                ->on('roles')
                ->cascadeOnDelete();
            $table->foreign('entity_type_id', 'mdm_rep_type_foreign')
                ->references('id')
                ->on('mdm_entity_types')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mdm_role_entity_permissions');
    }
};
