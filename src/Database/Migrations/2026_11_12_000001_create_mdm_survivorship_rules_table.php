<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mdm_survivorship_rules', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('entity_type_id');
            $table->string('target', 64)->nullable();
            $table->string('strategy', 32);
            $table->json('options')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['entity_type_id', 'target'], 'mdm_survivorship_rules_type_target_unique');

            $table->foreign('entity_type_id', 'mdm_survivorship_rules_type_foreign')
                ->references('id')
                ->on('mdm_entity_types')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mdm_survivorship_rules');
    }
};
