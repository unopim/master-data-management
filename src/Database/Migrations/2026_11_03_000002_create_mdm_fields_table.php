<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mdm_fields', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('entity_type_id');
            $table->string('code', 64);
            $table->string('type', 32);
            $table->string('group', 64)->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_required')->default(false);
            $table->boolean('is_unique')->default(false);
            $table->boolean('is_identifier')->default(false);
            $table->boolean('value_per_locale')->default(false);
            $table->boolean('is_effective_dated')->default(false);
            $table->boolean('is_searchable')->default(false);
            $table->boolean('multiple')->default(false);
            $table->boolean('is_archived')->default(false);
            $table->json('validation')->nullable();
            $table->unsignedBigInteger('code_list_id')->nullable();
            $table->unsignedBigInteger('reference_entity_type_id')->nullable();
            $table->timestamps();

            $table->unique(['entity_type_id', 'code'], 'mdm_fields_type_code_unique');
            $table->index(['entity_type_id', 'is_archived', 'position'], 'mdm_fields_type_archived_position_index');
            $table->index('code_list_id', 'mdm_fields_code_list_index');
            $table->index('reference_entity_type_id', 'mdm_fields_reference_type_index');

            $table->foreign('entity_type_id', 'mdm_fields_type_foreign')
                ->references('id')
                ->on('mdm_entity_types')
                ->cascadeOnDelete();
            $table->foreign('reference_entity_type_id', 'mdm_fields_reference_type_foreign')
                ->references('id')
                ->on('mdm_entity_types')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mdm_fields');
    }
};
