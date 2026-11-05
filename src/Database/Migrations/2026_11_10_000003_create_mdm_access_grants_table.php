<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mdm_access_grants', function (Blueprint $table): void {
            $table->id();
            $table->string('principal_type', 8);
            $table->unsignedBigInteger('principal_id');
            $table->unsignedBigInteger('entity_type_id')->nullable();
            $table->unsignedBigInteger('record_id');
            $table->json('actions');
            $table->timestamps();

            $table->index(['principal_type', 'principal_id'], 'mdm_access_grants_principal_index');
            $table->index('record_id', 'mdm_access_grants_record_index');
            $table->index('entity_type_id', 'mdm_access_grants_entity_type_index');

            $table->foreign('entity_type_id', 'mdm_access_grants_type_foreign')
                ->references('id')
                ->on('mdm_entity_types')
                ->cascadeOnDelete();
            $table->foreign('record_id', 'mdm_access_grants_record_foreign')
                ->references('id')
                ->on('mdm_records')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mdm_access_grants');
    }
};
