<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mdm_validation_rules', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('field_id');
            $table->string('type', 32);
            $table->json('params')->nullable();
            $table->string('severity', 16)->default('block');
            $table->json('messages')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['field_id', 'sort_order'], 'mdm_validation_rules_field_sort_index');

            $table->foreign('field_id', 'mdm_validation_rules_field_foreign')
                ->references('id')
                ->on('mdm_fields')
                ->cascadeOnDelete();
        });

        Schema::create('mdm_field_transforms', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('field_id');
            $table->string('transform', 32);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['field_id', 'transform'], 'mdm_field_transforms_field_transform_unique');

            $table->foreign('field_id', 'mdm_field_transforms_field_foreign')
                ->references('id')
                ->on('mdm_fields')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mdm_field_transforms');
        Schema::dropIfExists('mdm_validation_rules');
    }
};
