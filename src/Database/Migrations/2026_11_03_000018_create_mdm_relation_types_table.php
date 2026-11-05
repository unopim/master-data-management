<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mdm_relation_types', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 160)->unique('mdm_relation_types_code_unique');
            $table->unsignedBigInteger('from_entity_type_id');
            $table->unsignedBigInteger('to_entity_type_id');
            $table->string('cardinality', 8);
            $table->boolean('is_system')->default(false);
            $table->unsignedBigInteger('field_id')->nullable();
            $table->timestamps();

            $table->index('from_entity_type_id', 'mdm_relation_types_from_type_index');
            $table->index('to_entity_type_id', 'mdm_relation_types_to_type_index');
            $table->index('field_id', 'mdm_relation_types_field_index');

            $table->foreign('from_entity_type_id', 'mdm_relation_types_from_type_foreign')
                ->references('id')
                ->on('mdm_entity_types')
                ->cascadeOnDelete();

            $table->foreign('to_entity_type_id', 'mdm_relation_types_to_type_foreign')
                ->references('id')
                ->on('mdm_entity_types')
                ->cascadeOnDelete();

            $table->foreign('field_id', 'mdm_relation_types_field_foreign')
                ->references('id')
                ->on('mdm_fields')
                ->cascadeOnDelete();
        });

        Schema::create('mdm_relation_type_translations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('relation_type_id');
            $table->string('locale', 16);
            $table->string('label')->nullable();

            $table->unique(['relation_type_id', 'locale'], 'mdm_rtt_type_locale_unique');

            $table->foreign('relation_type_id', 'mdm_rtt_type_foreign')
                ->references('id')
                ->on('mdm_relation_types')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mdm_relation_type_translations');
        Schema::dropIfExists('mdm_relation_types');
    }
};
