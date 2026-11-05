<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mdm_entity_type_translations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('entity_type_id');
            $table->string('locale', 32);
            $table->string('name')->nullable();
            $table->text('description')->nullable();

            $table->unique(['entity_type_id', 'locale'], 'mdm_ett_type_locale_unique');
            $table->index(['locale', 'entity_type_id'], 'mdm_ett_locale_type_index');

            $table->foreign('entity_type_id', 'mdm_ett_type_foreign')
                ->references('id')
                ->on('mdm_entity_types')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mdm_entity_type_translations');
    }
};
